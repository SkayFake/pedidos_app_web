<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Http\Resources\V1\FoodReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @group Productos
 *
 * Endpoints para consultar el catálogo de productos disponibles,
 * con soporte para filtrado, búsqueda y paginación.
 * Incluye lógica inteligente de productos populares y recomendados.
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Listar productos
     *
     * Retorna un listado paginado de productos disponibles.
     * Soporta filtros por categoría, búsqueda por texto, y flags de recomendados/populares.
     *
     * - `popular=true` → Ordena por más vendidos (basado en ventas reales)
     * - `recommended=true` → Productos personalizados según historial del usuario
     *
     * @queryParam category_id integer Filtrar por ID de categoría. Example: 1
     * @queryParam branch_id integer Filtrar por ID de sucursal. Example: 1
     * @queryParam search string Buscar por nombre o descripción. Example: hamburguesa
     * @queryParam recommended boolean Mostrar productos recomendados inteligentes. Example: 1
     * @queryParam popular boolean Mostrar productos ordenados por más vendidos. Example: 1
     * @queryParam per_page integer Cantidad de resultados por página (máx 50). Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $version = Cache::get('products_cache_version', 1);
        $hasSearch = $request->filled('search');
        $cacheKey = "api.products.v{$version}." . md5($request->fullUrl() . '.' . (auth()->id() ?? 'guest'));

        // No usar caché para búsquedas para garantizar resultados frescos
        $buildQuery = function () use ($request) {
            $query = Product::query()
                ->where('is_available', true)
                ->with(['category', 'branch']);

            // Filtro por sucursal
            if ($request->filled('branch_id')) {
                $branchId = $request->integer('branch_id');
                $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                });
            }

            // Filtro por categoría
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->integer('category_id'));
            }

            // Búsqueda por nombre o descripción — compatible con MySQL/PostgreSQL
            if ($request->filled('search')) {
                $search = $request->string('search')->trim();
                $escapedSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
                $driver = DB::connection()->getDriverName();
                $likeOperator = ($driver === 'pgsql') ? 'ilike' : 'like';
                $query->where(function ($q) use ($escapedSearch, $likeOperator) {
                    $q->where('name', $likeOperator, "%{$escapedSearch}%")
                      ->orWhere('description', $likeOperator, "%{$escapedSearch}%");
                });
            }

            // Filtro por populares — basado en ventas reales (dinámico)
            if ($request->boolean('popular')) {
                $query->withCount(['orderItems as total_sold' => function ($q) {
                    $q->select(DB::raw('COALESCE(SUM(order_items.quantity), 0)'));
                }])
                ->orderByDesc('total_sold');
            }

            // Filtro por recomendados — personalizado por usuario
            if ($request->boolean('recommended')) {
                $user = auth()->user();

                if ($user) {
                    // Obtener categorías que el usuario más compra
                    $preferredCategoryIds = OrderItem::whereHas('order', function ($q) use ($user) {
                        $q->where('user_id', $user->id)->where('status', 'delivered');
                    })
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->select('products.category_id', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('products.category_id')
                    ->orderByDesc('cnt')
                    ->limit(3)
                    ->pluck('category_id');

                    if ($preferredCategoryIds->isNotEmpty()) {
                        // Productos de categorías preferidas que no ha comprado
                        $purchasedProductIds = OrderItem::whereHas('order', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })->pluck('product_id');

                        $query->whereIn('category_id', $preferredCategoryIds)
                              ->whereNotIn('id', $purchasedProductIds);
                    } else {
                        // Sin historial: mostrar los mejor calificados
                        $query->where('stars', '>=', 4.0)->orderByDesc('stars');
                    }
                } else {
                    // Sin autenticación: mejores calificados
                    $query->where('stars', '>=', 4.0)->orderByDesc('stars');
                }
            }

            // Ordenamiento por sort_by (para la pantalla de búsqueda)
            $sortBy = $request->string('sort_by')->toString();
            if ($sortBy && !$request->boolean('popular') && !$request->boolean('recommended')) {
                match ($sortBy) {
                    'most_sold' => $query->withCount(['orderItems as total_sold' => function ($q) {
                                        $q->select(DB::raw('COALESCE(SUM(order_items.quantity), 0)'));
                                    }])->orderByDesc('total_sold'),
                    'price_low'  => $query->orderBy('price', 'asc'),
                    'price_high' => $query->orderBy('price', 'desc'),
                    default      => $query->orderBy('name'),
                };
            } elseif (!$request->boolean('popular')) {
                $query->orderBy('name');
            }

            $perPage = min($request->integer('per_page', 15), 50);

            return $query->paginate($perPage);
        };

        // Omitir caché para búsquedas con texto o sort dinámico
        if ($hasSearch || $request->filled('sort_by')) {
            $paginator = $buildQuery();
        } else {
            $paginator = Cache::remember($cacheKey, 3600, $buildQuery);
        }

        return ProductResource::collection($paginator);
    }

    /**
     * Detalle de producto
     *
     * Retorna la información completa de un producto, incluyendo sus variantes
     * y extras disponibles. Retorna 404 si el producto no está disponible.
     *
     * @urlParam product integer required ID del producto. Example: 1
     */
    public function show(Product $product): JsonResponse
    {
        $version = Cache::get('products_cache_version', 1);
        $cacheKey = "api.product.v{$version}.{$product->id}";

        $productData = Cache::remember($cacheKey, 3600, function () use ($product) {
            if (!$product->is_available) {
                return null;
            }

            return $product->load([
                'category',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('is_default', 'desc'),
                'extras'   => fn ($q) => $q->where('is_available', true),
                'reviews'  => fn ($q) => $q->with('user')->latest()->limit(5),
            ]);
        });

        if (!$productData) {
            return $this->error('Este producto no está disponible actualmente.', 404);
        }

        return $this->success(
            new ProductResource($productData),
            'Detalle del producto.'
        );
    }

    /**
     * Reseñas del producto
     *
     * Retorna un listado paginado de las reseñas asociadas a un producto.
     *
     * @urlParam product integer required ID del producto. Example: 1
     * @queryParam per_page integer Cantidad de resultados por página (máx 50). Example: 15
     */
    public function reviews(Product $product): JsonResponse
    {
        if (!$product->is_available) {
            return $this->error('Este producto no está disponible actualmente.', 404);
        }

        $perPage = min(request()->integer('per_page', 15), 50);
        $reviews = $product->reviews()->with('user')->latest()->paginate($perPage);

        return $this->success(
            FoodReviewResource::collection($reviews)->response()->getData(true),
            'Reseñas del producto.'
        );
    }
}

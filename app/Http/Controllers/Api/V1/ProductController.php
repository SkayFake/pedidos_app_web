<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Productos
 *
 * Endpoints para consultar el catálogo de productos disponibles,
 * con soporte para filtrado, búsqueda y paginación.
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
     * @queryParam category_id integer Filtrar por ID de categoría. Example: 1
     * @queryParam branch_id integer Filtrar por ID de sucursal. Example: 1
     * @queryParam search string Buscar por nombre o descripción. Example: hamburguesa
     * @queryParam recommended boolean Mostrar solo productos recomendados. Example: 1
     * @queryParam popular boolean Mostrar solo productos populares. Example: 1
     * @queryParam per_page integer Cantidad de resultados por página (máx 50). Example: 15
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Hamburguesa Clásica",
     *       "description": "Hamburguesa con carne de res, lechuga, tomate y queso",
     *       "base_price": "5.50",
     *       "base_price_fmt": "$5.50",
     *       "image": "http://pedidosapp.test/storage/products/hamburguesa.jpg",
     *       "is_available": true,
     *       "is_recommended": true,
     *       "is_popular": false,
     *       "category": {
     *         "id": 1,
     *         "name": "Hamburguesas"
     *       }
     *     }
     *   ],
     *   "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
     *   "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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

        // Búsqueda por nombre o descripción
        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por recomendados
        if ($request->boolean('recommended')) {
            $query->where('is_recommended', true);
        }

        // Filtro por populares
        if ($request->boolean('popular')) {
            $query->where('is_popular', true);
        }

        $perPage = min($request->integer('per_page', 15), 50);

        return ProductResource::collection(
            $query->orderBy('name')->paginate($perPage)
        );
    }

    /**
     * Detalle de producto
     *
     * Retorna la información completa de un producto, incluyendo sus variantes
     * y extras disponibles. Retorna 404 si el producto no está disponible.
     *
     * @urlParam product integer required ID del producto. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Detalle del producto.",
     *   "data": {
     *     "id": 1,
     *     "name": "Hamburguesa Clásica",
     *     "description": "Hamburguesa con carne de res, lechuga, tomate y queso",
     *     "base_price": "5.50",
     *     "base_price_fmt": "$5.50",
     *     "image": "http://pedidosapp.test/storage/products/hamburguesa.jpg",
     *     "is_available": true,
     *     "is_recommended": true,
     *     "is_popular": false,
     *     "category": { "id": 1, "name": "Hamburguesas" },
     *     "variants": [
     *       {
     *         "id": 1,
     *         "name": "Tamaño Grande",
     *         "price_modifier": "2.00",
     *         "price_modifier_fmt": "+$2.00",
     *         "is_default": false,
     *         "is_available": true
     *       }
     *     ],
     *     "extras": [
     *       {
     *         "id": 1,
     *         "name": "Queso Extra",
     *         "price": "0.75",
     *         "price_fmt": "$0.75",
     *         "is_available": true
     *       }
     *     ]
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Este producto no está disponible actualmente."
     * }
     */
    public function show(Product $product): JsonResponse
    {
        if (!$product->is_available) {
            return $this->error('Este producto no está disponible actualmente.', 404);
        }

        $product->load([
            'category',
            'variants' => fn ($q) => $q->where('is_available', true),
            'extras'   => fn ($q) => $q->where('is_available', true),
        ]);

        return $this->success(
            new ProductResource($product),
            'Detalle del producto.'
        );
    }
}

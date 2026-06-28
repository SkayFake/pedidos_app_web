<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Categorías
 *
 * Endpoints para consultar las categorías de productos.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Listar categorías
     *
     * Retorna todas las categorías activas con el conteo de productos disponibles en cada una.
     *
     * @queryParam branch_id integer Filtrar el conteo de productos por ID de sucursal. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listado de categorías.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Hamburguesas",
     *       "products_count": 8
     *     },
     *     {
     *       "id": 2,
     *       "name": "Bebidas",
     *       "products_count": 12
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;

        $version = \Illuminate\Support\Facades\Cache::get('categories_cache_version', 1);
        $cacheKey = "api.categories.active.v{$version}.branch_" . ($branchId ?? 'all');

        $categories = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($branchId) {
            $productFilter = function ($q) use ($branchId) {
                $q->where('is_available', true);
                if ($branchId) {
                    $q->where(function ($query) use ($branchId) {
                        $query->where('branch_id', $branchId)
                              ->orWhereNull('branch_id');
                    });
                }
            };

            return Category::query()
                ->where('is_active', true)
                ->whereHas('products', $productFilter)
                ->withCount(['products' => $productFilter])
                ->orderBy('name')
                ->get();
        });

        return $this->success(
            CategoryResource::collection($categories),
            'Listado de categorías.'
        );
    }
}

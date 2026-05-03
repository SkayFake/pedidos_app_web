<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

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
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_available', true)])
            ->orderBy('name')
            ->get();

        return $this->success(
            CategoryResource::collection($categories),
            'Listado de categorías.'
        );
    }
}

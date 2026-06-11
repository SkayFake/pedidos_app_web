<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Sucursales
 *
 * Endpoints para consultar las sucursales disponibles en la aplicación.
 */
class BranchController extends Controller
{
    /**
     * Listar sucursales
     *
     * Retorna todas las sucursales activas en el sistema.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Sucursal Norte",
     *       "address": "Av. Principal #123",
     *       "phone": "555-1234",
     *       "latitude": "13.71",
     *       "longitude": "-89.21",
     *       "is_active": true
     *     }
     *   ]
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $branches = Branch::where('is_active', true)
            ->with(['schedules', 'specialSchedules'])
            ->orderBy('name')
            ->get();

        return BranchResource::collection($branches);
    }
}

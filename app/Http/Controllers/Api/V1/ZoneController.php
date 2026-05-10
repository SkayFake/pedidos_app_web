<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Zonas de Entrega
 *
 * Endpoints públicos para consultar las zonas con cobertura de entrega.
 */
class ZoneController extends Controller
{
    use ApiResponse;

    /**
     * Listar zonas
     *
     * Retorna todas las zonas de entrega activas.
     * Útil para que la aplicación móvil muestre las opciones disponibles
     * al crear o editar una dirección.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Zonas de entrega.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Zona Centro",
     *       "city": "Capital",
     *       "delivery_fee": "2.00",
     *       "is_deliverable": true
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $zones = Zone::where('is_active', true)->orderBy('name')->get();

        return $this->success($zones, 'Zonas de entrega.');
    }
}

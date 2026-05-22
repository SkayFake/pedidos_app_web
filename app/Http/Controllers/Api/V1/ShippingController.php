<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DistanceMatrixService;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    protected $distanceService;

    public function __construct(DistanceMatrixService $distanceService)
    {
        $this->distanceService = $distanceService;
    }

    public function getFee(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        if (!$branch->latitude || !$branch->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'La sucursal seleccionada no tiene coordenadas configuradas.'
            ], 400);
        }

        // Obtener la zona de entrega activa para esta sucursal
        $zone = DB::table('delivery_zones')
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->first();

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'No hay zonas de entrega activas para esta sucursal.'
            ], 400);
        }

        $distanceKm = $this->distanceService->getDistanceInKm(
            $branch->latitude, $branch->longitude,
            $request->lat, $request->lng
        );

        if ($distanceKm === null) {
             return response()->json([
                 'success' => false,
                 'message' => 'No se pudo calcular la distancia con la dirección proporcionada.'
             ], 500);
        }

        $fee = $zone->base_price;
        
        // Solo cobra extra si la distancia excede el radio base
        if ($distanceKm > $zone->base_distance_km) {
            $extraDistance = $distanceKm - $zone->base_distance_km;
            $fee += ($extraDistance * $zone->extra_per_km);
        }

        return response()->json([
            'success' => true,
            'fee' => round($fee, 2),
            'distance_km' => round($distanceKm, 2)
        ]);
    }
}

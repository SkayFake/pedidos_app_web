<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Services\DistanceMatrixService;
use App\Services\ReverseGeocodingService;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    use ApiResponse;

    protected DistanceMatrixService $distanceService;
    protected ReverseGeocodingService $reverseGeoService;

    public function __construct(DistanceMatrixService $distanceService, ReverseGeocodingService $reverseGeoService)
    {
        $this->distanceService = $distanceService;
        $this->reverseGeoService = $reverseGeoService;
    }

    /**
     * Calcular tarifa de envío
     *
     * Calcula la tarifa de envío basada en la distancia entre la sucursal y las coordenadas del cliente.
     */
    public function getFee(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'branch_id' => 'required|exists:branches,id'
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        if (!$branch->latitude || !$branch->longitude) {
            return $this->error('La sucursal seleccionada no tiene coordenadas configuradas.', 400);
        }

        // Obtener la zona de entrega activa para esta sucursal (usando el modelo Zone modificado)
        $zone = Zone::where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->first();

        if (!$zone) {
            return $this->error('No hay zonas de entrega activas para esta sucursal.', 400);
        }

        $distanceKm = $this->distanceService->getDistanceInKm(
            $branch->latitude, $branch->longitude,
            $request->lat, $request->lng
        );

        if ($distanceKm === null) {
            return $this->error('No se pudo calcular la distancia con la dirección proporcionada.', 500);
        }

        $fee = $zone->delivery_fee; // Usamos delivery_fee como tarifa base
        $isOutOfZone = false;
        
        // Verifica si la distancia excede el radio base
        if ($distanceKm > $zone->base_distance_km) {
            $isOutOfZone = true;
            
            // Si no se permiten entregas fuera de zona, rechazar
            if (!$zone->allow_out_of_zone_delivery) {
                return $this->error('El restaurante no tiene cobertura para esta zona.', 400, [
                    'is_out_of_zone' => true
                ]);
            }
            
            // Calcular el cobro extra por los km adicionales
            $extraDistance = $distanceKm - $zone->base_distance_km;
            $fee += ($extraDistance * $zone->extra_per_km);
        }

        $message = $isOutOfZone 
            ? 'Estás fuera de nuestra zona de cobertura principal. El costo de envío se ha calculado por distancia.' 
            : 'Tarifa de envío calculada.';

        return $this->success([
            'fee' => round($fee, 2),
            'distance_km' => round($distanceKm, 2),
            'is_out_of_zone' => $isOutOfZone
        ], $message);
    }

    /**
     * Verificar cobertura
     *
     * Verifica si una ubicación tiene cobertura de entrega usando geocodificación inversa.
     * Traduce las coordenadas a un nombre de municipio y lo compara con las zonas registradas.
     *
     * @queryParam lat numeric required Latitud. Example: 13.6929
     * @queryParam lng numeric required Longitud. Example: -89.2182
     */
    public function checkCoverage(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $municipality = $this->reverseGeoService->getMunicipalityFromCoords(
            $request->lat,
            $request->lng
        );

        if (!$municipality) {
            return $this->error('No se pudo determinar tu ubicación. Intenta de nuevo.', 422, [
                'has_coverage' => false,
            ]);
        }

        $escapedMunicipality = str_replace(['%', '_'], ['\%', '\_'], $municipality);
        $zone = Zone::where('is_active', true)
            ->where('is_deliverable', true)
            ->where('name', 'LIKE', "%{$escapedMunicipality}%")
            ->first();

        if (!$zone) {
            return $this->success([
                'has_coverage' => false,
                'municipality' => $municipality,
            ], "No tenemos cobertura en {$municipality} actualmente.");
        }

        return $this->success([
            'has_coverage' => true,
            'municipality' => $municipality,
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'delivery_fee' => $zone->delivery_fee,
            ],
        ], "¡Tenemos cobertura en {$municipality}!");
    }
}

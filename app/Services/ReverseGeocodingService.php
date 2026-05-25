<?php

namespace App\Services;

use App\Models\Zone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Geocodificación Inversa.
 * Traduce coordenadas GPS a nombres de municipios usando Google Maps API.
 */
class ReverseGeocodingService
{
    /**
     * Obtiene el nombre del municipio/ciudad a partir de coordenadas GPS.
     */
    public function getMunicipalityFromCoords(float $lat, float $lng): ?string
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        if (!$apiKey) {
            Log::warning('ReverseGeocoding: No hay API key de Google Maps configurada.');
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $apiKey,
                'language' => 'es',
                'result_type' => 'locality|administrative_area_level_2',
            ]);

            if (!$response->successful()) {
                Log::error('ReverseGeocoding: Error en la respuesta de Google', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                return null;
            }

            // Buscar el componente de tipo locality (ciudad/municipio)
            foreach ($data['results'] as $result) {
                foreach ($result['address_components'] as $component) {
                    if (in_array('locality', $component['types']) || in_array('administrative_area_level_2', $component['types'])) {
                        return $component['long_name'];
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('ReverseGeocoding: Excepción', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Busca una zona de cobertura que coincida con el municipio detectado.
     */
    public function findZoneByCoords(float $lat, float $lng): ?Zone
    {
        $municipality = $this->getMunicipalityFromCoords($lat, $lng);

        if (!$municipality) {
            return null;
        }

        return Zone::where('is_active', true)
            ->where('is_deliverable', true)
            ->where('name', 'LIKE', "%{$municipality}%")
            ->first();
    }
}

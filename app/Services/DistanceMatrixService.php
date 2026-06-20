<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceMatrixService
{
    /**
     * Calculates driving distance using Google Distance Matrix API.
     * Returns distance in kilometers.
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $destLat
     * @param float $destLng
     * @return float|null
     */
    public function getDistanceInKm($originLat, $originLng, $destLat, $destLng)
    {
        $apiKey = config('services.google_maps.key');

        if (!$apiKey) {
            Log::warning('Google Maps API key not configured. Using direct distance fallback.');
            return $this->getStraightLineDistance($originLat, $originLng, $destLat, $destLng);
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$originLat},{$originLng}",
                'destinations' => "{$destLat},{$destLng}",
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'OK' && !empty($data['rows'][0]['elements'][0])) {
                    $element = $data['rows'][0]['elements'][0];
                    if ($element['status'] === 'OK') {
                        // distance in meters
                        $meters = $element['distance']['value'];
                        return $meters / 1000.0;
                    }
                }
            }
            
            Log::error('Distance Matrix API failed or returned NO_RESULTS', ['response' => $response->json() ?? null]);
        } catch (\Exception $e) {
            Log::error('Distance Matrix Exception: ' . $e->getMessage());
        }

        // Fallback to Haversine straight line if API fails
        return $this->getStraightLineDistance($originLat, $originLng, $destLat, $destLng);
    }

    /**
     * Fallback Haversine straight-line distance in km
     */
    private function getStraightLineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

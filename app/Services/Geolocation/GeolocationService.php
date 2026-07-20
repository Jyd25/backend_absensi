<?php

namespace App\Services\Geolocation;

use App\Models\AttendanceLocation;
use App\Traits\ApiResponse;

class GeolocationService
{
    use ApiResponse;

    public const EARTH_RADIUS_KM = 6371;
    public const KM_TO_METER = 1000;

    public function validateLocation(float $latitude, float $longitude, ?int $locationId = null): array
    {
        $locations = AttendanceLocation::where('is_active', true);

        if ($locationId) {
            $locations->where('id', $locationId);
        }

        $locations = $locations->get();

        if ($locations->isEmpty()) {
            return [
                'inside_radius' => false,
                'message' => 'Tidak ada lokasi kehadiran yang aktif',
                'distance' => null,
                'location_name' => null,
            ];
        }

        $bestMatch = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                (float) $location->latitude, (float) $location->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $location;
            }
        }

        if (!$bestMatch) {
            return [
                'inside_radius' => false,
                'message' => 'Tidak ada lokasi yang cocok',
                'distance' => null,
                'location_name' => null,
            ];
        }

        $insideRadius = $minDistance <= (float) $bestMatch->radius;

        return [
            'inside_radius' => $insideRadius,
            'message' => $insideRadius
                ? 'Anda berada dalam radius lokasi kehadiran'
                : 'Anda berada di luar radius lokasi kehadiran',
            'distance' => round($minDistance, 2),
            'radius' => (float) $bestMatch->radius,
            'location_id' => $bestMatch->id,
            'location_name' => $bestMatch->location_name,
            'latitude' => (float) $bestMatch->latitude,
            'longitude' => (float) $bestMatch->longitude,
        ];
    }

    public function calculateDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2 +
             cos($lat1) * cos($lat2) *
             sin($dLng / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return $c * self::EARTH_RADIUS_KM * self::KM_TO_METER;
    }
}

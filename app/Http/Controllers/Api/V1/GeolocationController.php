<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Geolocation\GeolocationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeolocationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GeolocationService $geolocationService
    ) {}

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_id' => 'sometimes|exists:attendance_locations,id',
        ]);

        $result = $this->geolocationService->validateLocation(
            (float) $request->latitude,
            (float) $request->longitude,
            $request->location_id
        );

        return $this->successResponse($result, $result['message']);
    }
}

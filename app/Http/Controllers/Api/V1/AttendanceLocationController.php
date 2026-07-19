<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Http\Resources\AttendanceLocationResource;
use App\Models\AttendanceLocation;
use App\Services\AttendanceLocationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLocationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AttendanceLocationService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceLocation::class);

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        if ($search) {
            $locations = $this->service->search($search)->paginate($perPage);
        } else {
            $locations = $this->service->paginate($perPage);
        }

        return $this->paginatedResponse(AttendanceLocationResource::collection($locations));
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $this->authorize('create', AttendanceLocation::class);

        $location = $this->service->create($request->validated());

        return $this->successResponse(
            new AttendanceLocationResource($location),
            'Attendance location created successfully',
            201
        );
    }

    public function show(AttendanceLocation $attendanceLocation): JsonResponse
    {
        $this->authorize('view', $attendanceLocation);

        return $this->successResponse(
            new AttendanceLocationResource($attendanceLocation)
        );
    }

    public function update(UpdateLocationRequest $request, AttendanceLocation $attendanceLocation): JsonResponse
    {
        $this->authorize('update', $attendanceLocation);

        $attendanceLocation = $this->service->update($attendanceLocation->id, $request->validated());

        return $this->successResponse(
            new AttendanceLocationResource($attendanceLocation),
            'Attendance location updated successfully'
        );
    }

    public function destroy(AttendanceLocation $attendanceLocation): JsonResponse
    {
        $this->authorize('delete', $attendanceLocation);

        $this->service->delete($attendanceLocation->id);

        return $this->successResponse(null, 'Attendance location deleted successfully');
    }
}

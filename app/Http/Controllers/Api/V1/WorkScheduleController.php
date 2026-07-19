<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkSchedule\StoreWorkScheduleRequest;
use App\Http\Requests\WorkSchedule\UpdateWorkScheduleRequest;
use App\Http\Resources\WorkScheduleResource;
use App\Models\WorkSchedule;
use App\Services\WorkScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WorkScheduleService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkSchedule::class);

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        if ($search) {
            $schedules = $this->service->search($search)->paginate($perPage);
        } else {
            $schedules = $this->service->paginate($perPage);
        }

        return $this->paginatedResponse(WorkScheduleResource::collection($schedules));
    }

    public function store(StoreWorkScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', WorkSchedule::class);

        $schedule = $this->service->create($request->validated());

        return $this->successResponse(
            new WorkScheduleResource($schedule),
            'Work schedule created successfully',
            201
        );
    }

    public function show(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('view', $workSchedule);

        return $this->successResponse(
            new WorkScheduleResource($workSchedule)
        );
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('update', $workSchedule);

        $workSchedule = $this->service->update($workSchedule->id, $request->validated());

        return $this->successResponse(
            new WorkScheduleResource($workSchedule),
            'Work schedule updated successfully'
        );
    }

    public function destroy(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('delete', $workSchedule);

        $this->service->delete($workSchedule->id);

        return $this->successResponse(null, 'Work schedule deleted successfully');
    }
}

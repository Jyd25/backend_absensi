<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse;

    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['employee', 'location', 'schedule']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->has('date')) {
            $query->whereDate('check_in_time', $request->date);
        }

        if ($request->has('status')) {
            $query->where('attendance_status', $request->status);
        }

        $attendances = $query->latest('check_in_time')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse(AttendanceResource::collection($attendances));
    }

    public function store(CheckInRequest $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);

        $attendance = $this->attendanceService->checkIn(
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Check-in successful.',
            201
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $user = $request->user();
        $employeeId = $user->employee_id;

        if (!$employeeId) {
            return $this->errorResponse('No employee profile found.', 404);
        }

        $attendance = $this->attendanceService->getTodayByEmployee($employeeId);

        if (!$attendance) {
            return $this->errorResponse('No check-in record found for today.', 404);
        }

        $this->authorize('update', $attendance);

        $attendance = $this->attendanceService->checkOut(
            $attendance->id,
            $user
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Check-out successful.'
        );
    }

    public function show(Attendance $attendance): JsonResponse
    {
        $this->authorize('view', $attendance);

        $attendance->load(['employee', 'location', 'schedule']);

        return $this->successResponse(
            new AttendanceResource($attendance)
        );
    }

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $employeeId = $user->employee_id;

        if (!$employeeId) {
            return $this->errorResponse('No employee profile found.', 404);
        }

        $attendance = $this->attendanceService->getTodayByEmployee($employeeId);

        return $this->successResponse(
            $attendance ? new AttendanceResource($attendance->load(['employee', 'location', 'schedule'])) : null,
            $attendance ? 'Today\'s attendance retrieved.' : 'No attendance today.'
        );
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $employeeId = $user->employee_id;

        if (!$employeeId) {
            return $this->errorResponse('No employee profile found.', 404);
        }

        $attendances = $this->attendanceService->getHistory($employeeId, $request);

        return $this->paginatedResponse(AttendanceResource::collection($attendances));
    }
}

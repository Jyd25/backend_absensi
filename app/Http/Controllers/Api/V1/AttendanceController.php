<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse, SendsNotifications;

    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = $user->role?->name;

        $query = Attendance::with(['employee', 'location', 'schedule']);

        if (in_array($roleName, ['Guru', 'Karyawan'])) {
            $query->where('employee_id', $user->employee_id);
        } else {
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('department_id')) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }
        }

        if ($request->has('date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('check_in_time', $request->date)
                    ->orWhereDate('check_out_time', $request->date);
            });
        } elseif ($request->has('month') && $request->has('year')) {
            $month = (int) $request->month;
            $year = (int) $request->year;
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('check_in_time', [$start, $end])
                    ->orWhereBetween('check_out_time', [$start, $end]);
            });
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
        $user = $request->user();
        $employeeId = $user->employee_id;

        if ($employeeId) {
            $existingToday = $this->attendanceService->getTodayByEmployee($employeeId);
            if ($existingToday) {
                return $this->errorResponse('Anda sudah melakukan presensi hari ini.', 422);
            }
        }

        $result = $this->attendanceService->checkIn(
            $request->validated(),
            $user
        );

        if (is_array($result) && isset($result['success']) && !$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        $attendance = $result;

        $employee = $attendance->employee;
        $employeeName = $employee?->name ?? 'Karyawan';
        $time = $attendance->check_in_time
            ? Carbon::parse($attendance->check_in_time)->format('H:i')
            : Carbon::parse($attendance->check_out_time)->format('H:i');
        $statusLabel = $attendance->attendance_status?->label() ?? '-';
        $locStatus = $attendance->location_status?->label() ?? '-';
        $faceStatus = $attendance->face_status?->label() ?? '-';

        $isLate = $attendance->attendance_status?->value === 'late' && !$attendance->check_in_time;

        $this->notifyAdmins(
            $isLate ? 'Presensi Terlambat' : 'Check-In Baru',
            $isLate
                ? "{$employeeName} presensi terlambat pukul {$time}. Check-in kosong — menunggu disetujui admin. Status: {$statusLabel} | Lokasi: {$locStatus}"
                : "{$employeeName} telah check-in pukul {$time}. Status: {$statusLabel} | Lokasi: {$locStatus} | Wajah: {$faceStatus}",
            $isLate ? 'warning' : ($attendance->attendance_status?->value === 'late' ? 'warning' : 'success'),
            ['employee_id' => $employee?->id, 'attendance_id' => $attendance->id]
        );

        $this->notifyUser(
            $request->user()->id,
            $isLate ? 'Presensi Terlambat' : 'Check-In Berhasil',
            $isLate
                ? 'Presensi terlambat Anda pukul ' . $time . ' telah tercatat. Check-in kosong, menunggu penyesuaian oleh admin. Status: ' . $statusLabel
                : 'Check-in Anda pukul ' . $time . ' telah tercatat. Status: ' . $statusLabel,
            $isLate ? 'warning' : 'success',
            ['attendance_id' => $attendance->id]
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            $isLate ? 'Presensi terlambat berhasil. Check-in kosong, menunggu penyesuaian admin.' : 'Check-in successful.',
            201
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $user = $request->user();
        $employeeId = $user->employee_id;

        if (!$employeeId) {
            return $this->errorResponse('Profil karyawan tidak ditemukan.', 404);
        }

        $attendance = $this->attendanceService->getTodayByEmployee($employeeId);

        if (!$attendance) {
            return $this->errorResponse('Belum ada data check-in hari ini.', 404);
        }

        $attendance = $this->attendanceService->checkOut(
            $attendance->id,
            $user,
            $request->validated()
        );

        $employee = $attendance->employee;
        $employeeName = $employee?->name ?? 'Karyawan';
        $time = Carbon::parse($attendance->check_out_time)->format('H:i');
        $workDuration = $attendance->work_duration ?? '-';

        $this->notifyAdmins(
            'Check-Out Selesai',
            "{$employeeName} telah check-out pukul {$time}. Durasi kerja: {$workDuration}",
            'info',
            ['employee_id' => $employee?->id, 'attendance_id' => $attendance->id]
        );

        $this->notifyUser(
            $user->id,
            'Check-Out Berhasil',
            'Check-out Anda pukul ' . $time . ' telah tercatat. Durasi kerja: ' . $workDuration,
            'success',
            ['attendance_id' => $attendance->id]
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Check-out successful.'
        );
    }

    public function show(Request $request, Attendance $attendance): JsonResponse
    {
        $user = $request->user();
        $roleName = $user->role?->name;

        if (in_array($roleName, ['Guru', 'Karyawan']) && $attendance->employee_id !== $user->employee_id) {
            return $this->errorResponse('Tidak memiliki akses.', 403);
        }

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

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

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
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
        $now = Carbon::now('Asia/Jakarta');
        $hour = $now->hour;
        $minute = $now->hour * 60 + $now->minute;

        if ($minute < 3 * 60) {
            return $this->errorResponse('Belum waktu presensi. Waktu check-in dimulai pukul 03:00 WIB.', 422);
        }

        if ($minute >= 10 * 60) {
            return $this->errorResponse('Batas waktu check-in telah berakhir (09:59 WIB). Silakan lakukan check-out.', 422);
        }

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
        $time = Carbon::parse($attendance->check_in_time)->format('H:i');
        $statusLabel = $attendance->attendance_status?->label() ?? '-';
        $locStatus = $attendance->location_status?->label() ?? '-';
        $faceStatus = $attendance->face_status?->label() ?? '-';

        $this->notifyAdmins(
            'Check-In Baru',
            "{$employeeName} telah check-in pukul {$time}. Status: {$statusLabel} | Lokasi: {$locStatus} | Wajah: {$faceStatus}",
            $attendance->attendance_status?->value === 'late' ? 'warning' : 'success',
            ['employee_id' => $employee?->id, 'attendance_id' => $attendance->id]
        );

        $this->notifyUser(
            $request->user()->id,
            'Check-In Berhasil',
            'Check-in Anda pukul ' . $time . ' telah tercatat. Status: ' . $statusLabel,
            'success',
            ['attendance_id' => $attendance->id]
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
            return $this->errorResponse('Profil karyawan tidak ditemukan.', 404);
        }

        $attendance = $this->attendanceService->getTodayByEmployee($employeeId);
        $attendanceId = $attendance?->id;

        $attendance = $this->attendanceService->checkOut(
            $attendanceId,
            $user,
            $request->validated()
        );

        $employee = $attendance->employee;
        $employeeName = $employee?->name ?? 'Karyawan';
        $time = Carbon::parse($attendance->check_out_time)->format('H:i');
        $workDuration = $attendance->work_duration ?? '-';
        $isLate = !$attendance->check_in_time;

        $this->notifyAdmins(
            $isLate ? 'Presensi Terlambat' : 'Check-Out Selesai',
            $isLate
                ? "{$employeeName} presensi terlambat pukul {$time}. Check-in kosong — menunggu disetujui admin."
                : "{$employeeName} telah check-out pukul {$time}. Durasi kerja: {$workDuration}",
            $isLate ? 'warning' : 'info',
            ['employee_id' => $employee?->id, 'attendance_id' => $attendance->id]
        );

        $this->notifyUser(
            $user->id,
            $isLate ? 'Presensi Terlambat' : 'Check-Out Berhasil',
            $isLate
                ? 'Presensi terlambat Anda pukul ' . $time . ' telah tercatat. Check-in kosong, menunggu penyesuaian oleh admin.'
                : 'Check-out Anda pukul ' . $time . ' telah tercatat. Durasi kerja: ' . $workDuration,
            $isLate ? 'warning' : 'success',
            ['attendance_id' => $attendance->id]
        );

        return $this->successResponse(
            new AttendanceResource($attendance),
            $isLate ? 'Presensi terlambat berhasil.' : 'Check-out successful.'
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

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $user = $request->user();
        $roleName = $user->role?->name;

        if ($roleName !== 'Administrator') {
            return $this->errorResponse('Tidak memiliki akses.', 403);
        }

        $validated = $request->validate([
            'check_in_time' => 'nullable|date_format:Y-m-d\TH:i',
            'check_out_time' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $attendance->update($validated);
        $attendance->load(['employee', 'location', 'schedule']);

        return $this->successResponse(
            new AttendanceResource($attendance),
            'Data kehadiran berhasil diperbarui.'
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

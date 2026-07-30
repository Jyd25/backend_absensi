<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    use ApiResponse, SendsNotifications;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AttendanceCorrection::with(['employee', 'approver', 'attendance']);

        if (in_array($user->role?->name, ['Guru', 'Karyawan'])) {
            $query->where('employee_id', $user->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $corrections = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($corrections);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string|min:10',
        ]);

        $user = $request->user();
        if (!$user->employee_id) {
            return $this->errorResponse('Akun anda tidak terkait data karyawan', 422);
        }

        $attendance = Attendance::where('employee_id', $user->employee_id)
            ->whereDate('check_in_time', $request->date)
            ->first();

        $correction = AttendanceCorrection::create([
            'employee_id' => $user->employee_id,
            'attendance_id' => $attendance?->id,
            'date' => $request->date,
            'check_in_time' => $request->check_in_time,
            'check_out_time' => $request->check_out_time,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        $this->notifyAdmins(
            'Perbaikan Kehadiran Baru',
            "{$user->name} mengajukan perbaikan kehadiran untuk tanggal {$request->date}",
            'info',
            ['correction_id' => $correction->id, 'employee_id' => $user->employee_id, 'action' => 'create']
        );

        return $this->successResponse($correction, 'Pengajuan perbaikan berhasil dikirim', 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        if ($admin->role?->name !== 'Administrator') {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $correction = AttendanceCorrection::findOrFail($id);
        $request->validate([
            'admin_note' => 'nullable|string',
            'check_in_time' => 'nullable|string',
            'check_out_time' => 'nullable|string',
        ]);

        $checkInTime = $request->check_in_time ?? $correction->check_in_time;
        $checkOutTime = $request->check_out_time ?? $correction->check_out_time;
        $dateStr = $correction->date instanceof Carbon ? $correction->date->format('Y-m-d') : $correction->date;

        $normalizeTime = function ($t) {
            if (!$t) return null;
            $parts = explode(':', $t);
            return $parts[0] . ':' . $parts[1];
        };

        $checkInTime = $normalizeTime($checkInTime);
        $checkOutTime = $normalizeTime($checkOutTime);

        $employee = Employee::with('schedule')->find($correction->employee_id);

        $attendance = null;
        if ($correction->attendance_id) {
            $attendance = Attendance::find($correction->attendance_id);
        }
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $correction->employee_id)
                ->whereDate('check_in_time', $dateStr)
                ->first();
        }

        if ($attendance) {
            $updateData = [];
            if ($checkInTime) {
                $updateData['check_in_time'] = $dateStr . ' ' . $checkInTime . ':00';
            }
            if ($checkOutTime) {
                $updateData['check_out_time'] = $dateStr . ' ' . $checkOutTime . ':00';
            }
            if (!empty($updateData)) {
                $attendance->update($updateData);
                $attendance->refresh();
                $this->recalculateAttendanceStatus($attendance, $employee?->schedule);
            }
        } else {
            $createData = [
                'employee_id' => $correction->employee_id,
                'attendance_type' => 'check_in',
                'attendance_status' => 'present',
                'remarks' => 'Approved correction by admin',
            ];
            if ($checkInTime) {
                $createData['check_in_time'] = $dateStr . ' ' . $checkInTime . ':00';
            }
            if ($checkOutTime) {
                $createData['check_out_time'] = $dateStr . ' ' . $checkOutTime . ':00';
            }
            if ($checkInTime || $checkOutTime) {
                $attendance = Attendance::create($createData);
                $this->recalculateAttendanceStatus($attendance, $employee?->schedule);
            }
        }

        $correction->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'admin_note' => $request->admin_note,
        ]);

        if ($employee) {
            $user = $employee->user ?? User::where('employee_id', $employee->id)->first();
            if ($user) {
                $this->notifyUser(
                    $user->id,
                    'Perbaikan Disetujui',
                    "Perbaikan kehadiran tanggal {$correction->date} telah disetujui.",
                    'success',
                    ['correction_id' => $correction->id, 'action' => 'approved']
                );
            }
        }

        return $this->successResponse($correction->fresh(['employee', 'approver']), 'Perbaikan disetujui');
    }

    private function recalculateAttendanceStatus(Attendance $attendance, $schedule): void
    {
        $checkInTime = Carbon::parse($attendance->check_in_time);
        $checkOutTime = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;
        $isSaturday = $checkInTime->isSaturday();

        $scheduleStart = null;
        $scheduleEnd = null;
        $tolerance = 0;

        if ($schedule) {
            $tolerance = $schedule->tolerance_minutes ?? 0;
            if ($isSaturday && $schedule->saturday_start_time) {
                $scheduleStart = $schedule->saturday_start_time instanceof Carbon ? $schedule->saturday_start_time : Carbon::parse($schedule->saturday_start_time);
                $scheduleEnd = $schedule->saturday_end_time instanceof Carbon ? $schedule->saturday_end_time : Carbon::parse($schedule->saturday_end_time);
            } else {
                $scheduleStart = $schedule->start_time instanceof Carbon ? $schedule->start_time : Carbon::parse($schedule->start_time);
                $scheduleEnd = $schedule->end_time instanceof Carbon ? $schedule->end_time : Carbon::parse($schedule->end_time);
            }
        }

        if ($scheduleStart) {
            $lateThreshold = $scheduleStart->copy()->addMinutes($tolerance);
            $checkInMin = $checkInTime->copy()->setHour($scheduleStart->hour)->setMinute($scheduleStart->minute);
            $attendance->attendance_status = $checkInTime->gt($lateThreshold) ? 'late' : 'present';
            $attendance->save();
        }

        if ($checkOutTime && $scheduleEnd) {
            $scheduleEndCarbon = $checkOutTime->copy()->setHour($scheduleEnd->hour)->setMinute($scheduleEnd->minute);
            $attendance->status_checkout = $checkOutTime->lt($scheduleEndCarbon) ? 'Pulang Cepat' : 'Pulang Tepat Waktu';
            $attendance->save();
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role?->name !== 'Administrator') {
            return $this->errorResponse('Akses ditolak', 403);
        }

        $correction = AttendanceCorrection::findOrFail($id);
        $request->validate([
            'admin_note' => 'required|string',
        ]);

        $correction->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'admin_note' => $request->admin_note,
        ]);

        $employee = $correction->employee;
        if ($employee) {
            $user = $employee->user ?? User::where('employee_id', $employee->id)->first();
            if ($user) {
                $this->notifyUser(
                    $user->id,
                    'Perbaikan Ditolak',
                    "Perbaikan kehadiran tanggal {$correction->date} ditolak. Alasan: {$request->admin_note}",
                    'warning',
                    ['correction_id' => $correction->id, 'action' => 'rejected']
                );
            }
        }

        return $this->successResponse($correction->fresh(['employee', 'approver']), 'Perbaikan ditolak');
    }
}

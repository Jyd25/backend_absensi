<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\SendsNotifications;
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
            'Perbaikan Absensi Baru',
            "{$user->name} mengajukan perbaikan absensi untuk tanggal {$request->date}",
            'info',
            ['correction_id' => $correction->id, 'employee_id' => $user->employee_id, 'action' => 'create']
        );

        return $this->successResponse($correction, 'Pengajuan perbaikan berhasil dikirim', 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $correction = AttendanceCorrection::findOrFail($id);
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        if ($correction->attendance_id && $correction->check_in_time) {
            $attendance = Attendance::find($correction->attendance_id);
            if ($attendance) {
                $checkIn = $correction->date . ' ' . $correction->check_in_time . ':00';
                $checkOut = $correction->check_out_time ? $correction->date . ' ' . $correction->check_out_time . ':00' : $attendance->check_out_time;
                $attendance->update([
                    'check_in_time' => $checkIn,
                    'check_out_time' => $checkOut,
                ]);
            }
        } elseif ($correction->check_in_time && $correction->check_out_time) {
            $user = $request->user();
            $employeeId = $correction->employee_id;
            $checkIn = $correction->date . ' ' . $correction->check_in_time . ':00';
            $checkOut = $correction->date . ' ' . $correction->check_out_time . ':00';
            $status = 'present';

            Attendance::create([
                'employee_id' => $employeeId,
                'attendance_type' => 'check_in',
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'attendance_status' => $status,
                'remarks' => 'Approved correction by admin',
            ]);
        }

        $correction->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'admin_note' => $request->admin_note,
        ]);

        $employee = $correction->employee;
        if ($employee) {
            $user = $employee->user ?? User::where('employee_id', $employee->id)->first();
            if ($user) {
                $this->notifyUser(
                    $user->id,
                    'Perbaikan Disetujui',
                    "Perbaikan absensi tanggal {$correction->date} telah disetujui.",
                    'success',
                    ['correction_id' => $correction->id, 'action' => 'approved']
                );
            }
        }

        return $this->successResponse($correction->fresh(['employee', 'approver']), 'Perbaikan disetujui');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
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
                    "Perbaikan absensi tanggal {$correction->date} ditolak. Alasan: {$request->admin_note}",
                    'warning',
                    ['correction_id' => $correction->id, 'action' => 'rejected']
                );
            }
        }

        return $this->successResponse($correction->fresh(['employee', 'approver']), 'Perbaikan ditolak');
    }
}

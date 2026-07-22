<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $roleName = $user->role?->name;
        $isAdminOrPimpinan = in_array($roleName, ['Administrator', 'Pimpinan']);

        $query = Employee::active();

        if (!$isAdminOrPimpinan) {
            $query->where('id', $user->employee_id);
        }

        $totalEmployees = $query->count();

        $attendanceQuery = DB::table('attendances')
            ->whereDate('check_in_time', $today);

        if (!$isAdminOrPimpinan) {
            $attendanceQuery->where('employee_id', $user->employee_id);
        }

        $todayPresent = (clone $attendanceQuery)
            ->where('attendance_status', AttendanceStatus::Present->value)
            ->count();

        $todayLate = (clone $attendanceQuery)
            ->where('attendance_status', AttendanceStatus::Late->value)
            ->count();

        $todayPermission = (clone $attendanceQuery)
            ->where('attendance_status', AttendanceStatus::Permission->value)
            ->count();

        $todayLeave = (clone $attendanceQuery)
            ->where('attendance_status', AttendanceStatus::Leave->value)
            ->count();

        $todaySick = (clone $attendanceQuery)
            ->where('attendance_status', AttendanceStatus::Sick->value)
            ->count();

        $todayAbsent = $totalEmployees - $todayPresent - $todayLate - $todayPermission - $todayLeave - $todaySick;
        if ($todayAbsent < 0) {
            $todayAbsent = 0;
        }

        $result = [
            'total_employees' => $totalEmployees,
            'today_present' => $todayPresent,
            'today_late' => $todayLate,
            'today_absent' => $todayAbsent,
            'today_leave' => $todayLeave,
            'today_permission' => $todayPermission,
            'today_sick' => $todaySick,
        ];

        // Pimpinan / Admin: attendance detail list
        if (in_array($roleName, ['Administrator', 'Pimpinan'])) {
            $todayAttendances = DB::table('attendances')
                ->whereDate('check_in_time', $today)
                ->get();

            $attendedIds = $todayAttendances->pluck('employee_id')->toArray();

            $attended = Employee::with('user')
                ->whereIn('id', $attendedIds)
                ->get()
                ->map(function ($emp) use ($todayAttendances) {
                    $att = $todayAttendances->first(fn($a) => $a->employee_id === $emp->id);
                    return [
                        'employee_id' => $emp->id,
                        'name' => $emp->user->name ?? $emp->nik,
                        'nik' => $emp->nik,
                        'department' => $emp->department->name ?? '-',
                        'status' => $att->attendance_status,
                        'check_in_time' => $att->check_in_time,
                        'check_out_time' => $att->check_out_time,
                        'late_minutes' => $att->attendance_status === AttendanceStatus::Late->value
                            ? $this->calculateLateMinutes($att->check_in_time, $emp->schedule_id)
                            : 0,
                    ];
                })
                ->sortBy('name')
                ->values();

            $absentEmployees = Employee::with('user')
                ->active()
                ->whereNotIn('id', $attendedIds)
                ->get()
                ->map(function ($emp) {
                    return [
                        'employee_id' => $emp->id,
                        'name' => $emp->user->name ?? $emp->nik,
                        'nik' => $emp->nik,
                        'department' => $emp->department->name ?? '-',
                        'status' => 'absent',
                    ];
                })
                ->sortBy('name')
                ->values();

            $result['today_attendance_list'] = $attended->values();
            $result['today_absent_list'] = $absentEmployees->values();
        }

        // Guru/Karyawan: own attendance detail
        if (in_array($roleName, ['Guru', 'Karyawan']) && $user->employee_id) {
            $myAttendance = DB::table('attendances')
                ->where('employee_id', $user->employee_id)
                ->whereDate('check_in_time', $today)
                ->first();

            $employee = Employee::with('schedule', 'department')->find($user->employee_id);

            $now = Carbon::now('Asia/Jakarta');
            $scheduleStart = null;
            $scheduleEnd = null;
            $isSaturday = $now->isSaturday();

            if ($employee && $employee->schedule) {
                if ($isSaturday && $employee->schedule->saturday_start_time) {
                    $scheduleStart = $employee->schedule->saturday_start_time;
                    $scheduleEnd = $employee->schedule->saturday_end_time;
                } else {
                    $scheduleStart = $employee->schedule->start_time;
                    $scheduleEnd = $employee->schedule->end_time;
                }
            }

            $result['my_attendance'] = $myAttendance ? [
                'status' => $myAttendance->attendance_status,
                'check_in_time' => $myAttendance->check_in_time,
                'check_out_time' => $myAttendance->check_out_time,
                'location_status' => $myAttendance->location_status,
                'face_status' => $myAttendance->face_status,
            ] : null;

            $result['schedule'] = [
                'start_time' => $scheduleStart,
                'end_time' => $scheduleEnd,
                'check_in_deadline' => '09:00',
                'check_out_deadline' => '20:00',
            ];

            $result['current_time'] = $now->format('H:i:s');
            $result['current_date'] = $now->format('Y-m-d');
            $result['day_name'] = $now->translatedFormat('l');
        }

        return $this->successResponse($result);
    }

    public function weekly(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdminOrPimpinan = in_array($user->role?->name, ['Administrator', 'Pimpinan']);
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $days = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayQuery = DB::table('attendances')
                ->whereDate('check_in_time', $date);

            if (!$isAdminOrPimpinan) {
                $dayQuery->where('employee_id', $user->employee_id);
            }

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('l'),
                'present' => (clone $dayQuery)->where('attendance_status', AttendanceStatus::Present->value)->count(),
                'late' => (clone $dayQuery)->where('attendance_status', AttendanceStatus::Late->value)->count(),
                'absent' => (clone $dayQuery)->where('attendance_status', AttendanceStatus::Absent->value)->count(),
            ];
        }

        return $this->successResponse($days);
    }

    public function monthly(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdminOrPimpinan = in_array($user->role?->name, ['Administrator', 'Pimpinan']);
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendanceQuery = DB::table('attendances')
            ->whereBetween('check_in_time', [$startDate, $endDate]);

        if (!$isAdminOrPimpinan) {
            $attendanceQuery->where('employee_id', $user->employee_id);
        }

        $present = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Present->value)->count();
        $late = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Late->value)->count();
        $permission = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Permission->value)->count();
        $leave = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Leave->value)->count();
        $sick = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Sick->value)->count();
        $absent = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Absent->value)->count();

        $daysInMonth = $startDate->daysInMonth;
        $totalEmployees = $isAdminOrPimpinan ? Employee::active()->count() : 1;

        $totalExpected = $totalEmployees * $daysInMonth;
        $totalActual = $present + $late;

        return $this->successResponse([
            'year' => (int) $year,
            'month' => (int) $month,
            'days_in_month' => $daysInMonth,
            'total_employees' => $totalEmployees,
            'total_expected' => $totalExpected,
            'total_actual' => $totalActual,
            'present' => $present,
            'late' => $late,
            'permission' => $permission,
            'leave' => $leave,
            'sick' => $sick,
            'absent' => $absent,
            'attendance_rate' => $totalExpected > 0 ? round(($totalActual / $totalExpected) * 100, 2) : 0,
        ]);
    }

    private function calculateLateMinutes(string $checkInTime, ?int $scheduleId): int
    {
        if (!$scheduleId) return 0;

        $schedule = WorkSchedule::find($scheduleId);
        if (!$schedule) return 0;

        $checkIn = Carbon::parse($checkInTime);
        $isSaturday = $checkIn->isSaturday();

        if ($isSaturday && $schedule->saturday_start_time) {
            $scheduleStart = Carbon::parse($schedule->saturday_start_time);
        } else {
            $scheduleStart = Carbon::parse($schedule->start_time);
        }

        $tolerance = $schedule->tolerance_minutes ?? 0;
        $lateThreshold = $scheduleStart->copy()->addMinutes($tolerance);

        if ($checkIn->gt($lateThreshold)) {
            return max(0, $scheduleStart->diffInMinutes($checkIn) - $tolerance);
        }

        return 0;
    }
}

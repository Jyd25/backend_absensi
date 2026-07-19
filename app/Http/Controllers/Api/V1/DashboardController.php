<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
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

        $query = Employee::active();

        if (!$user->isAdmin) {
            $query->where('id', $user->employee_id);
        }

        $totalEmployees = $query->count();

        $attendanceQuery = DB::table('attendances')
            ->whereDate('check_in_time', $today);

        if (!$user->isAdmin) {
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

        return $this->successResponse([
            'total_employees' => $totalEmployees,
            'today_present' => $todayPresent,
            'today_late' => $todayLate,
            'today_absent' => $todayAbsent,
            'today_leave' => $todayLeave,
            'today_permission' => $todayPermission,
            'today_sick' => $todaySick,
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $days = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayQuery = DB::table('attendances')
                ->whereDate('check_in_time', $date);

            if (!$user->isAdmin) {
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
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendanceQuery = DB::table('attendances')
            ->whereBetween('check_in_time', [$startDate, $endDate]);

        if (!$user->isAdmin) {
            $attendanceQuery->where('employee_id', $user->employee_id);
        }

        $present = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Present->value)->count();
        $late = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Late->value)->count();
        $permission = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Permission->value)->count();
        $leave = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Leave->value)->count();
        $sick = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Sick->value)->count();
        $absent = (clone $attendanceQuery)->where('attendance_status', AttendanceStatus::Absent->value)->count();

        $daysInMonth = $startDate->daysInMonth;
        $totalEmployees = Employee::active()->count();

        if (!$user->isAdmin) {
            $totalEmployees = 1;
        }

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
}

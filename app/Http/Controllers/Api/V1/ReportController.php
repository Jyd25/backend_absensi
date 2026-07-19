<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    public function daily(Request $request): JsonResponse
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $departmentId = $request->get('department_id');

        $query = Employee::active()
            ->with(['department', 'position'])
            ->withCount([
                'attendances as present_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Present->value);
                },
                'attendances as late_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Late->value);
                },
                'attendances as absent_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Absent->value);
                },
                'attendances as permission_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Permission->value);
                },
                'attendances as leave_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Leave->value);
                },
                'attendances as sick_count' => function ($q) use ($date) {
                    $q->whereDate('check_in_time', $date)
                        ->where('attendance_status', AttendanceStatus::Sick->value);
                },
            ]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        $summary = [
            'total_employees' => $employees->count(),
            'present' => $employees->sum('present_count'),
            'late' => $employees->sum('late_count'),
            'absent' => $employees->sum('absent_count'),
            'permission' => $employees->sum('permission_count'),
            'leave' => $employees->sum('leave_count'),
            'sick' => $employees->sum('sick_count'),
        ];

        return $this->successResponse([
            'date' => $date,
            'summary' => $summary,
            'employees' => $employees,
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        $departmentId = $request->get('department_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Employee::active()
            ->with(['department', 'position'])
            ->withCount([
                'attendances as present_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Present->value);
                },
                'attendances as late_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Late->value);
                },
                'attendances as absent_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Absent->value);
                },
                'attendances as permission_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Permission->value);
                },
                'attendances as leave_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Leave->value);
                },
                'attendances as sick_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('attendance_status', AttendanceStatus::Sick->value);
                },
            ]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        $summary = [
            'total_employees' => $employees->count(),
            'present' => $employees->sum('present_count'),
            'late' => $employees->sum('late_count'),
            'absent' => $employees->sum('absent_count'),
            'permission' => $employees->sum('permission_count'),
            'leave' => $employees->sum('leave_count'),
            'sick' => $employees->sum('sick_count'),
        ];

        return $this->successResponse([
            'month' => (int) $month,
            'year' => (int) $year,
            'summary' => $summary,
            'employees' => $employees,
        ]);
    }

    public function employee(Request $request): JsonResponse
    {
        $employeeId = $request->get('employee_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        if (!$employeeId) {
            return $this->errorResponse('Employee ID is required', 422);
        }

        $employee = Employee::with(['department', 'position'])
            ->findOrFail($employeeId);

        $attendances = $employee->attendances()
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->latest('check_in_time')
            ->get();

        $summary = [
            'present' => $attendances->where('attendance_status', AttendanceStatus::Present->value)->count(),
            'late' => $attendances->where('attendance_status', AttendanceStatus::Late->value)->count(),
            'absent' => $attendances->where('attendance_status', AttendanceStatus::Absent->value)->count(),
            'permission' => $attendances->where('attendance_status', AttendanceStatus::Permission->value)->count(),
            'leave' => $attendances->where('attendance_status', AttendanceStatus::Leave->value)->count(),
            'sick' => $attendances->where('attendance_status', AttendanceStatus::Sick->value)->count(),
        ];

        return $this->successResponse([
            'employee' => $employee,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $summary,
            'attendances' => $attendances,
        ]);
    }

    public function department(Request $request): JsonResponse
    {
        $departmentId = $request->get('department_id');
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query = DB::table('departments')
            ->join('employees', 'departments.id', '=', 'employees.department_id')
            ->join('attendances', 'employees.id', '=', 'attendances.employee_id')
            ->whereBetween('attendances.check_in_time', [$startDate, $endDate])
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(DISTINCT employees.id) as total_employees'),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'permission' THEN 1 ELSE 0 END) as permission"),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'leave' THEN 1 ELSE 0 END) as leave"),
                DB::raw("SUM(CASE WHEN attendances.attendance_status = 'sick' THEN 1 ELSE 0 END) as sick")
            )
            ->groupBy('departments.id', 'departments.name');

        if ($departmentId) {
            $query->where('departments.id', $departmentId);
        }

        $departments = $query->get();

        return $this->successResponse([
            'month' => (int) $month,
            'year' => (int) $year,
            'departments' => $departments,
        ]);
    }
}

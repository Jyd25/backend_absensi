<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository extends BaseRepository
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function getTodayByEmployee($employeeId)
    {
        return $this->model->where('employee_id', $employeeId)
            ->whereDate('check_in_time', today())
            ->latest('check_in_time')
            ->first();
    }

    public function withEmployee()
    {
        return $this->model->query()->with(['employee', 'location', 'schedule']);
    }

    public function getByDateRange($start, $end)
    {
        return $this->model->whereBetween('check_in_time', [$start, $end]);
    }
}

<?php

namespace App\Repositories\FaceRecognition;

use App\Models\FaceDataset;
use App\Repositories\BaseRepository;

class FaceRepository extends BaseRepository
{
    public function __construct(FaceDataset $model)
    {
        parent::__construct($model);
    }

    public function getPrimaryByEmployee($employeeId)
    {
        return $this->model->where('employee_id', $employeeId)
            ->where('is_primary', true)
            ->whereNotNull('descriptor_path')
            ->first();
    }

    public function getByEmployee($employeeId)
    {
        return $this->model->where('employee_id', $employeeId)
            ->with('employee')
            ->latest()
            ->get();
    }
}

<?php

namespace App\Repositories;

use App\Models\WorkSchedule;

class WorkScheduleRepository extends BaseRepository
{
    public function __construct(WorkSchedule $model)
    {
        parent::__construct($model);
    }

    public function search(string $term)
    {
        return $this->model->where('name', 'like', "%{$term}%");
    }
}

<?php

namespace App\Repositories;

use App\Models\AttendanceLocation;

class AttendanceLocationRepository extends BaseRepository
{
    public function __construct(AttendanceLocation $model)
    {
        parent::__construct($model);
    }

    public function search(string $term)
    {
        return $this->model->where('location_name', 'like', "%{$term}%")
            ->orWhere('address', 'like', "%{$term}%");
    }
}

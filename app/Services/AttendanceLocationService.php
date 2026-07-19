<?php

namespace App\Services;

use App\Repositories\AttendanceLocationRepository;

class AttendanceLocationService extends BaseService
{
    public function __construct(AttendanceLocationRepository $repository)
    {
        parent::__construct($repository);
    }

    public function search(string $term)
    {
        return $this->repository->search($term);
    }
}

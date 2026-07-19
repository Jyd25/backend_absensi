<?php

namespace App\Services;

use App\Repositories\WorkScheduleRepository;

class WorkScheduleService extends BaseService
{
    public function __construct(WorkScheduleRepository $repository)
    {
        parent::__construct($repository);
    }

    public function search(string $term)
    {
        return $this->repository->search($term);
    }
}

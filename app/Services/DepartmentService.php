<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;

class DepartmentService extends BaseService
{
    public function __construct(DepartmentRepository $repository)
    {
        parent::__construct($repository);
    }

    public function search(string $term)
    {
        return $this->repository->search($term);
    }
}

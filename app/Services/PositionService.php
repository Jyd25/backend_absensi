<?php

namespace App\Services;

use App\Repositories\PositionRepository;

class PositionService extends BaseService
{
    public function __construct(PositionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function search(string $term)
    {
        return $this->repository->search($term);
    }
}

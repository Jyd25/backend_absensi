<?php

namespace App\Repositories;

use App\Models\Position;

class PositionRepository extends BaseRepository
{
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }

    public function search(string $term)
    {
        return $this->model->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }
}

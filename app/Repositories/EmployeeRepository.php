<?php

namespace App\Repositories;

use App\Models\Employee;

class EmployeeRepository extends BaseRepository
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    public function search($term)
    {
        return $this->model->query()->search($term);
    }

    public function getActive()
    {
        return $this->model->query()->active();
    }

    public function withRelations()
    {
        return $this->model->query()->with(['department', 'position', 'schedule']);
    }
}

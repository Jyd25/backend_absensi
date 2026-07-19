<?php

namespace App\Repositories\Auth;

use App\Models\LoginLog;
use App\Models\User;
use App\Repositories\BaseRepository;

class AuthRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function createLoginLog(array $data): LoginLog
    {
        return LoginLog::create($data);
    }

    public function getProfile(int $userId): ?User
    {
        return $this->model->with([
            'role',
            'employee.department',
            'employee.position',
            'employee.schedule',
        ])->find($userId);
    }
}

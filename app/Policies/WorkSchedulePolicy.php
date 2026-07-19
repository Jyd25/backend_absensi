<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkSchedule;

class WorkSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkSchedule $schedule): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function update(User $user, WorkSchedule $schedule): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function delete(User $user, WorkSchedule $schedule): bool
    {
        return $user->role?->name === 'Administrator';
    }
}

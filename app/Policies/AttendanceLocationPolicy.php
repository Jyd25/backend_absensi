<?php

namespace App\Policies;

use App\Models\AttendanceLocation;
use App\Models\User;

class AttendanceLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AttendanceLocation $location): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function update(User $user, AttendanceLocation $location): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function delete(User $user, AttendanceLocation $location): bool
    {
        return $user->role?->name === 'Administrator';
    }
}

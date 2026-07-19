<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        if (in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return true;
        }

        return true;
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if (in_array($user->role?->name, ['Administrator', 'Pimpinan'])) {
            return true;
        }

        return $user->employee_id === $attendance->employee_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Guru', 'Karyawan']);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->role?->name === 'Administrator';
    }
}

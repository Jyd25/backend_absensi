<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Administrator', 'Pimpinan']);
    }

    public function view(User $user, User $target): bool
    {
        return in_array($user->role?->name, ['Administrator', 'Pimpinan'])
            || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'Administrator';
    }

    public function update(User $user, User $target): bool
    {
        return $user->role?->name === 'Administrator'
            || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->role?->name === 'Administrator';
    }
}

<?php

namespace App\Traits;

use App\Models\Notification;
use App\Models\User;
use App\Jobs\SendNotificationJob;

trait SendsNotifications
{
    protected function notifyAdmins(string $title, string $message, string $type = 'info', array $data = []): void
    {
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'Administrator'))->get();

        foreach ($admins as $admin) {
            SendNotificationJob::dispatch($admin->id, $title, $message, $type, $data);
        }
    }

    protected function notifyUser(int $userId, string $title, string $message, string $type = 'info', array $data = []): void
    {
        SendNotificationJob::dispatch($userId, $title, $message, $type, $data);
    }

    protected function notifyRole(string $roleName, string $title, string $message, string $type = 'info', array $data = []): void
    {
        $users = User::whereHas('role', fn($q) => $q->where('name', $roleName))->get();

        foreach ($users as $user) {
            SendNotificationJob::dispatch($user->id, $title, $message, $type, $data);
        }
    }
}

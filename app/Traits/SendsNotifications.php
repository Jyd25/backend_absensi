<?php

namespace App\Traits;

use App\Models\User;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Log;

trait SendsNotifications
{
    protected function notifyAdmins(string $title, string $message, string $type = 'info', array $data = []): void
    {
        try {
            $admins = User::whereHas('role', fn($q) => $q->where('name', 'Administrator'))->get();

            foreach ($admins as $admin) {
                SendNotificationJob::dispatchSync($admin->id, $title, $message, $type, $data);
            }
        } catch (\Throwable $e) {
            Log::error('notifyAdmins gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    protected function notifyUser(int $userId, string $title, string $message, string $type = 'info', array $data = []): void
    {
        try {
            SendNotificationJob::dispatchSync($userId, $title, $message, $type, $data);
        } catch (\Throwable $e) {
            Log::error('notifyUser gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    protected function notifyRole(string $roleName, string $title, string $message, string $type = 'info', array $data = []): void
    {
        try {
            $users = User::whereHas('role', fn($q) => $q->where('name', $roleName))->get();

            foreach ($users as $user) {
                SendNotificationJob::dispatchSync($user->id, $title, $message, $type, $data);
            }
        } catch (\Throwable $e) {
            Log::error('notifyRole gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}

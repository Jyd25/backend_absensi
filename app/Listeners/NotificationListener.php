<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Log;

class NotificationListener
{
    public function handle(NotificationCreated $event): void
    {
        try {
            $notification = $event->notification;

            broadcast()->event('notification.' . $notification->user_id, [
                'type' => 'notification_created',
                'notification' => $notification,
            ]);
        } catch (\Throwable $e) {
            Log::error('NotificationListener gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}

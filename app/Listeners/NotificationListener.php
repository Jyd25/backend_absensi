<?php

namespace App\Listeners;

use App\Events\NotificationCreated;

class NotificationListener
{
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        broadcast()->event('notification.' . $notification->user_id, [
            'type' => 'notification_created',
            'notification' => $notification,
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        broadcast()->event('notification.' . $notification->user_id, [
            'type' => 'notification_created',
            'notification' => $notification,
        ]);
    }
}

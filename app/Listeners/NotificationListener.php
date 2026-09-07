<?php

namespace App\Listeners;

use App\Events\NotificationBroadcast;
use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Log;

class NotificationListener
{
    public function handle(NotificationCreated $event): void
    {
        try {
            broadcast(new NotificationBroadcast($event->notification));
        } catch (\Throwable $e) {
            Log::error('NotificationListener gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
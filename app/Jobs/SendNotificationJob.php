<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private string $title,
        private string $message,
        private string $type = 'info',
        private array $data = [],
    ) {}

    public function handle(): void
    {
        $notification = Notification::create([
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'is_read' => false,
        ]);

        event(new \App\Events\NotificationCreated($notification));
    }
}

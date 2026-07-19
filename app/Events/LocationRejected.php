<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public array $locationData
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('employee')];
    }

    public function broadcastAs(): string
    {
        return 'location.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'location' => $this->locationData,
        ];
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $stats)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return ['stats' => $this->stats];
    }
}

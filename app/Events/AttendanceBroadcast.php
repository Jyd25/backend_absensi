<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Attendance $attendance)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('attendance')];
    }

    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->attendance->id,
            'employee' => [
                'id' => $this->attendance->employee_id,
                'name' => $this->attendance->employee?->name,
            ],
            'attendance_status' => $this->attendance->attendance_status,
            'check_in_time' => $this->attendance->check_in_time,
            'check_out_time' => $this->attendance->check_out_time,
        ];
    }
}

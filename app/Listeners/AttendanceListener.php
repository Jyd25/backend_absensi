<?php

namespace App\Listeners;

use App\Enums\ProcessStatus;
use App\Events\AttendanceCreated;
use App\Models\AttendanceProcess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AttendanceListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AttendanceCreated $event): void
    {
        $attendance = $event->attendance;

        AttendanceProcess::create([
            'attendance_id' => $attendance->id,
            'step' => 'location_validation',
            'status' => ProcessStatus::Completed,
            'description' => 'Location validated successfully',
            'processed_at' => now(),
        ]);

        AttendanceProcess::create([
            'attendance_id' => $attendance->id,
            'step' => 'face_validation',
            'status' => ProcessStatus::Completed,
            'description' => 'Face validated successfully',
            'processed_at' => now(),
        ]);

        AttendanceProcess::create([
            'attendance_id' => $attendance->id,
            'step' => 'status_determination',
            'status' => ProcessStatus::Completed,
            'description' => 'Status determined: ' . $attendance->attendance_status->value,
            'processed_at' => now(),
        ]);

        broadcast()->event('attendance', [
            'type' => 'attendance_created',
            'attendance' => $attendance->load('employee'),
        ]);
    }
}

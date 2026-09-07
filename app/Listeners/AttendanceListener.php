<?php

namespace App\Listeners;

use App\Enums\ProcessStatus;
use App\Events\AttendanceBroadcast;
use App\Events\AttendanceCreated;
use App\Models\AttendanceProcess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AttendanceListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AttendanceCreated $event): void
    {
        try {
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

            broadcast(new AttendanceBroadcast($attendance->load('employee')));
        } catch (\Throwable $e) {
            Log::error('AttendanceListener gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
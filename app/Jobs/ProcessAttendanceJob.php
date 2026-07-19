<?php

namespace App\Jobs;

use App\Events\AttendanceCreated;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Attendance $attendance)
    {
    }

    public function uniqueId(): string
    {
        return 'process_attendance_' . $this->attendance->id;
    }

    public function handle(): void
    {
        $attendance = $this->attendance;
        $employee = $attendance->employee;
        $schedule = $employee->schedule;

        if (!$schedule) {
            $schedule = WorkSchedule::first();
        }

        $location = $attendance->location;
        $isValidLocation = $this->validateLocation($attendance, $location);
        $attendance->location_status = $isValidLocation ? \App\Enums\LocationStatus::InsideRadius : \App\Enums\LocationStatus::OutsideRadius;

        $isValidFace = $this->validateFace($attendance);
        $attendance->face_status = $isValidFace ? \App\Enums\FaceStatus::Matched : \App\Enums\FaceStatus::Unmatched;

        $attendance->attendance_status = $this->determineStatus($attendance, $schedule);

        $attendance->save();

        event(new AttendanceCreated($attendance));
    }

    private function validateLocation(Attendance $attendance, $location): bool
    {
        if (!$location) {
            return false;
        }

        $distance = $this->calculateDistance(
            $attendance->latitude,
            $attendance->longitude,
            $location->latitude,
            $location->longitude
        );

        $attendance->distance = $distance;

        return $distance <= $location->radius;
    }

    private function validateFace(Attendance $attendance): bool
    {
        return $attendance->face_score >= 60;
    }

    private function determineStatus(Attendance $attendance, WorkSchedule $schedule): \App\Enums\AttendanceStatus
    {
        $checkInTime = \Carbon\Carbon::parse($attendance->check_in_time);
        $scheduleStart = \Carbon\Carbon::parse($schedule->start_time);
        $toleranceMinutes = $schedule->tolerance_minutes;

        $lateThreshold = $scheduleStart->copy()->addMinutes($toleranceMinutes);

        if ($checkInTime->gt($lateThreshold)) {
            return \App\Enums\AttendanceStatus::Late;
        }

        return \App\Enums\AttendanceStatus::Present;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

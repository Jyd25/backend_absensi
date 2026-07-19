<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAttendanceReminder extends Command
{
    protected $signature = 'attendance:remind';
    protected $description = 'Send check-in/check-out reminder notifications to employees';

    public function handle(): int
    {
        $now = Carbon::now('Asia/Jakarta');
        $hour = $now->hour;
        $minute = $now->minute;
        $isSaturday = $now->isDay(Carbon::SATURDAY);

        $activeEmployees = Employee::where('is_active', true)
            ->with('user', 'schedule')
            ->get()
            ->filter(fn ($e) => $e->user);

        foreach ($activeEmployees as $employee) {
            $schedule = $employee->schedule;
            if (!$schedule) continue;

            $userId = $employee->user->id;

            // Determine schedule times
            if ($isSaturday && $schedule->saturday_start_time) {
                $startHour = (int) Carbon::parse($schedule->saturday_start_time)->hour;
                $startMinute = (int) Carbon::parse($schedule->saturday_start_time)->minute;
            } else {
                $startHour = (int) Carbon::parse($schedule->start_time)->hour;
                $startMinute = (int) Carbon::parse($schedule->start_time)->minute;
            }

            $endHour = (int) Carbon::parse($schedule->end_time)->hour;

            // Check if already checked in today
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('check_in_time', $now->toDateString())
                ->first();

            $hasCheckedIn = $todayAttendance && $todayAttendance->check_in_time;
            $hasCheckedOut = $todayAttendance && $todayAttendance->check_out_time;

            // Check-in reminders
            if (!$hasCheckedIn) {
                // 30 minutes before schedule start
                $remindBefore = $startHour * 60 + $startMinute - 30;
                $currentMinutes = $hour * 60 + $minute;

                if ($currentMinutes >= $remindBefore && $currentMinutes < $startHour * 60 + $startMinute) {
                    $cacheKey = "reminder_ci_30_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Pengingat Check-In',
                            '30 menit lagi waktu masuk dimulai. Siapkan diri untuk absen!',
                            'reminder'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }

                // At schedule start time
                if ($hour === $startHour && $minute === $startMinute) {
                    $cacheKey = "reminder_ci_start_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Waktu Check-In',
                            'Waktu masuk sudah dimulai! Segera lakukan check-in.',
                            'reminder'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }

                // 15 minutes after start (late warning)
                $lateThreshold = $startHour * 60 + $startMinute + 15;
                if ($currentMinutes >= $lateThreshold && $currentMinutes < $lateThreshold + 2) {
                    $cacheKey = "reminder_ci_late_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Anda Terlambat!',
                            'Anda belum check-in. Status akan tercatat sebagai Terlambat.',
                            'warning'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }
            }

            // Check-out reminders
            if ($hasCheckedIn && !$hasCheckedOut) {
                // 1 hour before end
                $co1h = $endHour - 1;
                if ($hour === $co1h && $minute === 0) {
                    $cacheKey = "reminder_co_1h_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Pengingat Check-Out',
                            '1 jam lagi sebelum waktu pulang. Jangan lupa check-out nanti!',
                            'reminder'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }

                // At end time
                if ($hour === $endHour && $minute === 0) {
                    $cacheKey = "reminder_co_end_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Waktu Check-Out',
                            'Waktu pulang sudah tiba! Segera lakukan check-out.',
                            'reminder'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }

                // 15 minutes after end (overdue)
                $overdueThreshold = $endHour * 60 + 15;
                $currentMinutes = $hour * 60 + $minute;
                if ($currentMinutes >= $overdueThreshold && $currentMinutes < $overdueThreshold + 2) {
                    $cacheKey = "reminder_co_overdue_{$employee->id}_{$now->toDateString()}";
                    if (!\Cache::has($cacheKey)) {
                        SendNotificationJob::dispatch(
                            $userId,
                            'Check-Out Terlewat!',
                            'Anda belum check-out. Segera check-out untuk menghindari status Alpha.',
                            'warning'
                        );
                        \Cache::put($cacheKey, true, now()->endOfDay());
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FaceStatus;
use App\Enums\LocationStatus;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JulyAttendanceDemoSeeder extends Seeder
{
    /**
     * Demo data: fill 1-31 July 2026 with attendance records for every
     * active employee — all "Hadir" with randomized clock in/out times,
     * so the export + bulk email report can be demoed end-to-end.
     * Re-running wipes and rebuilds July data for a clean full month.
     */
    public function run(): void
    {
        $year = 2026;
        $month = 7;
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $employees = Employee::query()->active()->with('schedule')->get();
        $location = AttendanceLocation::where('is_active', true)->orderBy('id')->first();

        if ($employees->isEmpty()) {
            $this->command->warn('Tidak ada karyawan aktif — seeding dilewati.');

            return;
        }

        // Clean rebuild of July scope
        $deleted = Attendance::where(function ($q) use ($year, $month) {
            $q->whereBetween('check_in_time', [Carbon::create($year, $month, 1)->startOfDay(), Carbon::create($year, $month, Carbon::createFromDate($year, $month, 1)->daysInMonth)->endOfDay()])
                ->orWhere(function ($q2) use ($year, $month) {
                    $q2->whereNull('check_in_time')
                        ->whereBetween('check_out_time', [Carbon::create($year, $month, 1)->startOfDay(), Carbon::create($year, $month, Carbon::createFromDate($year, $month, 1)->daysInMonth)->endOfDay()]);
                });
        })->delete();

        $created = 0;

        foreach (range(1, $daysInMonth) as $day) {
            $date = Carbon::createFromDate($year, $month, $day);
            $isSaturday = $date->isSaturday();

            foreach ($employees as $employee) {
                // Schedule-aware working hours (Saturday variants included)
                $schedule = $employee->schedule;
                if ($isSaturday && $schedule?->saturday_start_time) {
                    $startTime = $schedule->saturday_start_time;
                    $endTime = $schedule->saturday_end_time ?? $startTime;
                } elseif ($schedule?->start_time) {
                    $startTime = $schedule->start_time;
                    $endTime = $schedule->end_time;
                } else {
                    $startTime = '07:15';
                    $endTime = '16:00';
                }

                $startAt = Carbon::parse($date->toDateString() . ' ' . substr($startTime, 0, 5), 'Asia/Jakarta');
                $endAt = Carbon::parse($date->toDateString() . ' ' . substr($endTime, 0, 5), 'Asia/Jakarta');

                // Deterministic pseudo-random jitter per employee/day
                $seed = crc32("{$employee->id}-{$date->toDateString()}");
                mt_srand($seed);
                $checkIn = $startAt->copy()->subMinutes(mt_rand(3, 25));
                $checkOut = $endAt->copy()->addMinutes(mt_rand(5, 40));
                $latJitter = mt_rand(-80, 80) / 1e6;
                $lngJitter = mt_rand(-80, 80) / 1e6;

                Attendance::create([
                    'employee_id' => $employee->id,
                    'location_id' => $location?->id,
                    'schedule_id' => $schedule?->id,
                    'attendance_type' => AttendanceType::CheckIn->value,
                    'check_in_time' => $checkIn,
                    'check_out_time' => $checkOut,
                    'latitude' => $location ? $location->latitude + $latJitter : null,
                    'longitude' => $location ? $location->longitude + $lngJitter : null,
                    'distance' => $location ? mt_rand(4, 38) : null,
                    'face_score' => mt_rand(88, 99) + (mt_rand(0, 9) / 10),
                    'location_status' => LocationStatus::InsideRadius->value,
                    'face_status' => FaceStatus::Matched->value,
                    'attendance_status' => AttendanceStatus::Present->value,
                    'device' => 'Web',
                    'ip_address' => '127.0.0.' . ($seed % 254 + 1),
                    'address' => 'Jl. Rancamaya No.30, Bogor',
                    'checkout_address' => 'Jl. Rancamaya No.30, Bogor',
                    'status_checkout' => 'Pulang Tepat Waktu',
                    'remarks' => null,
                ]);

                $created++;
            }
        }

        mt_srand();

        $this->command->info("Demo kehadiran Juli {$year}: {$deleted} record lama dihapus, {$created} record 'Hadir' dibuat (semua karyawan, tanggal 1-31).");
    }
}

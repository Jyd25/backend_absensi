<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FaceStatus;
use App\Enums\LocationStatus;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JulyAttendanceDemoSeeder extends Seeder
{
    /**
     * Demo data: fill July 2026 with realistic attendance records for every
     * active employee so report export/email can be tested end-to-end.
     * Idempotent: existing records are never duplicated.
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

        $created = 0;
        $skipped = 0;

        foreach (range(1, $daysInMonth) as $day) {
            $date = Carbon::createFromDate($year, $month, $day);

            // Skip Sundays and registered holidays
            if (HolidayService::isSunday($date) || HolidayService::holidayOn($date)) {
                continue;
            }

            $isSaturday = $date->isSaturday();

            foreach ($employees as $employee) {
                // Schedule-aware working hours (Saturday variants included)
                $schedule = $employee->schedule;
                if ($isSaturday && $schedule?->saturday_start_time) {
                    $startTime = $schedule->saturday_start_time;
                    $endTime = $schedule->saturday_end_time ?? $startTime;
                    $tolerance = $schedule->tolerance_minutes ?? 0;
                } elseif ($schedule?->start_time) {
                    $startTime = $schedule->start_time;
                    $endTime = $schedule->end_time;
                    $tolerance = $schedule->tolerance_minutes ?? 0;
                } else {
                    $startTime = '07:15';
                    $endTime = '16:00';
                    $tolerance = 15;
                }

                $startAt = Carbon::parse($date->toDateString() . ' ' . substr($startTime, 0, 5), 'Asia/Jakarta');
                $endAt = Carbon::parse($date->toDateString() . ' ' . substr($endTime, 0, 5), 'Asia/Jakarta');

                $alreadyExists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('check_in_time', $date->toDateString())
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;

                    continue;
                }

                // Deterministic pseudo-random distribution per employee/day
                $seed = crc32("{$employee->id}-{$date->toDateString()}");
                mt_srand($seed);
                $pick = mt_rand(1, 100);
                $jitter = fn (int $min, int $max) => mt_rand($min, $max);

                if ($pick <= 68) {
                    [$status, $checkIn, $checkOut] = [
                        AttendanceStatus::Present,
                        $startAt->copy()->subMinutes($jitter(5, 25)),
                        $endAt->copy()->addMinutes($jitter(5, 40)),
                    ];
                    $remarks = null;
                } elseif ($pick <= 84) {
                    [$status, $checkIn, $checkOut] = [
                        AttendanceStatus::Late,
                        $startAt->copy()->addMinutes($tolerance + $jitter(3, 30)),
                        $endAt->copy()->addMinutes($jitter(0, 25)),
                    ];
                    $remarks = 'Terlambat ' . $jitter(5, 35) . ' menit';
                } elseif ($pick <= 91) {
                    [$status, $checkIn, $checkOut] = [
                        AttendanceStatus::Permission,
                        $startAt->copy()->subMinutes($jitter(10, 20)),
                        $endAt->copy()->subMinutes($jitter(120, 200)),
                    ];
                    $remarks = 'Izin keperluan keluarga';
                } elseif ($pick <= 96) {
                    [$status, $checkIn, $checkOut] = [
                        AttendanceStatus::Sick,
                        $startAt->copy()->subMinutes($jitter(10, 20)),
                        $endAt->copy()->subMinutes($jitter(150, 240)),
                    ];
                    $remarks = 'Sakit — surat keterangan menyusul';
                } else {
                    [$status, $checkIn, $checkOut] = [
                        AttendanceStatus::Leave,
                        $startAt->copy(),
                        $endAt->copy()->subHour(),
                    ];
                    $remarks = 'Cuti tahunan';
                }

                $checkoutEarlyMinutes = $checkOut->diffInMinutes($endAt, false);
                $statusCheckout = $checkoutEarlyMinutes >= 15 ? 'Pulang Cepat' : 'Pulang Tepat Waktu';

                $latJitter = $location ? mt_rand(-80, 80) / 1e6 : 0;
                $lngJitter = $location ? mt_rand(-80, 80) / 1e6 : 0;

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
                    'attendance_status' => $status->value,
                    'device' => 'Web',
                    'ip_address' => '127.0.0.' . ($seed % 254 + 1),
                    'address' => 'Jl. Rancamaya No.30, Bogor',
                    'checkout_address' => 'Jl. Rancamaya No.30, Bogor',
                    'status_checkout' => $statusCheckout,
                    'remarks' => $remarks,
                ]);

                $created++;
            }
        }

        mt_srand();

        $this->command->info("Demo kehadiran Juli {$year}: {$created} record dibuat, {$skipped} dilewati (sudah ada).");
    }
}

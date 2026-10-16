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
use Illuminate\Support\Collection;

class AttendanceDemoSeeder extends Seeder
{
    /**
     * Bulan yang diisi data demo kehadiran (tahun, bulan).
     * Tambahkan pasangan baru bila perlu, contoh: [2026, 9].
     *
     * @var array<int, array{0: int, 1: int}>
     */
    public array $months = [
        [2026, 7],
        [2026, 8],
    ];

    /**
     * @var array<int, string>
     */
    private array $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Mengisi data kehadiran demo untuk bulan-bulan di $months bagi semua
     * karyawan aktif, mengikuti jam kerja masing-masing pada tabel
     * work_schedules:
     *   - hari kerja  : jam masuk sedikit sebelum jadwal dan jam pulang
     *                   sedikit setelah jadwal, status "Hadir";
     *   - hari Sabtu  : memakai jadwal Sabtu (bila diatur);
     *   - hari Minggu : dicatat "Libur";
     *   - hari di luar working_days : dilewati.
     *
     * Baris yang SUDAH ADA (misalnya hasil migrasi data produksi) tidak diubah
     * dan tidak dihapus, sehingga seeder ini aman dijalankan berulang kali.
     */
    public function run(): void
    {
        // Data DEMO tidak boleh masuk ke lingkungan produksi. Beberapa jalur
        // deploy menjalankan `php artisan db:seed --force` (mis. bin/*.sh di
        // Render), jadi seeder ini hanya berjalan di lokal/development/testing.
        if (! app()->environment(['local', 'development', 'testing'])) {
            $this->command->warn('Lingkungan "' . app()->environment() . '" - AttendanceDemoSeeder dilewati (data demo tidak diisi).');

            return;
        }

        $employees = Employee::query()->active()->with('schedule')->get();
        $location = AttendanceLocation::where('is_active', true)->orderBy('id')->first();

        if ($employees->isEmpty()) {
            $this->command->warn('Tidak ada karyawan aktif - seeding dilewati.');

            return;
        }

        $created = 0;
        $kept = 0;
        $skipped = 0;

        foreach ($this->months as [$year, $month]) {
            [$monthCreated, $monthKept, $monthSkipped] = $this->seedMonth($year, $month, $employees, $location);

            $created += $monthCreated;
            $kept += $monthKept;
            $skipped += $monthSkipped;

            $this->command->info(sprintf(
                'Demo kehadiran %s %d: %d record dibuat, %d hari dipertahankan, %d hari di luar jadwal.',
                $this->monthNames[$month],
                $year,
                $monthCreated,
                $monthKept,
                $monthSkipped,
            ));
        }

        $this->command->info(sprintf(
            'Total: %d record demo dibuat, %d hari dipertahankan, %d hari dilewati.',
            $created,
            $kept,
            $skipped,
        ));
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array{0: int, 1: int, 2: int} [dibuat, dipertahankan, dilewati]
     */
    private function seedMonth(int $year, int $month, Collection $employees, ?AttendanceLocation $location): array
    {
        $first = Carbon::create($year, $month, 1)->startOfDay();
        $last = $first->copy()->endOfMonth();

        // Tanggal yang sudah punya data (mis. hasil migrasi backup) -> jangan diubah.
        $existing = Attendance::query()
            ->where(function ($query) use ($first, $last) {
                $query->whereBetween('check_in_time', [$first, $last])
                    ->orWhere(function ($sub) use ($first, $last) {
                        $sub->whereNull('check_in_time')->whereBetween('check_out_time', [$first, $last]);
                    });
            })
            ->get(['employee_id', 'check_in_time', 'check_out_time'])
            ->mapWithKeys(function (Attendance $attendance) {
                $time = $attendance->check_in_time ?? $attendance->check_out_time;

                return [$attendance->employee_id . '|' . $time?->toDateString() => true];
            });

        $created = 0;
        $kept = 0;
        $skipped = 0;

        foreach (range(1, $first->daysInMonth) as $day) {
            $date = Carbon::create($year, $month, $day)->startOfDay();

            foreach ($employees as $employee) {
                if ($existing->has($employee->id . '|' . $date->toDateString())) {
                    $kept++;

                    continue;
                }

                if ($this->createDailyAttendance($employee, $date, $location)) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        return [$created, $kept, $skipped];
    }

    /**
     * Membuat satu baris kehadiran. Mengembalikan false bila hari tersebut
     * tidak termasuk jadwal kerja karyawan (tidak ada baris dibuat).
     */
    private function createDailyAttendance(Employee $employee, Carbon $date, ?AttendanceLocation $location): bool
    {
        $schedule = $employee->schedule;

        // Minggu = hari libur (mengikuti pengisian otomatis aplikasi).
        if ($date->isSunday()) {
            Attendance::create([
                'employee_id' => $employee->id,
                'schedule_id' => $schedule?->id,
                'attendance_type' => AttendanceType::CheckIn->value,
                'check_in_time' => $date->copy()->startOfDay(),
                'attendance_status' => AttendanceStatus::Libur->value,
                'remarks' => 'Hari libur otomatis (Hari Minggu)',
            ]);

            return true;
        }

        // Lewati hari yang tidak termasuk working_days jadwal.
        $workingDays = $schedule?->working_days;
        if (is_array($workingDays) && $workingDays !== []
            && ! in_array(strtolower($date->format('l')), array_map('strtolower', $workingDays), true)) {
            return false;
        }

        // Jam masuk/pulang mengikuti jadwal karyawan (default 07:00-16:00).
        $startTime = $schedule?->startTimeFor($date) ?? Carbon::parse('07:00');
        $endTime = $schedule?->endTimeFor($date) ?? Carbon::parse('16:00');

        $startAt = $this->atDate($date, $startTime);
        $endAt = $this->atDate($date, $endTime);

        // Jitter acak tapi deterministik per karyawan/hari.
        $seed = crc32("{$employee->id}-{$date->toDateString()}");
        mt_srand($seed);
        $checkIn = $startAt->copy()->subMinutes(mt_rand(3, 25));
        $checkOut = $endAt->copy()->addMinutes(mt_rand(5, 40));
        $latJitter = mt_rand(-80, 80) / 1e6;
        $lngJitter = mt_rand(-80, 80) / 1e6;
        $distance = mt_rand(4, 38);
        $faceScore = mt_rand(88, 99) + (mt_rand(0, 9) / 10);
        $ipSuffix = ($seed % 254) + 1;
        mt_srand();

        // Status dihitung dengan aturan yang sama seperti aplikasi:
        // "Terlambat" hanya bila check-in melewati jam masuk jadwal.
        $status = $schedule?->isLate($checkIn) ? AttendanceStatus::Late : AttendanceStatus::Present;

        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location?->id,
            'schedule_id' => $schedule?->id,
            'attendance_type' => AttendanceType::CheckIn->value,
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'latitude' => $location ? $location->latitude + $latJitter : null,
            'longitude' => $location ? $location->longitude + $lngJitter : null,
            'distance' => $location ? $distance : null,
            'face_score' => $faceScore,
            'location_status' => LocationStatus::InsideRadius->value,
            'face_status' => FaceStatus::Matched->value,
            'attendance_status' => $status->value,
            'device' => 'Web',
            'ip_address' => '127.0.0.' . $ipSuffix,
            'address' => 'Jl. Rancamaya No.30, Bogor',
            'checkout_address' => 'Jl. Rancamaya No.30, Bogor',
            'status_checkout' => 'Pulang Tepat Waktu',
            'remarks' => null,
        ]);

        return true;
    }

    /**
     * Tempelkan jam:menit dari $timeOfDay ke tanggal $date.
     */
    private function atDate(Carbon $date, Carbon $timeOfDay): Carbon
    {
        return $date->copy()->setTime($timeOfDay->hour, $timeOfDay->minute, 0);
    }
}

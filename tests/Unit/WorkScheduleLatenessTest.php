<?php

namespace Tests\Unit;

use App\Models\WorkSchedule;
use Carbon\Carbon;
use Tests\TestCase;

class WorkScheduleLatenessTest extends TestCase
{
    private function schedule(array $attributes = []): WorkSchedule
    {
        return new WorkSchedule(array_merge([
            'name' => 'Akademik (Guru & Pimpinan)',
            'start_time' => '07:15',
            'end_time' => '16:00',
            'saturday_start_time' => '08:00',
            'saturday_end_time' => '12:00',
            // Sengaja diberi toleransi: aturan tidak boleh memakai toleransi.
            'tolerance_minutes' => 15,
        ], $attributes));
    }

    public function test_check_in_pukul_0100_sampai_jam_masuk_tidak_terlambat(): void
    {
        $schedule = $this->schedule();

        $times = [
            '2026-07-23 01:00:00',
            '2026-07-23 01:19:31', // data asli dari backup produksi
            '2026-07-23 03:00:00',
            '2026-07-23 06:59:59',
            '2026-07-23 07:14:59',
            '2026-07-23 07:15:00', // tepat pada jam masuk
        ];

        foreach ($times as $time) {
            $checkIn = Carbon::parse($time);

            $this->assertFalse($schedule->isLate($checkIn), "Check-in {$time} seharusnya Hadir, bukan Terlambat.");
            $this->assertSame(0, $schedule->lateMinutes($checkIn), "Menit terlambat {$time} seharusnya 0.");
        }
    }

    public function test_check_in_setelah_jam_masuk_terlambat(): void
    {
        $schedule = $this->schedule();

        // 07:16 -> lewat 1 menit. Toleransi 15 menit TIDAK menggeser batas.
        $checkIn = Carbon::parse('2026-07-23 07:16:00');
        $this->assertTrue($schedule->isLate($checkIn));
        $this->assertSame(1, $schedule->lateMinutes($checkIn));

        // 08:04:51 -> 49,85 menit -> dibulatkan ke atas = 50 (seperti data produksi).
        $checkIn = Carbon::parse('2026-07-23 08:04:51');
        $this->assertTrue($schedule->isLate($checkIn));
        $this->assertSame(50, $schedule->lateMinutes($checkIn));
    }

    public function test_batas_keterlambatan_memakai_tanggal_check_in(): void
    {
        $schedule = $this->schedule();

        // Tanggal jauh di masa lalu; jam masuk jadwal dibaca apa adanya (07:15),
        // tidak boleh tergeser tanggal hari ini.
        $checkIn = Carbon::parse('2026-02-03 07:14:00');
        $this->assertFalse($schedule->isLate($checkIn));

        $this->assertSame(
            '2026-02-03 07:15:00',
            $schedule->lateThresholdFor($checkIn)->format('Y-m-d H:i:s'),
        );
    }

    public function test_sabtu_memakai_jam_masuk_sabtu(): void
    {
        $schedule = $this->schedule();

        // Sabtu 01:00 s/d jam masuk Sabtu (08:00) tidak dihitung terlambat.
        $times = ['01:00:00', '03:00:00', '06:00:00', '07:30:00', '07:59:59', '08:00:00'];
        foreach ($times as $time) {
            $checkIn = Carbon::parse("2026-08-01 {$time}"); // Sabtu
            $this->assertTrue($checkIn->isSaturday());
            $this->assertFalse($schedule->isLate($checkIn), "Sabtu {$time} masih sebelum jam masuk Sabtu (08:00).");
            $this->assertSame(0, $schedule->lateMinutes($checkIn));
        }

        $this->assertTrue($schedule->isLate(Carbon::parse('2026-08-01 08:01:00')));
    }

    public function test_jadwal_tanpa_jam_masuk_tidak_pernah_terlambat(): void
    {
        $schedule = $this->schedule(['start_time' => null, 'saturday_start_time' => null]);

        $this->assertNull($schedule->lateThresholdFor(Carbon::parse('2026-07-23 09:00:00')));
        $this->assertFalse($schedule->isLate(Carbon::parse('2026-07-23 09:00:00')));
        $this->assertSame(0, $schedule->lateMinutes(Carbon::parse('2026-07-23 09:00:00')));
    }
}

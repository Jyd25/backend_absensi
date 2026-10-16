<?php

namespace Tests\Unit;

use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use Tests\TestCase;

class CheckoutStatusTest extends TestCase
{
    private function schedule(array $attributes = []): WorkSchedule
    {
        return new WorkSchedule(array_merge([
            'name' => 'Akademik (Guru & Pimpinan)',
            'start_time' => '07:15',
            'end_time' => '16:00',
            'saturday_start_time' => '08:00',
            'saturday_end_time' => '12:00',
        ], $attributes));
    }

    private function service(): AttendanceService
    {
        return app(AttendanceService::class);
    }

    public function test_pulang_sebelum_jam_pulang_dianggap_pulang_cepat(): void
    {
        $service = $this->service();

        // Contoh dari pengguna: keluar 13:00 s/d 15:59 => Pulang Cepat.
        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-31 13:00:00', $this->schedule()));
        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-31 15:59:00', $this->schedule()));
    }

    public function test_pulang_pada_atau_setelah_jam_pulang_dianggap_tepat_waktu(): void
    {
        $service = $this->service();

        $this->assertSame('Pulang Tepat Waktu', $service->calculateCheckoutStatus('2026-08-31 16:00:00', $this->schedule()));

        // Regresi: tanggal lama tidak boleh dibandingkan dengan jam pulang HARI INI,
        // yang membuat absensi lama selalu berubah menjadi "Pulang Cepat".
        $this->assertSame('Pulang Tepat Waktu', $service->calculateCheckoutStatus('2026-08-31 16:21:00', $this->schedule()));
    }

    public function test_sabtu_memakai_jam_pulang_sabtu(): void
    {
        $service = $this->service();

        // Sabtu jam kerja sampai 12:00 => pulang 10:00 s/d 11:59 "Pulang Cepat",
        // mulai 12:00 "Pulang Tepat Waktu".
        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-29 10:00:00', $this->schedule()));
        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-29 11:00:00', $this->schedule()));
        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-29 11:59:00', $this->schedule()));
        $this->assertSame('Pulang Tepat Waktu', $service->calculateCheckoutStatus('2026-08-29 12:00:00', $this->schedule()));
    }

    public function test_tanpa_jam_pulang_status_tidak_ditentukan(): void
    {
        $this->assertNull($this->service()->calculateCheckoutStatus(null, $this->schedule()));
    }

    public function test_jadwal_lain_memakai_jam_pulangnya_sendiri(): void
    {
        $service = $this->service();
        $dapurPagi = $this->schedule([
            'name' => 'Dapur Pagi',
            'start_time' => '03:00',
            'end_time' => '15:00',
            'saturday_end_time' => null,
        ]);

        $this->assertSame('Pulang Cepat', $service->calculateCheckoutStatus('2026-08-31 14:59:00', $dapurPagi));
        $this->assertSame('Pulang Tepat Waktu', $service->calculateCheckoutStatus('2026-08-31 15:05:00', $dapurPagi));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Hari Libur Nasional & Cuti Bersama Indonesia tahun 2026,
     * sesuai SKB 3 Menteri No. 1497/2/5 Tahun 2025 (ditetapkan 19 September 2025).
     */
    public function run(): void
    {
        $holidays = [
            // === Hari Libur Nasional ===
            ['date' => '2026-01-01', 'name' => 'Tahun Baru 2026 Masehi', 'type' => 'national'],
            ['date' => '2026-01-16', 'name' => 'Isra Mikraj Nabi Muhammad SAW', 'type' => 'national'],
            ['date' => '2026-02-17', 'name' => 'Tahun Baru Imlek 2577 Kongzili', 'type' => 'national'],
            ['date' => '2026-03-19', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'type' => 'national'],
            ['date' => '2026-03-21', 'name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
            ['date' => '2026-03-22', 'name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
            ['date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus (Jumat Agung)', 'type' => 'national'],
            ['date' => '2026-04-05', 'name' => 'Hari Kebangkitan Yesus Kristus (Paskah)', 'type' => 'national'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'type' => 'national'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus', 'type' => 'national'],
            ['date' => '2026-05-27', 'name' => 'Hari Raya Idul Adha 1447 H', 'type' => 'national'],
            ['date' => '2026-05-31', 'name' => 'Hari Raya Waisak 2570 BE', 'type' => 'national'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'type' => 'national'],
            ['date' => '2026-06-16', 'name' => 'Tahun Baru Islam 1448 H (1 Muharam)', 'type' => 'national'],
            ['date' => '2026-08-17', 'name' => 'Hari Proklamasi Kemerdekaan RI', 'type' => 'national'],
            ['date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad SAW', 'type' => 'national'],
            ['date' => '2026-12-25', 'name' => 'Kelahiran Yesus Kristus (Natal)', 'type' => 'national'],

            // === Cuti Bersama ===
            ['date' => '2026-02-16', 'name' => 'Cuti Bersama Tahun Baru Imlek 2577 Kongzili', 'type' => 'collective'],
            ['date' => '2026-03-18', 'name' => 'Cuti Bersama Hari Suci Nyepi (Tahun Baru Saka 1948)', 'type' => 'collective'],
            ['date' => '2026-03-20', 'name' => 'Cuti Bersama Idul Fitri 1447 H', 'type' => 'collective'],
            ['date' => '2026-03-23', 'name' => 'Cuti Bersama Idul Fitri 1447 H', 'type' => 'collective'],
            ['date' => '2026-03-24', 'name' => 'Cuti Bersama Idul Fitri 1447 H', 'type' => 'collective'],
            ['date' => '2026-05-15', 'name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'type' => 'collective'],
            ['date' => '2026-05-28', 'name' => 'Cuti Bersama Idul Adha 1447 H', 'type' => 'collective'],
            ['date' => '2026-12-24', 'name' => 'Cuti Bersama Kelahiran Yesus Kristus (Natal)', 'type' => 'collective'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'type' => $holiday['type'],
                    'description' => $holiday['description'] ?? null,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $weekdaysAndSaturday = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        $schedules = [
            [
                'name' => 'Akademik (Guru & Pimpinan)',
                'start_time' => '07:15',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'tolerance_minutes' => 15,
                'working_days' => $weekdaysAndSaturday,
                'saturday_start_time' => '08:00',
                'saturday_end_time' => '12:00',
            ],
            [
                'name' => 'Staff & Teknisi',
                'start_time' => '07:00',
                'end_time' => '16:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'tolerance_minutes' => 15,
                'working_days' => $weekdaysAndSaturday,
                'saturday_start_time' => '08:00',
                'saturday_end_time' => '12:00',
            ],
            [
                'name' => 'UKS & Finance',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'tolerance_minutes' => 15,
                'working_days' => $weekdaysAndSaturday,
                'saturday_start_time' => '08:00',
                'saturday_end_time' => '12:00',
            ],
            [
                'name' => 'Dapur Pagi',
                'start_time' => '03:00',
                'end_time' => '15:00',
                'break_start' => '10:00',
                'break_end' => '11:00',
                'tolerance_minutes' => 15,
                'working_days' => $weekdays,
                'saturday_start_time' => null,
                'saturday_end_time' => null,
            ],
            [
                'name' => 'Dapur Siang',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_start' => '13:00',
                'break_end' => '14:00',
                'tolerance_minutes' => 15,
                'working_days' => $weekdays,
                'saturday_start_time' => null,
                'saturday_end_time' => null,
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::firstOrCreate(['name' => $schedule['name']], $schedule);
        }
    }
}

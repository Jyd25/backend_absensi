<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Kepala Sekolah', 'description' => 'Kepala Sekolah', 'role_name' => 'Pimpinan'],
            ['name' => 'Admin', 'description' => 'Administrator', 'role_name' => 'Administrator'],
            ['name' => 'Guru', 'description' => 'Guru Mengajar', 'role_name' => 'Guru'],
            ['name' => 'Staff Dapur', 'description' => 'Staff Dapur / Katering', 'role_name' => 'Karyawan'],
            ['name' => 'Staff', 'description' => 'Staff Administrasi', 'role_name' => 'Karyawan'],
            ['name' => 'Teknisi', 'description' => 'Teknisi / Sarana Prasarana', 'role_name' => 'Karyawan'],
            ['name' => 'Operator', 'description' => 'Operator Komputer', 'role_name' => 'Administrator'],
        ];

        foreach ($positions as $position) {
            $role = Role::where('name', $position['role_name'])->first();
            Position::updateOrCreate(
                ['name' => $position['name']],
                [
                    'description' => $position['description'],
                    'role_id' => $role?->id,
                ]
            );
        }
    }
}

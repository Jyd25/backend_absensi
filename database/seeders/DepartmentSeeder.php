<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Akademik', 'description' => 'Bagian Akademik (Guru & Pimpinan)'],
            ['name' => 'Dapur', 'description' => 'Bagian Dapur / Katering'],
            ['name' => 'UKS', 'description' => 'Bagian Usaha Kesehatan Sekolah'],
            ['name' => 'Finance', 'description' => 'Bagian Keuangan'],
            ['name' => 'Staff', 'description' => 'Bagian Staff / Administrasi'],
            ['name' => 'Teknisi', 'description' => 'Bagian Teknisi / Sarpras'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(['name' => $department['name']], $department);
        }
    }
}

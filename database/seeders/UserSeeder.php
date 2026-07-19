<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $pimpinanRole = Role::where('name', 'Pimpinan')->first();
        $guruRole = Role::where('name', 'Guru')->first();
        $karyawanRole = Role::where('name', 'Karyawan')->first();

        $adminPosition = Position::where('name', 'Admin')->first();
        $kepalaPosition = Position::where('name', 'Kepala Sekolah')->first();
        $guruPosition = Position::where('name', 'Guru')->first();
        $staffDapurPosition = Position::where('name', 'Staff Dapur')->first();

        $akademikDept = Department::where('name', 'Akademik')->first();
        $dapurDept = Department::where('name', 'Dapur')->first();

        $scheduleAkademik = WorkSchedule::where('name', 'Akademik (Guru & Pimpinan)')->first();
        $scheduleDapurPagi = WorkSchedule::where('name', 'Dapur Pagi')->first();

        // 1. Jayadi Dinata - Administrator (dept Akademik, schedule Akademik)
        $jayadiEmployee = Employee::updateOrCreate(
            ['nik' => 'ADM001'],
            [
                'name' => 'Jayadi Dinata',
                'gender' => 'male',
                'birth_place' => 'Jakarta',
                'birth_date' => '1980-01-15',
                'phone' => '081234567800',
                'email' => 'jayadi@scr.sch.id',
                'address' => 'Jakarta',
                'department_id' => $akademikDept?->id,
                'position_id' => $adminPosition?->id,
                'schedule_id' => $scheduleAkademik?->id,
                'is_active' => true,
            ]
        );

        $jayadiUser = User::updateOrCreate(
            ['email' => 'jayadi@scr.sch.id'],
            [
                'name' => 'Jayadi Dinata',
                'password' => Hash::make('jayadi123'),
                'role_id' => $adminRole->id,
                'employee_id' => $jayadiEmployee->id,
                'status' => 'active',
            ]
        );
        $jayadiUser->syncRoles('Administrator');

        // 2. Jamal - Staff Dapur (dept Dapur, schedule Dapur Pagi)
        $jamalEmployee = Employee::updateOrCreate(
            ['nik' => 'STF001'],
            [
                'name' => 'Jamal',
                'gender' => 'male',
                'birth_place' => 'Surabaya',
                'birth_date' => '1995-06-15',
                'phone' => '081234567801',
                'email' => 'jamal@scr.sch.id',
                'address' => 'Surabaya',
                'department_id' => $dapurDept?->id,
                'position_id' => $staffDapurPosition?->id,
                'schedule_id' => $scheduleDapurPagi?->id,
                'is_active' => true,
            ]
        );

        $jamalUser = User::updateOrCreate(
            ['email' => 'jamal@scr.sch.id'],
            [
                'name' => 'Jamal',
                'password' => Hash::make('jamal123'),
                'role_id' => $karyawanRole->id,
                'employee_id' => $jamalEmployee->id,
                'status' => 'active',
            ]
        );
        $jamalUser->syncRoles('Karyawan');

        // 3. Susanto - Pimpinan / Kepala Sekolah (dept Akademik, schedule Akademik)
        $susantoEmployee = Employee::updateOrCreate(
            ['nik' => 'PIMP001'],
            [
                'name' => 'Susanto',
                'gender' => 'male',
                'birth_place' => 'Bandung',
                'birth_date' => '1975-03-20',
                'phone' => '081234567802',
                'email' => 'susanto@scr.sch.id',
                'address' => 'Bandung',
                'department_id' => $akademikDept?->id,
                'position_id' => $kepalaPosition?->id,
                'schedule_id' => $scheduleAkademik?->id,
                'is_active' => true,
            ]
        );

        $susantoUser = User::updateOrCreate(
            ['email' => 'susanto@scr.sch.id'],
            [
                'name' => 'Susanto',
                'password' => Hash::make('susanto123'),
                'role_id' => $pimpinanRole->id,
                'employee_id' => $susantoEmployee->id,
                'status' => 'active',
            ]
        );
        $susantoUser->syncRoles('Pimpinan');

        // 4. Phindo - Guru (dept Akademik, schedule Akademik)
        $phindoEmployee = Employee::updateOrCreate(
            ['nik' => 'GRU001'],
            [
                'name' => 'Phindo',
                'gender' => 'male',
                'birth_place' => 'Yogyakarta',
                'birth_date' => '1992-08-10',
                'phone' => '081234567803',
                'email' => 'phindo@scr.sch.id',
                'address' => 'Yogyakarta',
                'department_id' => $akademikDept?->id,
                'position_id' => $guruPosition?->id,
                'schedule_id' => $scheduleAkademik?->id,
                'is_active' => true,
            ]
        );

        $phindoUser = User::updateOrCreate(
            ['email' => 'phindo@scr.sch.id'],
            [
                'name' => 'Phindo',
                'password' => Hash::make('phindo123'),
                'role_id' => $guruRole->id,
                'employee_id' => $phindoEmployee->id,
                'status' => 'active',
            ]
        );
        $phindoUser->syncRoles('Guru');
    }
}

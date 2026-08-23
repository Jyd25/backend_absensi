<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY attendance_status ENUM('present', 'late', 'permission', 'leave', 'sick', 'absent', 'libur') NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM attendances WHERE attendance_status = 'libur'");
        DB::statement("ALTER TABLE attendances MODIFY attendance_status ENUM('present', 'late', 'permission', 'leave', 'sick', 'absent') NULL");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->time('saturday_start_time')->nullable()->after('working_days');
            $table->time('saturday_end_time')->nullable()->after('saturday_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropColumn(['saturday_start_time', 'saturday_end_time']);
        });
    }
};

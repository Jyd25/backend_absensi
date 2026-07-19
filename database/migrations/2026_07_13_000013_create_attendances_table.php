<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('attendance_locations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('work_schedules')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('attendance_type', ['check_in', 'check_out']);
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('distance', 8, 2)->nullable();
            $table->decimal('face_score', 5, 2)->nullable();
            $table->enum('location_status', ['inside_radius', 'outside_radius'])->nullable();
            $table->enum('face_status', ['matched', 'unmatched'])->nullable();
            $table->enum('attendance_status', ['present', 'late', 'permission', 'leave', 'sick', 'absent'])->nullable();
            $table->string('device')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('remarks')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('location_id');
            $table->index('attendance_status');
            $table->index('created_at');
            $table->index(['employee_id', 'created_at']);
            $table->index(['employee_id', 'attendance_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nik', 20)->unique();
            $table->string('name', 100);
            $table->enum('gender', ['male', 'female']);
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->text('address')->nullable();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('schedule_id')->constrained('work_schedules')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nik');
            $table->index('email');
            $table->index('department_id');
            $table->index('position_id');
            $table->index('schedule_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

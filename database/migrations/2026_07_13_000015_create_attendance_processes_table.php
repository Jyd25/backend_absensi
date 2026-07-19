<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_processes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('attendance_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('step');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->text('description')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_processes');
    }
};

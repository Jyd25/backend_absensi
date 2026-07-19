<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('attendance_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('action');
            $table->foreignId('performed_by')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('email');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('status', ['success', 'failed']);
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};

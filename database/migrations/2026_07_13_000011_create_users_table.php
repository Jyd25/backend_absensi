<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('role_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'locked', 'suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('role_id');
            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

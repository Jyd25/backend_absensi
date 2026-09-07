<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('recipient_email');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('format', 10)->default('pdf');
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_reports');
    }
};
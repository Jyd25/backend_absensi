<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spatie Laravel ActivityLog v5 menyimpan aktivitas ke tabel `activity_log`
 * dengan kolom `attribute_changes`. Migration lama project hanya membuat
 * tabel `activity_logs` (dipakai App\Models\ActivityLog), sehingga setiap
 * penulisan log aktivitas gagal: "Table 'activity_log' doesn't exist".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_log')) {
            return;
        }

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};

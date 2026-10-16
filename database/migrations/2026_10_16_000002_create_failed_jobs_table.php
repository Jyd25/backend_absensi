<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `failed_jobs` belum pernah dibuat oleh migration project.
 * Karena QUEUE_CONNECTION=database, queue worker butuh tabel ini untuk
 * mencatat job yang gagal — tanpa tabel ini worker error dan job hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};

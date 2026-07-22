<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE face_datasets SET image_path = NULL WHERE image_path IS NOT NULL');
        DB::statement('UPDATE face_update_requests SET image_path = NULL WHERE image_path IS NOT NULL');

        Schema::table('face_datasets', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('face_datasets', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });
    }
};

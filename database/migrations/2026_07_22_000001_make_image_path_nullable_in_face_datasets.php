<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_datasets', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('face_datasets', function (Blueprint $table) {
            $table->string('image_path')->nullable(false)->change();
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->string('image_path')->nullable(false)->change();
        });
    }
};

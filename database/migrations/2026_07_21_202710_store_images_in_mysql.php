<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->longText('photo_data')->nullable()->after('photo');
        });

        Schema::table('face_datasets', function (Blueprint $table) {
            $table->longText('image_data')->nullable()->after('image_path');
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->longText('image_data')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('photo_data');
        });

        Schema::table('face_datasets', function (Blueprint $table) {
            $table->dropColumn('image_data');
        });

        Schema::table('face_update_requests', function (Blueprint $table) {
            $table->dropColumn('image_data');
        });
    }
};

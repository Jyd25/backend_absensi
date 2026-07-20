<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_datasets', function (Blueprint $table) {
            $table->text('descriptor_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('face_datasets', function (Blueprint $table) {
            $table->string('descriptor_path')->nullable()->change();
        });
    }
};

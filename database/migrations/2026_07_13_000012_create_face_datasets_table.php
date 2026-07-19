<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_datasets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('descriptor_path')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_datasets');
    }
};

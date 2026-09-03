<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('checkout_latitude', 10, 8)->nullable()->after('longitude');
            $table->decimal('checkout_longitude', 11, 8)->nullable()->after('checkout_latitude');
            $table->decimal('checkout_distance', 12, 2)->nullable()->after('checkout_longitude');
            $table->string('checkout_face_status')->nullable()->after('checkout_distance');
            $table->decimal('checkout_face_score', 5, 2)->nullable()->after('checkout_face_status');
            $table->string('checkout_location_status')->nullable()->after('checkout_face_score');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'checkout_latitude',
                'checkout_longitude',
                'checkout_distance',
                'checkout_face_status',
                'checkout_face_score',
                'checkout_location_status',
            ]);
        });
    }
};

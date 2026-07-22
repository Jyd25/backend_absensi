<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->longText('checkin_photo_data')->nullable()->after('photo_data');
            $table->longText('checkout_photo_data')->nullable()->after('checkin_photo_data');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['checkin_photo_data', 'checkout_photo_data']);
        });
    }
};

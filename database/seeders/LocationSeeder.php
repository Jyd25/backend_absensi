<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceLocation::firstOrCreate(
            ['location_name' => 'Cahaya Rancamaya Islamic Boarding School'],
            [
                'latitude' => -6.6653167,
                'longitude' => 106.8354433,
                'radius' => 100,
                'address' => 'Jl. Rancamaya No.30, RT.01/RW.04, Bojongkerta, Kec. Bogor Sel., Kota Bogor, Jawa Barat 16139',
            ]
        );
    }
}

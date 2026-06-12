<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            [
                'station_name' => 'Halim',
                'city' => 'Jakarta',
                'code' => 'HLM'
            ],
            [
                'station_name' => 'Karawang',
                'city' => 'Karawang',
                'code' => 'KWG'
            ],
            [
                'station_name' => 'Padalarang',
                'city' => 'Bandung Barat',
                'code' => 'PDL'
            ],
            [
                'station_name' => 'Tegalluar Summarecon',
                'city' => 'Bandung',
                'code' => 'TLR'
            ],
        ];

        DB::table('stations')->insert($stations);
    }
}

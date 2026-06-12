<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Definisi Stasiun ID dan urutannya
        // Asumsi dari StationSeeder: 1=Halim, 2=Karawang, 3=Padalarang, 4=Tegalluar
        $stationsEastbound = [
            ['id' => 1, 'offset' => 0],   // Halim
            ['id' => 2, 'offset' => 20],  // Karawang
            ['id' => 3, 'offset' => 45],  // Padalarang
            ['id' => 4, 'offset' => 60],  // Tegalluar
        ];

        $stationsWestbound = [
            ['id' => 4, 'offset' => 0],   // Tegalluar
            ['id' => 3, 'offset' => 15],  // Padalarang
            ['id' => 2, 'offset' => 40],  // Karawang
            ['id' => 1, 'offset' => 60],  // Halim
        ];

        // 2. Definisi Kereta 
        // Asumsi dari TrainSeeder: 1=WT001, 2=WT002, 3=WT003, 4=WT004
        $trains = [
            ['id' => 1, 'direction' => 'east', 'time' => '07:00'],
            ['id' => 2, 'direction' => 'west', 'time' => '09:00'],
            ['id' => 3, 'direction' => 'east', 'time' => '13:00'],
            ['id' => 4, 'direction' => 'west', 'time' => '15:00'],
        ];

        $schedules = [];

        // Generate untuk hari ini sampai 3 hari ke depan
        for ($dayOffset = 0; $dayOffset <= 3; $dayOffset++) {
            $scheduleDate = Carbon::now()->addDays($dayOffset)->format('Y-m-d');
            
            foreach ($trains as $train) {
                $stations = ($train['direction'] === 'east') ? $stationsEastbound : $stationsWestbound;
                $startTime = Carbon::parse($scheduleDate . ' ' . $train['time']);

                // Kombinasi semua rute untuk kereta ini
                for ($i = 0; $i < count($stations) - 1; $i++) {
                    for ($j = $i + 1; $j < count($stations); $j++) {
                        
                        $departStation = $stations[$i];
                        $arriveStation = $stations[$j];

                        // Hitung waktu berangkat dan tiba dinamis
                        $departureTime = (clone $startTime)->addMinutes($departStation['offset']);
                        $arrivalTime   = (clone $startTime)->addMinutes($arriveStation['offset']);

                        // Menghitung jumlah segmen yang dilewati
                        $stationMultiplier = $j - $i;

                        // Harga dasar (Ekonomi).
                        $basePrice = 100000 + ($stationMultiplier * 50000); 

                        $schedules[] = [
                            'train_id'          => $train['id'],
                            'departure_station' => $departStation['id'],
                            'arrival_station'   => $arriveStation['id'],
                            'departure_time'    => $departureTime->format('Y-m-d H:i:s'),
                            'arrival_time'      => $arrivalTime->format('Y-m-d H:i:s'),
                            'price'             => $basePrice,
                        ];
                    }
                }
            }
        }

        // Kosongkan tabel schedules jika diperlukan (bisa uncomment)
        // DB::table('schedules')->truncate();

        // Masukkan seluruh kombinasi jadwal ke database
        DB::table('schedules')->insert($schedules);
    }
}

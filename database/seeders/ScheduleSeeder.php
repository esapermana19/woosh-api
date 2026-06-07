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
        $stations = [1, 2, 3, 4]; // 1: Halim, 2: Karawang, 3: Padalarang, 4: Tegalluar
        $trains = [1, 2, 3, 4];   // Train ID yang tersedia
        
        $schedules = [];
        
        // Buat jadwal untuk 7 hari ke depan (bisa disesuaikan)
        $startDate = Carbon::today();
        
        for ($day = 0; $day < 7; $day++) {
            $currentDate = $startDate->copy()->addDays($day);
            
            foreach ($stations as $departure) {
                foreach ($stations as $arrival) {
                    if ($departure === $arrival) continue;
                    
                    // Jadwal setiap 2 jam dari jam 06:00 sampai 22:00
                    for ($hour = 6; $hour <= 22; $hour += 2) {
                        $departureTime = $currentDate->copy()->setHour($hour)->setMinute(0)->setSecond(0);
                        
                        // Durasi perjalanan dinamis berdasarkan selisih stasiun (misal 15 menit per stasiun)
                        // Stasiun 1 ke 4 -> selisih 3 -> 45 menit
                        $distance = abs($arrival - $departure);
                        $durationMinutes = $distance * 15;
                        
                        $arrivalTime = $departureTime->copy()->addMinutes($durationMinutes);
                        
                        // Harga dinamis: misal base price 100rb + (50rb per jarak stasiun)
                        // 1 ke 2 -> 150.000, 1 ke 4 -> 250.000
                        $price = ($distance * 50000) + 100000;
                        
                        // Tentukan train_id agar bervariasi (rumus sederhana)
                        $trainIndex = ($hour / 2 + $departure + $day) % count($trains);
                        $trainId = $trains[$trainIndex];
                        
                        $schedules[] = [
                            'train_id'          => $trainId,
                            'departure_station' => $departure,
                            'arrival_station'   => $arrival,
                            'departure_time'    => $departureTime->format('Y-m-d H:i:s'),
                            'arrival_time'      => $arrivalTime->format('Y-m-d H:i:s'),
                            'price'             => $price,
                        ];
                    }
                }
            }
        }

        // Insert data ke tabel schedules dalam bentuk chunk
        foreach (array_chunk($schedules, 500) as $chunk) {
            DB::table('schedules')->insert($chunk);
        }
    }
}

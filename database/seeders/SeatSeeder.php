<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seats = [];
        $classes = [
            'economy' => 'E',
            'business' => 'B',
            'vip' => 'V'
        ];

        // 4 Kereta
        for ($trainId = 1; $trainId <= 4; $trainId++) {
            
            // 3 Gerbong (Kelas) per kereta
            foreach ($classes as $className => $classCode) {
                
                // Asumsi 10 Baris x 4 Kursi (A,B,C,D) per gerbong = 40 kursi
                for ($row = 1; $row <= 10; $row++) {
                    foreach (['A', 'B', 'C', 'D'] as $col) {
                        // Nomor kursi, misalnya E-1A, B-2C, V-10D
                        $seatNumber = $classCode . '-' . $row . $col;

                        $seats[] = [
                            'train_id' => $trainId,
                            'seat_number' => $seatNumber,
                            'class' => $className
                        ];
                    }
                }
            }
        }

        // Karena data bisa cukup banyak (480 data), gunakan chunk agar aman
        $chunks = array_chunk($seats, 100);
        foreach ($chunks as $chunk) {
            DB::table('seats')->insert($chunk);
        }
    }
}

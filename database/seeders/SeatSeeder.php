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
        // Daftar kelas untuk masing-masing gerbong (1 = vip, 2 = business, 3 = economy)
        $gerbongs = [
            1 => 'vip',
            2 => 'business',
            3 => 'economy',
        ];
        
        $columns = ['A', 'B', 'C', 'D'];
        $now = now();
        $seatsData = [];

        // Asumsi kita buatkan seeder untuk train_id 1 (bisa disesuaikan jika ingin generate banyak kereta)
        $trains = [1, 2, 3, 4]; 

        foreach ($trains as $train_id) {
            foreach ($gerbongs as $gerbongNumber => $class) {
                // Tiap gerbong punya 10 baris kursi (1 sampai 10)
                for ($row = 1; $row <= 10; $row++) {
                    foreach ($columns as $column) {
                        // Format penamaan kursi: G{Gerbong}-{Baris}{Kolom}
                        // Contoh: G1-1A, G1-10D, G2-3B, dll.
                        // Jika ingin mengikuti format lama tanpa baris, bisa diubah sesuai kebutuhan
                        $seat_number = "G{$gerbongNumber}-{$row}{$column}";
                        
                        $seatsData[] = [
                            'train_id'    => $train_id,
                            'seat_number' => $seat_number,
                            'class'       => $class,
                        ];
                    }
                }
            }
        }

        // Insert data ke tabel seats dalam bentuk chunk untuk performa yang baik
        foreach (array_chunk($seatsData, 500) as $chunk) {
            DB::table('seats')->insert($chunk);
        }
    }
}

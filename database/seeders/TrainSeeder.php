<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trains = [
            [
                'train_name' => 'Woosh Train 1',
                'train_code' => 'WT001',
                'total_seats' => 600
            ],
            [
                'train_name' => 'Woosh Train 2',
                'train_code' => 'WT002',
                'total_seats' => 600
            ],
            [
                'train_name' => 'Woosh Train 3',
                'train_code' => 'WT003',
                'total_seats' => 600
            ],
            [
                'train_name' => 'Woosh Train 4',
                'train_code' => 'WT004',
                'total_seats' => 600
            ],
        ];

        DB::table('trains')->insert($trains);
    }
}

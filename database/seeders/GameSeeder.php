<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'name'                       => 'Mobile',
                'price'                      => 1500,
                'image_path'                 => '/assets/imgs/mobile1.png',
                'price_to_play'              => 1,
                'minimum_amount_for_winning'  => 1650,
                'minimum_deposit'            => 10,
                'target_amount'              => 1650,
                'current_amount'             => 0,
                'draw_number'                => '1',
                'is_enabled'                 => true,
            ],
            [
                'name'                       => 'Bike & Electronics',
                'price'                      => 15000,
                'image_path'                 => '/assets/imgs/be1.png',
                'price_to_play'              => 4,
                'minimum_amount_for_winning'  => 16500,
                'minimum_deposit'            => 25,
                'target_amount'              => 16500,
                'current_amount'             => 0,
                'draw_number'                => '1',
                'is_enabled'                 => true,
            ],
            [
                'name'                       => 'SUV',
                'price'                      => 50000,
                'image_path'                 => '/assets/imgs/suv1.png',
                'price_to_play'              => 8,
                'minimum_amount_for_winning'  => 55000,
                'minimum_deposit'            => 30,
                'target_amount'              => 55000,
                'current_amount'             => 0,
                'draw_number'                => '1',
                'is_enabled'                 => true,
            ],
            [
                'name'                       => 'Muscle Car',
                'price'                      => 150000,
                'image_path'                 => '/assets/imgs/muscle-car1.png',
                'price_to_play'              => 24,
                'minimum_amount_for_winning'  => 165000,
                'minimum_deposit'            => 40,
                'target_amount'              => 165000,
                'current_amount'             => 0,
                'draw_number'                => '1',
                'is_enabled'                 => true,
            ],
            [
                'name'                       => 'Super Cash Prize',
                'price'                      => 200000,
                'image_path'                 => '/assets/imgs/super-car1.png',
                'price_to_play'              => 32,
                'minimum_amount_for_winning'  => 220000,
                'minimum_deposit'            => 50,
                'target_amount'              => 220000,
                'current_amount'             => 0,
                'draw_number'                => '1',
                'is_enabled'                 => true,
            ],
        ];

        foreach ($games as $game) {
            \App\Models\Game::updateOrCreate(
                ['name' => $game['name']],
                $game,
            );
        }
    }
}

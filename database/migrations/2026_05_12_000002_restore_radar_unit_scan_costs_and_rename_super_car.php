<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Correct the previous wallet-dollar interpretation back to Radar Cash units.
     */
    public function up(): void
    {
        DB::table('games')->where('name', 'Super Cash Prize')->update(['name' => 'Super Car']);

        $games = [
            'Mobile' => [
                'price' => 1800.00,
                'target_amount' => 1800.00,
                'price_to_play' => 1.00,
                'minimum_deposit' => 10.00,
                'minimum_amount_for_winning' => 1800,
            ],
            'Bike & Electronics' => [
                'price' => 18000.00,
                'target_amount' => 18000.00,
                'price_to_play' => 4.00,
                'minimum_deposit' => 25.00,
                'minimum_amount_for_winning' => 18000,
            ],
            'SUV' => [
                'target_amount' => 55000.00,
                'price_to_play' => 8.00,
                'minimum_deposit' => 30.00,
                'minimum_amount_for_winning' => 55000,
            ],
            'Muscle Car' => [
                'target_amount' => 165000.00,
                'price_to_play' => 24.00,
                'minimum_deposit' => 40.00,
                'minimum_amount_for_winning' => 165000,
            ],
            'Super Car' => [
                'target_amount' => 220000.00,
                'price_to_play' => 32.00,
                'minimum_deposit' => 50.00,
                'minimum_amount_for_winning' => 220000,
            ],
        ];

        foreach ($games as $name => $values) {
            DB::table('games')->where('name', $name)->update($values);
        }
    }

    public function down(): void
    {
        $games = [
            'Mobile' => ['price_to_play' => 0.25],
            'Bike & Electronics' => ['price_to_play' => 4.00],
            'SUV' => ['price_to_play' => 16.00],
            'Muscle Car' => ['price_to_play' => 144.00],
            'Super Car' => ['price_to_play' => 256.00],
        ];

        foreach ($games as $name => $values) {
            DB::table('games')->where('name', $name)->update($values);
        }

        DB::table('games')->where('name', 'Super Car')->update(['name' => 'Super Cash Prize']);
    }
};

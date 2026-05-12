<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align prize targets and per-scan Radar Cash debits with the RD:Leb algorithm.
     */
    public function up(): void
    {
        $games = [
            'Mobile' => [
                'price' => 1800.00,
                'target_amount' => 1800.00,
                'price_to_play' => 1.00,
                'minimum_amount_for_winning' => 1800,
            ],
            'Bike & Electronics' => [
                'price' => 18000.00,
                'target_amount' => 18000.00,
                'price_to_play' => 4.00,
                'minimum_amount_for_winning' => 18000,
            ],
            'SUV' => [
                'target_amount' => 55000.00,
                'price_to_play' => 8.00,
                'minimum_amount_for_winning' => 55000,
            ],
            'Muscle Car' => [
                'target_amount' => 165000.00,
                'price_to_play' => 24.00,
                'minimum_amount_for_winning' => 165000,
            ],
            'Super Car' => [
                'target_amount' => 220000.00,
                'price_to_play' => 32.00,
                'minimum_amount_for_winning' => 220000,
            ],
        ];

        DB::table('games')->where('name', 'Super Cash Prize')->update(['name' => 'Super Car']);

        foreach ($games as $name => $values) {
            DB::table('games')->where('name', $name)->update($values);
        }
    }

    public function down(): void
    {
        $games = [
            'Mobile' => [
                'price' => 1500.00,
                'target_amount' => 1650.00,
                'price_to_play' => 1.00,
                'minimum_amount_for_winning' => 1650,
            ],
            'Bike & Electronics' => [
                'price' => 15000.00,
                'target_amount' => 16500.00,
                'price_to_play' => 4.00,
                'minimum_amount_for_winning' => 16500,
            ],
            'SUV' => [
                'target_amount' => 55000.00,
                'price_to_play' => 8.00,
                'minimum_amount_for_winning' => 55000,
            ],
            'Muscle Car' => [
                'target_amount' => 165000.00,
                'price_to_play' => 24.00,
                'minimum_amount_for_winning' => 165000,
            ],
            'Super Car' => [
                'target_amount' => 220000.00,
                'price_to_play' => 32.00,
                'minimum_amount_for_winning' => 220000,
            ],
        ];

        foreach ($games as $name => $values) {
            DB::table('games')->where('name', $name)->update($values);
        }

        DB::table('games')->where('name', 'Super Car')->update(['name' => 'Super Cash Prize']);
    }
};

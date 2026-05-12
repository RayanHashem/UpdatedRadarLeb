<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Per-scan cost is Radar Cash units per game.
     * Matches product rules: 1, 4, 8, 24, 32.
     */
    public function up(): void
    {
        $perScanRadarByName = [
            'Mobile' => 1,
            'Bike & Electronics' => 4,
            'SUV' => 8,
            'Muscle Car' => 24,
            'Super Cash Prize' => 32,
        ];

        foreach ($perScanRadarByName as $name => $radars) {
            DB::table('games')->where('name', $name)->update(['price_to_play' => $radars]);
        }
    }

    public function down(): void
    {
        $oldDollarPerScanByName = [
            'Mobile' => 0.25,
            'Bike & Electronics' => 4.00,
            'SUV' => 16.00,
            'Muscle Car' => 144.00,
            'Super Cash Prize' => 256.00,
        ];

        foreach ($oldDollarPerScanByName as $name => $amount) {
            DB::table('games')->where('name', $name)->update(['price_to_play' => $amount]);
        }
    }
};

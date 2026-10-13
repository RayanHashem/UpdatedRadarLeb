<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mobile prize is now shown as $2,000 (was $1,500): its price, target and
     * win threshold go from 1800 to 2400 Radar Cash (1 Radar Cash = $1).
     * Scan cost (1) and minimum deposit (10) are unchanged.
     */
    public function up(): void
    {
        DB::table('games')->where('name', 'Mobile')->update([
            'price' => 2400.00,
            'target_amount' => 2400.00,
            'minimum_amount_for_winning' => 2400,
        ]);
    }

    public function down(): void
    {
        DB::table('games')->where('name', 'Mobile')->update([
            'price' => 1800.00,
            'target_amount' => 1800.00,
            'minimum_amount_for_winning' => 1800,
        ]);
    }
};

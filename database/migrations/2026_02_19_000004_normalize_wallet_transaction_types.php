<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Normalize type values to lowercase snake_case.
     */
    public function up(): void
    {
        // Normalize to lowercase snake_case
        $normalizations = [
            'Scan' => 'debit',
            'SCAN' => 'debit',
            'scan' => 'debit',
            'Play' => 'debit',
            'PLAY' => 'debit',
            'play' => 'debit',
            'Spent' => 'debit',
            'SPENT' => 'spent',
            'spent' => 'debit', // Map 'spent' to 'debit' for consistency
            'Top_up' => 'topup',
            'TOP_UP' => 'topup',
            'top_up' => 'topup',
            'TopUp' => 'topup',
            'Topup' => 'topup',
            'TOPUP' => 'topup',
            'Reward' => 'reward',
            'REWARD' => 'reward',
            'reward' => 'reward',
            'Adjustment' => 'adjustment',
            'ADJUSTMENT' => 'adjustment',
            'Refund' => 'refund',
            'REFUND' => 'refund',
        ];

        foreach ($normalizations as $oldValue => $newValue) {
            DB::table('wallet_transactions')
                ->where('type', $oldValue)
                ->update(['type' => $newValue]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse normalization
    }
};

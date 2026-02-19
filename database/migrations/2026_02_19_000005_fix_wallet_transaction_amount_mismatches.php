<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix amount mismatches: ensure wallet_transactions.amount = scans.cost for scan-related transactions.
     */
    public function up(): void
    {
        // Update wallet_transactions.amount to match scans.cost where they differ
        // (Allow 1 cent tolerance for rounding differences)
        DB::statement("
            UPDATE wallet_transactions wt
            SET amount = s.cost
            FROM scans s
            WHERE wt.scan_id = s.id
            AND ABS(wt.amount - s.cost) > 0.01
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse amount corrections
    }
};

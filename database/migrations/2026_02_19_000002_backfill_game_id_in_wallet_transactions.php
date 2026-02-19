<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill game_id from scans table where scan_id exists.
     */
    public function up(): void
    {
        // Backfill from scans: if wallet_transaction has scan_id, use scan's game_id
        // PostgreSQL-compatible UPDATE with JOIN
        DB::statement("
            UPDATE wallet_transactions wt
            SET game_id = s.game_id
            FROM scans s
            WHERE wt.scan_id = s.id
            AND wt.game_id IS NULL
            AND s.game_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: we can't reliably reverse the backfill
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill wallet_transactions.game_id from the related scan.
     *
     * Originally written as Postgres-only `UPDATE ... FROM` join syntax, which
     * fails on SQLite. Rewritten as a SQL-standard correlated subquery that
     * runs on SQLite, MySQL, and Postgres.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE wallet_transactions
            SET game_id = (
                SELECT scans.game_id
                FROM scans
                WHERE scans.id = wallet_transactions.scan_id
            )
            WHERE game_id IS NULL
              AND scan_id IS NOT NULL
              AND EXISTS (
                  SELECT 1
                  FROM scans
                  WHERE scans.id = wallet_transactions.scan_id
                    AND scans.game_id IS NOT NULL
              )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: we can't reliably reverse the backfill.
    }
};

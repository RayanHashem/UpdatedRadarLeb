<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix amount mismatches: ensure wallet_transactions.amount = scans.cost for
     * scan-related transactions (allowing 1¢ tolerance for rounding).
     *
     * Originally used Postgres-only `UPDATE ... FROM` join syntax. Rewritten as a
     * SQL-standard correlated subquery so it runs on SQLite, MySQL, and Postgres.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE wallet_transactions
            SET amount = (
                SELECT scans.cost
                FROM scans
                WHERE scans.id = wallet_transactions.scan_id
            )
            WHERE scan_id IS NOT NULL
              AND EXISTS (
                  SELECT 1
                  FROM scans
                  WHERE scans.id = wallet_transactions.scan_id
                    AND ABS(wallet_transactions.amount - scans.cost) > 0.01
              )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse amount corrections.
    }
};

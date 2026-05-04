<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique partial index on wallet_transactions.scan_id (NOT NULL only),
     * after deduping any existing rows that share a scan_id.
     *
     * Originally used Postgres-only `DELETE ... USING ...` syntax. Rewritten as a
     * two-step delete (collect duplicate ids, then delete by id) which is portable
     * across SQLite, MySQL, and Postgres. The partial unique index uses
     * `WHERE scan_id IS NOT NULL`, supported by both SQLite and Postgres
     * (which is what this project targets).
     */
    public function up(): void
    {
        // Step 1: find duplicate ids — keep the smallest id per scan_id, mark the rest for deletion.
        $duplicateIds = DB::table('wallet_transactions as wt1')
            ->join('wallet_transactions as wt2', function ($join) {
                $join->on('wt1.scan_id', '=', 'wt2.scan_id')
                    ->whereColumn('wt1.id', '>', 'wt2.id');
            })
            ->whereNotNull('wt1.scan_id')
            ->pluck('wt1.id')
            ->unique()
            ->all();

        if (! empty($duplicateIds)) {
            DB::table('wallet_transactions')->whereIn('id', $duplicateIds)->delete();
        }

        // Step 2: partial unique index. Both SQLite and Postgres support partial indexes.
        DB::statement('
            CREATE UNIQUE INDEX wallet_transactions_scan_id_unique
            ON wallet_transactions (scan_id)
            WHERE scan_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS wallet_transactions_scan_id_unique');
    }
};

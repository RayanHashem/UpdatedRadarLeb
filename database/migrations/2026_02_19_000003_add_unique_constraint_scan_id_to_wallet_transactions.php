<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add unique constraint on scan_id to prevent duplicate transactions for the same scan.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // First, remove any duplicates (keep the oldest transaction per scan_id)
            \DB::statement("
                DELETE FROM wallet_transactions wt1
                USING wallet_transactions wt2
                WHERE wt1.scan_id IS NOT NULL
                AND wt1.scan_id = wt2.scan_id
                AND wt1.id > wt2.id
            ");
        });

        // Add unique partial index for non-null scan_id values
        // PostgreSQL allows unique indexes on nullable columns (NULLs don't violate uniqueness)
        \DB::statement("
            CREATE UNIQUE INDEX wallet_transactions_scan_id_unique 
            ON wallet_transactions (scan_id) 
            WHERE scan_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("DROP INDEX IF EXISTS wallet_transactions_scan_id_unique");
    }
};

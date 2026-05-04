<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert games.price_to_play from INTEGER to DECIMAL(12,2).
     *
     * Originally written with raw Postgres-only SQL (`ALTER COLUMN ... TYPE ... USING ...`),
     * which broke local SQLite dev. Rewritten to use Laravel's Schema builder so the same
     * migration runs on SQLite, MySQL, and Postgres.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->decimal('price_to_play', 12, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->integer('price_to_play')->default(1)->change();
        });
    }
};

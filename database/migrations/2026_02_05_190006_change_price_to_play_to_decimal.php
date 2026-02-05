<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL requires explicit type cast
        DB::statement('ALTER TABLE games ALTER COLUMN price_to_play TYPE DECIMAL(12,2) USING price_to_play::DECIMAL(12,2)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE games ALTER COLUMN price_to_play TYPE INTEGER USING price_to_play::INTEGER');
    }
};

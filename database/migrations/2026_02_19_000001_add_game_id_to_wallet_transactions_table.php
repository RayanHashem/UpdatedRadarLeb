<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('game_id')
                ->nullable()
                ->after('user_id')
                ->constrained('games')
                ->onDelete('restrict');
            
            // Indexes for efficient filtering
            $table->index(['user_id', 'game_id'], 'wallet_transactions_user_game_index');
            $table->index(['game_id', 'created_at'], 'wallet_transactions_game_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropIndex('wallet_transactions_user_game_index');
            $table->dropIndex('wallet_transactions_game_created_index');
            $table->dropColumn('game_id');
        });
    }
};

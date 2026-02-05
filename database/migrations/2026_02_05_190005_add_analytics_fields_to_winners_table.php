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
        Schema::table('winners', function (Blueprint $table) {
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->after('id');
            $table->foreignId('game_id')->nullable()->constrained('games')->after('winner_user_id');
            $table->foreignId('scan_id')->nullable()->constrained('scans')->after('game_id');
            $table->integer('draw_number')->nullable()->after('scan_id');
            $table->timestamp('won_at')->nullable()->after('draw_number');
        });

        // Add indexes separately to avoid conflicts
        Schema::table('winners', function (Blueprint $table) {
            $table->index('winner_user_id', 'winners_winner_user_id_index');
            $table->index('game_id', 'winners_game_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('winners', function (Blueprint $table) {
            $table->dropForeign(['winner_user_id']);
            $table->dropForeign(['game_id']);
            $table->dropForeign(['scan_id']);
            $table->dropIndex('winners_winner_user_id_index');
            $table->dropIndex('winners_game_id_index');
            $table->dropColumn(['winner_user_id', 'game_id', 'scan_id', 'draw_number', 'won_at']);
        });
    }
};

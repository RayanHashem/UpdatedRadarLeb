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
        Schema::create('game_user_stats', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('game_id')->constrained('games');
            $t->unsignedTinyInteger('current_radar')->default(0);   // 0-6
            $t->unsignedInteger('failed_scans')->default(0);
            $t->unsignedInteger('successful_scans')->default(0);
            $t->decimal('amount_spent', 12, 2)->default(0);
            $t->unsignedInteger('fails_in_level')->default(0);
            $t->timestamps();

            // Indexes for performance
            $t->index('user_id', 'game_user_stats_user_id_index');
            $t->index('game_id', 'game_user_stats_game_id_index');

            // Prevent duplicate user-game stat rows
            $t->unique(['user_id', 'game_id'], 'game_user_stats_user_game_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_user_stats');
    }
};

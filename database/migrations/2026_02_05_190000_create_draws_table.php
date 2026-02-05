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
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->integer('draw_number');
            $table->string('status')->default('open'); // open, closed
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('winner_user_id')->nullable()->constrained('users');
            $table->foreignId('winning_scan_id')->nullable()->constrained('scans');
            $table->timestamps();

            // Indexes
            $table->index('game_id', 'draws_game_id_index');
            $table->index('status', 'draws_status_index');
            $table->index(['game_id', 'draw_number'], 'draws_game_draw_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};

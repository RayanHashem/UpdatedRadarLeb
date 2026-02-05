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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->boolean('success')->default(false);
            $table->unsignedTinyInteger('radar_level')->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id', 'scans_user_id_index');
            $table->index('game_id', 'scans_game_id_index');
            $table->index('created_at', 'scans_created_at_index');
            $table->index(['game_id', 'success', 'created_at'], 'scans_game_success_created_index');
            $table->index(['user_id', 'created_at'], 'scans_user_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};

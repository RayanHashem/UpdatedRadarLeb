<?php

namespace Tests\Feature\Api;

use App\Models\Game;
use App\Models\User;
use App\Models\Winner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPA-facing JSON endpoints under /games, /me, /me/game, /winners.
 * They aren't separate "API" routes (they live in routes/auth.php) but
 * structurally they ARE the API the Vue dashboard calls into.
 */
class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_games_index_requires_auth(): void
    {
        Game::factory()->count(2)->create();

        $this->getJson('/games')->assertStatus(401);
    }

    public function test_games_index_returns_all_games_with_progress(): void
    {
        $user = User::factory()->create();
        Game::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/games');

        $response->assertOk();
        $this->assertCount(3, $response->json());
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'price', 'image', 'is_enabled', 'progress' => ['radar_level', 'failed_scans', 'successful', 'amount_spent', 'can_win_final']],
        ]);
    }

    public function test_me_returns_current_user_summary(): void
    {
        $user = User::factory()->withBalance(42.50)->create();
        $game = Game::factory()->create();
        $user->forceFill(['game_id' => $game->id])->save();

        $response = $this->actingAs($user)->getJson('/me');

        $response->assertOk();
        $response->assertJson([
            'id'             => $user->id,
            'game_id'        => $game->id,
            'wallet_balance' => 42.5,
        ]);
    }

    public function test_me_endpoint_requires_auth(): void
    {
        $this->getJson('/me')->assertStatus(401);
    }

    public function test_user_can_select_a_game(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $response = $this->actingAs($user)->postJson('/me/game', ['game_id' => $game->id]);

        $response->assertOk()->assertJson(['message' => 'saved']);
        $this->assertSame($game->id, (int) $user->fresh()->game_id);
    }

    public function test_user_cannot_select_a_disabled_game(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->disabled()->create();

        $response = $this->actingAs($user)->postJson('/me/game', ['game_id' => $game->id]);

        $response->assertStatus(403);
    }

    public function test_user_can_clear_their_game_selection(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();
        $user->forceFill(['game_id' => $game->id])->save();

        $response = $this->actingAs($user)->postJson('/me/game', ['game_id' => null]);

        $response->assertOk()->assertJson(['message' => 'cleared']);
        $this->assertNull($user->fresh()->game_id);
    }

    public function test_winners_index_requires_auth(): void
    {
        Winner::factory()->count(3)->create();

        $this->getJson('/winners')->assertStatus(401);
    }

    public function test_winners_index_is_paginated_and_bounded(): void
    {
        $user = User::factory()->create();
        Winner::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson('/winners?per_page=10');

        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'game_name', 'user_name']], 'current_page', 'per_page', 'total']);
        $this->assertCount(10, $response->json('data'));
        $this->assertSame(25, $response->json('total'));
    }

    public function test_winners_per_page_is_capped(): void
    {
        $user = User::factory()->create();
        Winner::factory()->count(60)->create();

        // Request 1000 — controller hard-caps at 50 to prevent giant payloads.
        $response = $this->actingAs($user)->getJson('/winners?per_page=1000');

        $response->assertOk();
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }
}

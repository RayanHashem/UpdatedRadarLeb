<?php

namespace Tests\Unit\Actions\Game;

use App\Actions\Game\CheckWinEligibility;
use App\Models\Game;
use App\Models\GameUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direct tests for the leaderboard + threshold gate that decides whether a
 * user can win the final draw on a game.
 *
 * Two rules, AND'd together:
 *   1. The user must be in the top 3 spenders for that game.
 *   2. The game's total amount_spent must be >= minimum_amount_for_winning.
 */
class CheckWinEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_in_top3_with_pool_above_threshold_is_eligible(): void
    {
        $game = Game::factory()->create(['minimum_amount_for_winning' => 100]);

        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        GameUserStat::create(['user_id' => $alice->id, 'game_id' => $game->id, 'amount_spent' => 80]);
        GameUserStat::create(['user_id' => $bob->id,   'game_id' => $game->id, 'amount_spent' => 30]);
        // Total spent = 110; threshold = 100. Both users are in top 3 (only 2 exist).

        $action = app(CheckWinEligibility::class);

        $this->assertTrue($action($game, $alice));
        $this->assertTrue($action($game, $bob));
    }

    public function test_user_in_top3_but_pool_under_threshold_is_not_eligible(): void
    {
        $game = Game::factory()->create(['minimum_amount_for_winning' => 1000]);
        $alice = User::factory()->create();

        GameUserStat::create(['user_id' => $alice->id, 'game_id' => $game->id, 'amount_spent' => 50]);
        // Total = 50; threshold = 1000. Pool gate fails.

        $this->assertFalse(app(CheckWinEligibility::class)($game, $alice));
    }

    public function test_user_outside_top3_is_never_eligible(): void
    {
        $game = Game::factory()->create(['minimum_amount_for_winning' => 1]);

        $top1 = User::factory()->create();
        $top2 = User::factory()->create();
        $top3 = User::factory()->create();
        $also = User::factory()->create();

        // Pool is huge so the threshold gate will pass — the only thing
        // protecting $also is the top-3 check.
        GameUserStat::create(['user_id' => $top1->id, 'game_id' => $game->id, 'amount_spent' => 100]);
        GameUserStat::create(['user_id' => $top2->id, 'game_id' => $game->id, 'amount_spent' => 50]);
        GameUserStat::create(['user_id' => $top3->id, 'game_id' => $game->id, 'amount_spent' => 25]);
        GameUserStat::create(['user_id' => $also->id, 'game_id' => $game->id, 'amount_spent' => 5]);

        $action = app(CheckWinEligibility::class);

        $this->assertTrue($action($game, $top1));
        $this->assertTrue($action($game, $top2));
        $this->assertTrue($action($game, $top3));
        $this->assertFalse($action($game, $also));
    }

    public function test_user_with_no_stat_row_is_not_eligible(): void
    {
        $game = Game::factory()->create(['minimum_amount_for_winning' => 1]);
        $stranger = User::factory()->create();

        // Other users have stats, but $stranger has never played this game.
        $top1 = User::factory()->create();
        GameUserStat::create(['user_id' => $top1->id, 'game_id' => $game->id, 'amount_spent' => 50]);

        $this->assertFalse(app(CheckWinEligibility::class)($game, $stranger));
    }
}

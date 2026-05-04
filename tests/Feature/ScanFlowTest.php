<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Scan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money-path tests for POST /scan/{game} and App\Actions\Game\AttemptScan.
 *
 * The success/failure outcome of a single scan is non-deterministic by
 * design (random_int decides). What is deterministic and worth testing:
 *   - the wallet decrements by the game's price_to_play
 *   - exactly one Scan row is created
 *   - exactly one WalletTransaction row is created (firstOrCreate on scan_id)
 *   - the transaction's amount matches the scan's cost (1¢ tolerance hook)
 *   - failure cases short-circuit and leave the DB unchanged
 */
class ScanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_scan_game_they_can_afford(): void
    {
        $game = Game::factory()->pricedAt(4)->create();
        $user = User::factory()->withBalance(20)->create();

        $response = $this->actingAs($user)->postJson("/scan/{$game->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'antenna_detected',
            'progress' => ['radar_level', 'failed_scans', 'successful', 'amount_spent', 'can_win_final'],
            'wallet',
        ]);

        $this->assertEqualsWithDelta(16.0, (float) $user->fresh()->wallet_balance, 0.001);
        $this->assertSame(1, Scan::where('user_id', $user->id)->count());
        $this->assertSame(1, WalletTransaction::where('user_id', $user->id)->count());
    }

    public function test_scan_response_echoes_authoritative_wallet_balance(): void
    {
        $game = Game::factory()->pricedAt(8)->create();
        $user = User::factory()->withBalance(50)->create();

        $response = $this->actingAs($user)->postJson("/scan/{$game->id}");

        $response->assertOk();
        $this->assertEqualsWithDelta(42.0, (float) $response->json('wallet'), 0.001);
    }

    public function test_wallet_transaction_amount_matches_scan_cost(): void
    {
        $game = Game::factory()->pricedAt(24)->create();
        $user = User::factory()->withBalance(100)->create();

        $this->actingAs($user)->postJson("/scan/{$game->id}")->assertOk();

        $scan = Scan::where('user_id', $user->id)->sole();
        $tx   = WalletTransaction::where('scan_id', $scan->id)->sole();

        $this->assertSame('debit', $tx->type);
        $this->assertEqualsWithDelta((float) $scan->cost, (float) $tx->amount, 0.001);
        $this->assertSame($game->id, (int) $tx->game_id);
    }

    public function test_user_cannot_scan_when_balance_is_insufficient(): void
    {
        $game = Game::factory()->pricedAt(32)->create();
        $user = User::factory()->withBalance(5)->create();

        $response = $this->actingAs($user)->postJson("/scan/{$game->id}");

        $response->assertStatus(402);
        // PHP's json_encode serializes whole-number floats as ints (5.0 → 5),
        // so use a delta-aware float compare instead of strict assertJsonPath.
        $this->assertEqualsWithDelta(5.0, (float) $response->json('wallet'), 0.001);

        $this->assertSame(0, Scan::where('user_id', $user->id)->count());
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertEqualsWithDelta(5.0, (float) $user->fresh()->wallet_balance, 0.001);
    }

    public function test_scan_against_disabled_game_is_rejected(): void
    {
        $game = Game::factory()->pricedAt(4)->disabled()->create();
        $user = User::factory()->withBalance(50)->create();

        $response = $this->actingAs($user)->postJson("/scan/{$game->id}");

        $response->assertStatus(403);
        $this->assertEqualsWithDelta(50.0, (float) $user->fresh()->wallet_balance, 0.001);
        $this->assertSame(0, Scan::count());
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_scan_is_rejected_when_radar_offline_globally(): void
    {
        // updateOrCreate — Filament boot might already have inserted a row
        // for this setting, and the column has a UNIQUE index on `key`.
        SystemSetting::updateOrCreate(['key' => 'scans_enabled'], ['value' => '0']);

        $game = Game::factory()->pricedAt(1)->create();
        $user = User::factory()->withBalance(50)->create();

        $response = $this->actingAs($user)->postJson("/scan/{$game->id}");

        $response->assertStatus(423);
        $this->assertSame(0, Scan::count());
    }

    public function test_unauthenticated_user_cannot_scan(): void
    {
        $game = Game::factory()->create();

        $response = $this->postJson("/scan/{$game->id}");

        $response->assertStatus(401);
        $this->assertSame(0, Scan::count());
    }

    public function test_scan_writes_are_atomic_one_scan_one_transaction(): void
    {
        // Regression for the "I have to refresh the page" bug: even after
        // many sequential scans, every scan must produce exactly one
        // matching wallet_transaction (firstOrCreate guard on scan_id).
        $game = Game::factory()->pricedAt(1)->create();
        $user = User::factory()->withBalance(10)->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson("/scan/{$game->id}")->assertOk();
        }

        $scans = Scan::where('user_id', $user->id)->count();
        $txs   = WalletTransaction::where('user_id', $user->id)->count();

        $this->assertSame(5, $scans);
        $this->assertSame(5, $txs, 'one transaction per scan invariant broken');
        $this->assertEqualsWithDelta(5.0, (float) $user->fresh()->wallet_balance, 0.001);
    }
}

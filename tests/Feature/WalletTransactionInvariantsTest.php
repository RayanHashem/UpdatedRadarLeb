<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Scan;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Direct unit-shaped tests against WalletTransaction::boot() invariants.
 * These never go through HTTP — they exercise the model's saving hook so
 * the safety net stays in place even if every controller is rewritten.
 */
class WalletTransactionInvariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_is_lowercased_on_save(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $tx = WalletTransaction::create([
            'user_id'       => $user->id,
            'game_id'       => $game->id,
            'type'          => 'TopUp',
            'amount'        => 50,
            'balance_after' => 50,
        ]);

        $this->assertSame('topup', $tx->fresh()->type);
    }

    public function test_amount_must_match_scan_cost_within_tolerance(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $scan = Scan::factory()->for($user)->for($game)->pricedAt(8)->create();

        $this->expectException(InvalidArgumentException::class);

        WalletTransaction::create([
            'user_id'       => $user->id,
            'game_id'       => $game->id,
            'scan_id'       => $scan->id,
            'type'          => 'debit',
            'amount'        => 5,            // doesn't match scan->cost = 8
            'balance_after' => 0,
        ]);
    }

    public function test_amount_within_one_cent_of_scan_cost_is_accepted(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $scan = Scan::factory()->for($user)->for($game)->pricedAt(8)->create();

        $tx = WalletTransaction::create([
            'user_id'       => $user->id,
            'game_id'       => $game->id,
            'scan_id'       => $scan->id,
            'type'          => 'debit',
            'amount'        => 8.005,        // within 1¢ tolerance
            'balance_after' => 0,
        ]);

        $this->assertNotNull($tx->id);
    }

    public function test_topup_can_have_null_game_id(): void
    {
        $user = User::factory()->create();

        $tx = WalletTransaction::create([
            'user_id'       => $user->id,
            'game_id'       => null,
            'scan_id'       => null,
            'type'          => 'topup',
            'amount'        => 100,
            'balance_after' => 100,
        ]);

        $this->assertSame('topup', $tx->fresh()->type);
        $this->assertNull($tx->fresh()->game_id);
    }
}

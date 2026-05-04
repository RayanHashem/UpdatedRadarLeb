<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the security lockdown that swapped `$guarded = []`
 * for explicit `$fillable` allowlists on User, WalletTransaction, Game.
 *
 * If a future contributor reverts to `$guarded = []` or accidentally adds
 * `wallet_balance`/`role`/`current_amount` back to $fillable, these tests
 * fail loudly.
 */
class MassAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_wallet_balance_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->withBalance(10)->create();

        $user->update([
            'name'           => 'Updated',
            'wallet_balance' => 999_999,    // attacker payload
        ]);

        $this->assertSame('Updated', $user->fresh()->name);
        $this->assertEqualsWithDelta(10.0, (float) $user->fresh()->wallet_balance, 0.001);
    }

    public function test_user_role_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();

        $user->update([
            'name' => 'Updated',
            'role' => 'super_admin',         // privilege-escalation payload
        ]);

        $this->assertNull($user->fresh()->role);
    }

    public function test_user_email_verified_at_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->unverified()->create();

        $user->update([
            'email_verified_at' => now(),
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_game_current_amount_cannot_be_mass_assigned(): void
    {
        $game = Game::factory()->create(['current_amount' => 0]);

        $game->update([
            'name'           => 'Renamed',
            'current_amount' => 9_000_000,   // pool-tampering payload
        ]);

        $this->assertSame('Renamed', $game->fresh()->name);
        $this->assertEqualsWithDelta(0.0, (float) $game->fresh()->current_amount, 0.001);
    }

    public function test_wallet_transaction_only_allows_listed_fields(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $tx = WalletTransaction::create([
            'user_id'           => $user->id,
            'game_id'           => $game->id,
            'type'              => 'topup',
            'amount'            => 100,
            'balance_after'     => 100,
            'unknown_evil_col'  => 'should be ignored, never persisted',
        ]);

        // Sanity: the legit attributes wrote through.
        $this->assertSame('topup', $tx->fresh()->type);
        $this->assertEqualsWithDelta(100.0, (float) $tx->fresh()->amount, 0.001);
    }
}

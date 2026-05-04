<?php

namespace Tests\Unit\Actions\Wallet;

use App\Actions\Wallet\TopOffWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Direct tests for the admin top-off action. Filament's UserResource calls
 * this on every "Top up" submission; tests guarantee the atomic credit +
 * audit trail behavior independent of the admin UI.
 */
class TopOffWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_user_wallet_and_writes_topup_transaction(): void
    {
        $user = User::factory()->withBalance(50)->create();

        $tx = app(TopOffWallet::class)($user, 25.50);

        $this->assertEqualsWithDelta(75.50, (float) $user->fresh()->wallet_balance, 0.001);

        $this->assertSame('topup', $tx->type);
        $this->assertNull($tx->game_id);
        $this->assertNull($tx->scan_id);
        $this->assertEqualsWithDelta(25.50, (float) $tx->amount, 0.001);
        $this->assertEqualsWithDelta(75.50, (float) $tx->balance_after, 0.001);
    }

    public function test_each_topup_creates_exactly_one_transaction_row(): void
    {
        $user = User::factory()->withBalance(0)->create();

        app(TopOffWallet::class)($user, 10);
        app(TopOffWallet::class)($user, 20);
        app(TopOffWallet::class)($user, 5);

        $this->assertEqualsWithDelta(35.0, (float) $user->fresh()->wallet_balance, 0.001);
        $this->assertSame(3, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(3, WalletTransaction::where('user_id', $user->id)->where('type', 'topup')->count());
    }

    public function test_negative_amount_is_rejected(): void
    {
        $user = User::factory()->withBalance(50)->create();

        $this->expectException(InvalidArgumentException::class);

        app(TopOffWallet::class)($user, -5);
    }

    public function test_zero_amount_is_rejected(): void
    {
        $user = User::factory()->withBalance(50)->create();

        $this->expectException(InvalidArgumentException::class);

        app(TopOffWallet::class)($user, 0);
    }

    public function test_notes_are_persisted_when_provided(): void
    {
        $user = User::factory()->create();

        $tx = app(TopOffWallet::class)($user, 10, 'Promotional credit, ticket #42');

        $this->assertSame('Promotional credit, ticket #42', $tx->fresh()->notes);
    }

    public function test_default_notes_are_admin_topoff(): void
    {
        $user = User::factory()->create();

        $tx = app(TopOffWallet::class)($user, 10);

        $this->assertSame('Admin topoff', $tx->fresh()->notes);
    }
}

<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Admin top-off: credit $amount Radar Cash to $user's wallet and record an
 * audit row in wallet_transactions.
 *
 * Used by Filament's UserResource topoffRadarCash action. Lives here as an
 * action class so:
 *   - The admin UI doesn't carry the DB::transaction + invariants logic
 *     inline (Filament closures are hard to test).
 *   - Future call sites — a future "redeem voucher" or scheduled bonus —
 *     can reuse the same atomic credit path.
 *
 * Throws InvalidArgumentException if $amount is non-positive (top-offs are
 * always positive credits; refunds use a different code path).
 */
class TopOffWallet
{
    public function __invoke(User $user, float $amount, ?string $notes = 'Admin topoff'): WalletTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Top-off amount must be positive, got {$amount}");
        }

        return DB::transaction(function () use ($user, $amount, $notes) {
            // Increment is atomic at the SQL layer. Using `update(['wallet_balance' => …])`
            // would race with concurrent scans.
            $user->increment('wallet_balance', $amount);
            $user->refresh();

            return WalletTransaction::create([
                'user_id'       => $user->id,
                'game_id'       => null,           // top-offs aren't tied to any game
                'scan_id'       => null,
                'type'          => 'topup',
                'amount'        => $amount,
                'balance_after' => $user->wallet_balance,
                'notes'         => $notes,
            ]);
        });
    }
}

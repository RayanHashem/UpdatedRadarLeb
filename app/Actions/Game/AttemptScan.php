<?php

namespace App\Actions\Game;

use App\Models\Game;
use App\Models\GameUserStat;
use App\Models\Scan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Run a single scan for $user on $game and persist all the side effects:
 * decrement the wallet, increment the prize pool, advance the user's per-game
 * stat, write a Scan row, write a matching WalletTransaction.
 *
 * Why this is an action class:
 *   - Three tables (`users`, `scans`, `wallet_transactions`) plus
 *     `game_user_stats` and `games` mutate in one logical operation.
 *   - The whole sequence runs inside a DB transaction so a partial failure
 *     never leaves the wallet desynced from `scans.cost`. Doing the
 *     orchestration on the Eloquent model conflated business logic with
 *     persistence; in an action class it's testable in isolation.
 *
 * Aborts with HTTP-shaped exceptions (`abort_*()`) so a controller calling
 * the action gets the same behavior as the old in-model orchestration.
 *   - 423: game disabled or radar offline globally.
 *   - 402: insufficient balance.
 *
 * Usage:
 *   $result = app(AttemptScan::class)($user, $game);
 *
 * Returns the same array shape the front-end already consumes:
 *   [
 *     'antenna_detected' => bool,
 *     'progress'         => array,   // see BuildGameProgress
 *     'wallet'           => float,
 *   ]
 */
class AttemptScan
{
    public function __construct(
        private readonly BuildGameProgress $buildProgress,
    ) {
    }

    public function __invoke(User $user, Game $game): array
    {
        $this->guardGameAvailable($game);

        $cost = (float) $game->price_to_play;
        $this->guardSufficientBalance($user, $cost);

        return DB::transaction(function () use ($user, $game, $cost) {
            // Re-read inside the txn — another scan request from the same user
            // may have decremented the balance between guard and txn open.
            $user->refresh();
            $this->guardSufficientBalance($user, $cost);

            $stat = $game->stats()->firstOrCreate(['user_id' => $user->id]);

            $user->decrement('wallet_balance', $cost);
            $stat->increment('amount_spent', $cost);
            $game->increment('current_amount', $cost);

            $isSuccess = $this->rollOutcome($stat);
            $stat->save();

            $scan = $game->scans()->create([
                'user_id'     => $user->id,
                'success'     => $isSuccess,
                'radar_level' => $stat->current_radar ?? 0,
                'cost'        => $cost,
            ]);

            $user->refresh();

            // firstOrCreate keyed on scan_id keeps the unique-partial-index
            // honest even if this txn somehow runs twice (it shouldn't).
            WalletTransaction::firstOrCreate(
                ['scan_id' => $scan->id],
                [
                    'user_id'       => $user->id,
                    'game_id'       => $game->id,
                    'type'          => 'debit',
                    'amount'        => $cost,
                    'balance_after' => $user->wallet_balance,
                ]
            );

            return [
                'antenna_detected' => $isSuccess,
                'progress'         => ($this->buildProgress)($game, $user, $stat, includeWinEligibility: true),
                'wallet'           => (float) $user->wallet_balance,
            ];
        });
    }

    /**
     * Decide whether this scan wins. Mutates $stat in-place when it does.
     *
     * Two-stage RNG, kept identical to the original behaviour:
     *   1. baseFails climbs with the user's radar level (10/level), with a
     *      ±2 jitter so the threshold isn't deterministic.
     *   2. Once fails_in_level reaches the threshold, a coin flip decides
     *      whether the scan actually wins. random_int (CSPRNG) — see
     *      docs/security.md for why mt_rand was removed.
     */
    private function rollOutcome(GameUserStat $stat): bool
    {
        $stat->increment('fails_in_level');

        $nextRadar   = min($stat->current_radar + 1, 6);
        $baseFails   = $nextRadar * 10;
        $neededFails = $baseFails + random_int(-2, 2);

        $isSuccess = false;
        if ($stat->fails_in_level >= $neededFails && random_int(0, 1) === 1) {
            $stat->current_radar     = $nextRadar;
            $stat->successful_scans += 1;
            $stat->fails_in_level    = 0;
            $isSuccess               = true;
        }

        if (! $isSuccess) {
            $stat->failed_scans += 1;
        }

        return $isSuccess;
    }

    private function guardGameAvailable(Game $game): void
    {
        abort_unless((bool) $game->is_enabled, 423, 'game_disabled');
        abort_unless(SystemSetting::get('scans_enabled', true), 423, 'Radar offline');
    }

    private function guardSufficientBalance(User $user, float $cost): void
    {
        abort_if((float) $user->wallet_balance < $cost, 402, 'Not enough balance');
    }
}

<?php

namespace App\Actions\Game;

use App\Models\Game;
use App\Models\User;

/**
 * Decide whether $user can win the final draw on $game.
 *
 * The product rule is: be in the top-3 spenders on this game AND the game's
 * total spend has crossed the prize-pool threshold.
 *
 * Performance: short-circuits on the cheaper query first.
 *   - Top-3 leaderboard: 1 indexed query, LIMIT 3.
 *   - Threshold sum: full scan of game_user_stats for this game (expensive
 *     once the table grows).
 * For the common case — a user who isn't in the top 3 — we never run the SUM.
 *
 * Net query count:
 *   - Users not in top-3 (most users): 1 query.
 *   - Users in top-3:                    2 queries.
 */
class CheckWinEligibility
{
    public function __invoke(Game $game, User $user): bool
    {
        $topIds = $game->stats()
            ->orderByDesc('amount_spent')
            ->limit(3)
            ->pluck('user_id');

        if (! $topIds->contains($user->id)) {
            return false;
        }

        $totalSpent = (float) $game->stats()->sum('amount_spent');

        return $totalSpent >= (float) $game->minimum_amount_for_winning;
    }
}

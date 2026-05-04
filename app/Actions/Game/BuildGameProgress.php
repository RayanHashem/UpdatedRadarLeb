<?php

namespace App\Actions\Game;

use App\Models\Game;
use App\Models\GameUserStat;
use App\Models\User;

/**
 * Build the progress payload for a (Game, User) pair — the array shape the
 * front-end consumes on the dashboard and inside the scan response.
 *
 * Two call patterns:
 *
 *   1. List view (dashboard, /games index): pass a pre-loaded $stat from
 *      a `keyBy(game_id)` collection so we avoid N+1 over the games list.
 *      Pass `$includeWinEligibility = false` — the dashboard list doesn't
 *      need the expensive leaderboard query for every card.
 *
 *   2. Single-game (scan response, single-game endpoints): pass null for
 *      $stat and the action materialises (or creates) the row, AND pass
 *      `$includeWinEligibility = true` to get the authoritative flag.
 */
class BuildGameProgress
{
    public function __construct(
        private readonly CheckWinEligibility $checkWinEligibility,
    ) {
    }

    public function __invoke(
        Game $game,
        User $user,
        ?GameUserStat $stat = null,
        bool $includeWinEligibility = false,
    ): array {
        $stat ??= $game->stats()->firstOrCreate(['user_id' => $user->id]);

        return [
            'radar_level'   => $stat->current_radar ?? 0,
            'failed_scans'  => $stat->failed_scans ?? 0,
            'successful'    => $stat->successful_scans ?? 0,
            'amount_spent'  => $stat->amount_spent ?? 0,
            'can_win_final' => $includeWinEligibility
                ? ($this->checkWinEligibility)($game, $user)
                : false,
        ];
    }
}

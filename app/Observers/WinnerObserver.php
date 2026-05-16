<?php

namespace App\Observers;

use App\Models\Draw;
use App\Models\Game;
use App\Models\Winner;
use Illuminate\Support\Facades\DB;

/**
 * Bookkeeping that runs after a Winner row is created.
 *
 *   1. Close the current open Draw for this game: stamp `closed_at`
 *      (= the moment the winner won), record `winner_user_id`,
 *      `winning_scan_id`, and flip the stored status to 'closed'.
 *   2. Bump the Game's `draw_number` so every future scan competes for
 *      the next draw — e.g. after a Mobile winner, all players are now
 *      playing for Mobile draw #2 instead of #1.
 *   3. Open a fresh Draw row for that new draw number, stamped
 *      opened_at = now() if the prize is still enabled. (If the admin
 *      disabled the prize before/at the moment of declaring the winner,
 *      `opened_at` stays null and re-enabling the prize will stamp it
 *      via GameObserver — keeping the two flows symmetric.)
 *
 * All three steps run inside one DB transaction so we never end up with
 * an "old draw closed, new draw missing" half-state.
 */
class WinnerObserver
{
    public function created(Winner $winner): void
    {
        if ($winner->game_id === null) {
            return;
        }

        DB::transaction(function () use ($winner) {
            $game = Game::query()->lockForUpdate()->find($winner->game_id);
            if ($game === null) {
                return;
            }

            $closedAt = $winner->won_at ?? $winner->created_at ?? now();
            $currentDrawNumber = (int) ($game->draw_number ?? 1);

            Draw::query()
                ->where('game_id', $game->id)
                ->where('draw_number', $currentDrawNumber)
                ->whereNull('winner_user_id')
                ->update([
                    'winner_user_id' => $winner->winner_user_id ?? $winner->user_id,
                    'winning_scan_id' => $winner->scan_id,
                    'closed_at' => $closedAt,
                    'status' => 'closed',
                    'updated_at' => now(),
                ]);

            $game->increment('draw_number');
            $game->refresh();

            Draw::create([
                'game_id' => $game->id,
                'draw_number' => (int) $game->draw_number,
                'status' => $game->is_enabled ? 'open' : 'closed',
                'opened_at' => $game->is_enabled ? now() : null,
            ]);
        });
    }
}

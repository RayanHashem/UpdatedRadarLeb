<?php

namespace App\Observers;

use App\Models\Draw;
use App\Models\Game;

/**
 * Side effects of mutating a Game row.
 *
 * Today we only care about one transition: the admin toggling the
 * `is_enabled` flag from false → true on the Prizes resource. That toggle
 * represents "this prize is now playable, the current draw is open" — so
 * we stamp `opened_at` on whichever Draw row is the current open one for
 * this game (the row with no winner yet). If no Draw row exists yet for
 * the current `draw_number`, we create one. This keeps the Draws admin
 * page in sync with the is_enabled toggle without forcing admins to
 * touch two screens.
 *
 * We do NOT clear `closed_at` here — closing-on-disable would conflict
 * with the project rule that `closed_at` only ever means "winner declared
 * at this time". A disabled-but-not-yet-won draw simply shows status
 * "closed" via the derived column on DrawResource until re-enabled.
 */
class GameObserver
{
    public function updated(Game $game): void
    {
        if (! $game->wasChanged('is_enabled')) {
            return;
        }

        if (! $game->is_enabled) {
            return;
        }

        $drawNumber = (int) ($game->draw_number ?? 1);

        $draw = Draw::query()
            ->where('game_id', $game->id)
            ->where('draw_number', $drawNumber)
            ->whereNull('winner_user_id')
            ->first();

        if ($draw === null) {
            Draw::create([
                'game_id' => $game->id,
                'draw_number' => $drawNumber,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            return;
        }

        if ($draw->opened_at === null) {
            $draw->opened_at = now();
            $draw->status = 'open';
            $draw->save();
        }
    }
}

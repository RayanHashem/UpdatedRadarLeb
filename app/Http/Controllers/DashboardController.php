<?php

namespace App\Http\Controllers;

use App\Actions\Game\BuildGameProgress;
use App\Models\Game;
use App\Models\GameUserStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard — Inertia entry point for the public app.
 *
 * Pre-loads everything the Vue dashboard needs in a single batch (one
 * query for games, one for stats keyed by game_id) so the rendering
 * function avoids N+1 over the games loop.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildGameProgress $buildProgress,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $games       = Game::orderBy('id')->get();
        $statsByGame = GameUserStat::where('user_id', $user->id)
            ->get()
            ->keyBy('game_id');

        return Inertia::render('Dashboard', [
            'games' => $games->map(fn (Game $g) => [
                'id'         => $g->id,
                'name'       => $g->name,
                'price'      => $g->price,
                'image'      => $g->image_path,
                // Skip win-eligibility on the list view — dashboard cards
                // don't need the per-game leaderboard query.
                'progress'   => ($this->buildProgress)($g, $user, $statsByGame->get($g->id)),
                'is_enabled' => (bool) $g->is_enabled,
            ]),
            'selectedGameId' => null,
            'wallet_balance' => (float) $user->wallet_balance,
        ]);
    }
}

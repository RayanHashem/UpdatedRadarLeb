<?php

namespace App\Http\Controllers\Api;

use App\Actions\Game\AttemptScan;
use App\Actions\Game\BuildGameProgress;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * SPA-facing JSON endpoints for games.
 *
 * The controller is intentionally thin: business logic lives in
 * App\Actions\Game\*. The controller only knows how to translate HTTP →
 * action input → JSON response.
 */
class GameController extends Controller
{
    public function __construct(
        private readonly BuildGameProgress $buildProgress,
        private readonly AttemptScan $attemptScan,
    ) {
    }

    /**
     * Lightweight game catalog for the SPA. Dashboard.vue reads this on
     * mount and on focus to refresh the per-user progress without forcing
     * a full Inertia round-trip.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $games = Game::orderBy('id')->get()->map(fn (Game $g) => [
            'id'         => $g->id,
            'name'       => $g->name,
            'price'      => $g->price,
            'image'      => $g->image_path,
            'progress'   => ($this->buildProgress)($g, $user, includeWinEligibility: true),
            'is_enabled' => (bool) $g->is_enabled,
        ]);

        return response()->json($games);
    }

    /**
     * POST /scan/{game}.
     *
     * Always echoes the authoritative wallet balance in the response (success
     * or failure) so the UI can update Radar Cash on any outcome without a
     * full reload — avoids the "I have to refresh the page" bug we used to
     * see when an abort_*() call fired before the wallet round-tripped.
     */
    public function scan(Game $game): JsonResponse
    {
        $user = auth()->user();

        if (! $game->is_enabled) {
            return response()->json([
                'message' => 'Prize is disabled',
                'wallet'  => (float) $user->wallet_balance,
            ], 403);
        }

        try {
            return response()->json(($this->attemptScan)($user, $game), 200);
        } catch (HttpException $e) {
            $user->refresh();

            return response()->json([
                'message' => $e->getMessage() ?: 'Scan failed',
                'wallet'  => (float) $user->wallet_balance,
            ], $e->getStatusCode());
        }
    }
}

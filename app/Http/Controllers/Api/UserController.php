<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Update the user's selected game (prize).
     *
     * Accepts:
     *   - a valid games.id  → persists the selection (rejecting disabled games)
     *   - null               → clears the selection. Used by Dashboard.vue
     *                          after a scan so the UI returns to a clean
     *                          "no prize picked" state and survives a reload.
     */
    public function updateGame(Request $request): JsonResponse
    {
        $request->validate([
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
        ]);

        $gameId = $request->integer('game_id') ?: null;

        if ($gameId === null) {
            $request->user()->update(['game_id' => null]);

            return response()->json(['message' => 'cleared']);
        }

        $game = Game::findOrFail($gameId);
        if (! $game->is_enabled) {
            return response()->json(['message' => 'Prize is disabled'], 403);
        }

        $request->user()->update(['game_id' => $gameId]);

        return response()->json(['message' => 'saved']);
    }
}

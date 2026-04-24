<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * Update the user's selected game (prize).
     *
     * Accepts either:
     *   - a valid games.id  → persists the selection (rejecting disabled games)
     *   - null              → clears the selection, used by Dashboard.vue after
     *                          a scan completes so the UI returns to a clean,
     *                          "no-prize-picked" state and survives a reload.
     */
    public function updateGame(Request $request)
    {
        $request->validate([
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
        ]);

        $gameId = $request->input('game_id');

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
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

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
     * Update the user's selected game (prize). Rejects if the game is disabled.
     */
    public function updateGame(Request $request)
    {
        $request->validate(['game_id' => 'required', 'exists:games,id']);

        $game = Game::find($request->game_id);
        if (! $game->is_enabled) {
            return response()->json(['message' => 'Prize is disabled'], 403);
        }

        $request->user()->update(['game_id' => $request->game_id]);

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

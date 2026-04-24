<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Game::all()->map(fn ($g) => [
            'id'         => $g->id,
            'name'       => $g->name,
            'price'      => $g->price,
            'image'      => $g->image_path,
            'progress'   => $g->progressFor(auth()->user()),
            'is_enabled' => (bool) $g->is_enabled,
        ]);
    }

    /**
     * POST /scan/{game}
     * Always includes the authoritative wallet balance in every response
     * (success or failure) so the UI can update Radar Cash on any outcome
     * without requiring a page reload.
     */
    public function scan(Game $game)
    {
        $user = auth()->user();

        if (! $game->is_enabled) {
            return response()->json([
                'message' => 'Prize is disabled',
                'wallet'  => (float) $user->wallet_balance,
            ], 403);
        }

        try {
            return response()->json($game->attemptScan($user), 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $user->refresh();

            return response()->json([
                'message' => $e->getMessage() ?: 'Scan failed',
                'wallet'  => (float) $user->wallet_balance,
            ], $e->getStatusCode());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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

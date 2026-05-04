<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Return a small payload describing the current authenticated user — used
     * by the SPA shell to bootstrap the user-menu and the wallet pill on
     * navigation. Don't add fields here that should be private; this response
     * is rendered into client memory.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'             => $user->id,
            'game_id'        => $user->game_id,
            'wallet_balance' => (float) $user->wallet_balance,
        ]);
    }
}

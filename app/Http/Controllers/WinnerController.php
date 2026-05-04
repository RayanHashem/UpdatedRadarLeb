<?php

namespace App\Http\Controllers;

use App\Models\Winner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WinnerController extends Controller
{
    /**
     * Recent public winners. Auth-gated, paginated, throttled at the route
     * layer — see routes/web.php. The table is small (one row per draw win)
     * but we still bound the response so this endpoint can never become a
     * heavy scrape target.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min($perPage, 50)); // hard upper bound

        $winners = Winner::query()
            ->select('id', 'game_name', 'user_name', 'created_at')
            ->latest('created_at')
            ->paginate($perPage)
            ->through(fn (Winner $w) => [
                'id'         => $w->id,
                'game_name'  => $w->game_name,
                'user_name'  => $w->user_name,
                'created_at' => $w->created_at?->toIso8601String(),
            ]);

        return response()->json($winners);
    }
}

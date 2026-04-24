<?php

use App\Http\Controllers\RadarController;
use App\Models\Game;
use App\Models\GameUserStat;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();
    $games = Game::all();

    $statsByGame = GameUserStat::where('user_id', $user->id)
        ->get()
        ->keyBy('game_id');

    return Inertia::render('Dashboard', [
        'games' => $games->map(fn ($g) => [
            'id'         => $g->id,
            'name'       => $g->name,
            'price'      => $g->price,
            'image'      => $g->image_path,
            'progress'   => $g->progressFromStat($statsByGame->get($g->id)),
            'is_enabled' => (bool) $g->is_enabled,
        ]),
        // Always start with no prize selected on page (re)entry — user must manually pick a prize
        // so the balance / minimum-deposit checks in selectPrize() run every time.
        'selectedGameId'  => null,
        'wallet_balance'  => (float) $user->wallet_balance,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/radar/status', [RadarController::class, 'status'])->middleware('throttle:30,1');
Route::get('/winners', function () {
    return \App\Models\Winner::select('game_name', 'user_name')->get();
});

Route::get('/terms', fn () => Inertia::render('Legal', ['type' => 'terms']))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Legal', ['type' => 'privacy']))->name('privacy');

require __DIR__.'/auth.php';

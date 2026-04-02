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
        'selectedGameId'  => $user->game_id,
        'wallet_balance'  => $user->wallet_balance,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get ('/radar/status',  [RadarController::class,'status']);
Route::get('/winners', function () {
    return \App\Models\Winner::select('game_name', 'user_name')->get();
});

Route::get('/terms', fn () => Inertia::render('Legal', ['type' => 'terms']))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Legal', ['type' => 'privacy']))->name('privacy');

require __DIR__.'/auth.php';

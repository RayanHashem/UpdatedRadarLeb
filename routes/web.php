<?php

use App\Http\Controllers\RadarController;
use App\Models\Game;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'games' => Game::all()->map(fn ($g) => [
            'id'         => $g->id,
            'name'       => $g->name,
            'price'      => $g->price,
            'image'      => $g->image_path,
            'progress'   => $g->progressFor(auth()->user()),
            'is_enabled' => (bool) $g->is_enabled, // 👈 add this
        ]),
        'selectedGameId'  => auth()->user()->game_id,
        'wallet_balance'  => auth()->user()->wallet_balance,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get ('/radar/status',  [RadarController::class,'status']);
Route::get('/winners', function () {
    return \App\Models\Winner::select('game_name', 'user_name')->get();
});

Route::get('/terms', fn () => Inertia::render('Legal', ['type' => 'terms']))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Legal', ['type' => 'privacy']))->name('privacy');

require __DIR__.'/auth.php';

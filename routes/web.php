<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminTwoFactorController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RadarController;
use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Inertia (Vue 3) renders here. JSON-only API endpoints live in routes/api.php
| and routes/auth.php. Keep this file free of inline closures — every route
| dispatches to a controller so route caching and tests stay simple.
|
*/

Route::middleware(['auth', 'verified'])
    ->get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::middleware(['auth:admin', 'throttle:6,1'])->group(function () {
    Route::get('/admin/two-factor', [AdminTwoFactorController::class, 'create'])
        ->name('admin.two-factor.create');
    Route::post('/admin/two-factor', [AdminTwoFactorController::class, 'store'])
        ->name('admin.two-factor.store');
});

Route::middleware('throttle:30,1')
    ->get('/radar/status', [RadarController::class, 'status']);

// Used by the Winners overlay on Dashboard.vue. Auth-gated, throttled, and
// paginated at the controller — we don't want this becoming a scrape target.
Route::middleware(['auth', 'throttle:60,1'])
    ->get('/winners', [WinnerController::class, 'index'])
    ->name('winners.index');

Route::get('/terms',   [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/credits', [LegalController::class, 'credits'])->name('credits');

// Locale switcher — POST so we don't get stuck in a redirect loop on cached
// pages. The locale itself is validated in the controller. Throttle matches
// the user-update endpoints; nobody legitimately switches language 60+ times
// per minute, but we don't want it to be a free no-op session writer either.
Route::middleware('throttle:60,1')
    ->post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', array_keys(config('contact.locales', ['en' => 'English'])))
    ->name('locale.update');

require __DIR__.'/auth.php';

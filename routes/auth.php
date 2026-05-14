<?php

use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
 * Password reset must NOT use the "guest" middleware: users often open "Forgot
 * password" from the dashboard (still logged in) or click the email link while
 * a session exists. RedirectIfAuthenticated would send them to the dashboard
 * and the flow would appear "broken".
 */
Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('password.request');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

// Same throttle as forgot-password — both are credential-touching endpoints.
Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.store');

/*
 * Guest routes — registration + login. Login + register are each throttled
 * separately to keep brute-force attempts on either flow expensive.
 */
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');
});

/*
 * Authenticated app + API routes. Note `/scan/{game}` is throttled tightly
 * because it's the money-mutating endpoint (each call debits the wallet).
 */
Route::middleware('auth')->group(function () {
    Route::get('/games',           [GameController::class, 'index']);
    Route::get('/scan/nonce',      [GameController::class, 'nonce'])
        ->middleware('throttle:30,1');
    Route::post('/scan/{game}',    [GameController::class, 'scan'])
        ->middleware('throttle:20,1');

    Route::get('/me',              [MeController::class, 'show']);
    Route::post('/me/game',        [UserController::class, 'updateGame'])
        ->middleware('throttle:60,1');

    // Settings — verify+update are credential-touching, throttle them like
    // the public auth flows (10/min keeps brute-force expensive).
    Route::post('/settings/password/verify', [SettingsController::class, 'verifyOldPassword'])
        ->middleware('throttle:10,1');
    Route::post('/settings/password',        [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:10,1')
        ->name('settings.password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

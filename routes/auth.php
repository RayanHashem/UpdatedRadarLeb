<?php

use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

/*
 * Password reset must NOT use the "guest" middleware: users often open "Forgot password"
 * from the dashboard (still logged in) or click the email link while a session exists.
 * RedirectIfAuthenticated would send them to the dashboard and the flow would appear "broken".
 */
Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('password.request');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/games',        [GameController::class,'index']);
    Route::post('/scan/{game}',        [GameController::class,'scan']);

    Route::get('/me', function (Request $r) {
        $u = $r->user();

        return [
            'id'             => $u->id,
            'game_id'        => $u->game_id,
            'wallet_balance' => (float) $u->wallet_balance,
        ];
    });
    Route::post('/me/game',     [\App\Http\Controllers\Api\UserController::class,'updateGame']);

    // Settings routes
    Route::post('/settings/password/verify', [SettingsController::class, 'verifyOldPassword']);
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

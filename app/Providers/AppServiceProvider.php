<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use App\Http\Responses\Filament\AdminLoginResponse;
use App\Models\Game;
use App\Models\Winner;
use App\Observers\GameObserver;
use App\Observers\WinnerObserver;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', PostgresConnector::class);
        $this->app->singleton(LoginResponseContract::class, AdminLoginResponse::class);

        $this->app->booting(function () {
            $timeout = env('DB_CONNECT_TIMEOUT');
            if ($timeout !== null) {
                config(['database.connections.pgsql.connect_timeout' => (int) $timeout]);
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        /*
         * Optional MAIL_RESET_BASE_URL: canonical public URL for password-reset emails.
         * Without it, the link uses the current request root (see PortBasedSessionIsolation),
         * which can be the wrong port if the request hit e.g. :8001 while users use :8000.
         */
        /*
         * Draw lifecycle observers.
         *
         *   - GameObserver stamps `opened_at` on the active Draw row when an
         *     admin flips `is_enabled` from false → true on the Prizes
         *     resource.
         *   - WinnerObserver closes the active Draw, increments the game's
         *     `draw_number`, and opens a fresh Draw row whenever a Winner
         *     is recorded — so all subsequent scans compete for the new
         *     draw round.
         */
        Game::observe(GameObserver::class);
        Winner::observe(WinnerObserver::class);

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $base = (string) (env('MAIL_RESET_BASE_URL') ?: config('app.url'));
            $base = rtrim($base, '/');

            return $base.'/reset-password/'.$token
                .'?email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}

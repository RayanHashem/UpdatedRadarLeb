<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use App\Http\Responses\Filament\AdminLoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
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
                config(["database.connections.pgsql.connect_timeout" => (int) $timeout]);
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
    }
}

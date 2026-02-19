<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use App\Http\Responses\Filament\AdminLoginResponse;
use App\Http\Responses\Filament\AdminLogoutResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
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
        
        // Register custom Filament admin login/logout responses to ensure redirects stay on admin port/path
        $this->app->singleton(LoginResponseContract::class, AdminLoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, AdminLogoutResponse::class);
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

<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureAdminPortIsolation;
use App\Http\Middleware\RedirectIfCannotAccessPanel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\Profile;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->renderHook(PanelsRenderHook::STYLES_AFTER, function (): string {
                return Blade::render(<<<'HTML'
                    <style>
                        /* Filament admin: prevent table overflow on 360/390/414px */
                        @media (max-width: 414px) {
                            .fi-ta-content { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100vw; }
                            .fi-ta-table { min-width: 280px; }
                            .fi-fo-component-ctn input, .fi-fo-component-ctn select { max-width: 100%; box-sizing: border-box; }
                        }
                    </style>
                HTML);
            })
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->login()
            ->homeUrl(fn (): string => '/admin') // Explicitly set home URL to stay in admin
            ->brandName('RadarLeb Admin')
            ->brandLogo(asset('assets/imgs/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->font('Inter')
            ->darkMode(true, isForced: true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Dashboard'),
                NavigationGroup::make()
                    ->label('Games'),
                NavigationGroup::make()
                    ->label('Users')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Winners'),
                NavigationGroup::make()
                    ->label('Analytics')
                    ->collapsible()
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->middleware([
                EnsureAdminPortIsolation::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                RedirectIfCannotAccessPanel::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

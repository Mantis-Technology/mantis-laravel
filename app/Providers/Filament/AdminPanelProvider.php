<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->login()
            ->domain(config('app.central_domain'))
            ->colors([
                'primary' => [50 => 'oklch(0.975 0.012 50)', 100 => 'oklch(0.945 0.025 48)', 200 => 'oklch(0.895 0.05 45)', 300 => 'oklch(0.82 0.085 42)', 400 => 'oklch(0.72 0.115 39)', 500 => 'oklch(0.65 0.125 37)', 600 => 'oklch(0.58 0.125 36.2)', 700 => 'oklch(0.5 0.115 35.5)', 800 => 'oklch(0.43 0.095 35)', 900 => 'oklch(0.37 0.075 35)', 950 => 'oklch(0.26 0.05 35)'],
                'info' => [50 => 'oklch(0.97 0.012 200)', 100 => 'oklch(0.94 0.025 202)', 200 => 'oklch(0.88 0.04 203)', 300 => 'oklch(0.81 0.055 204)', 400 => 'oklch(0.7 0.07 204.5)', 500 => 'oklch(0.62 0.075 205)', 600 => 'oklch(0.537 0.074 205)', 700 => 'oklch(0.463 0.063 205.5)', 800 => 'oklch(0.4 0.053 206)', 900 => 'oklch(0.35 0.045 206)', 950 => 'oklch(0.25 0.03 206)'],
                'gray' => Color::Stone,
            ])
            ->brandName('MANTIS Admin')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('2.5rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

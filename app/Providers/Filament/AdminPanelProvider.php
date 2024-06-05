<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Filament\Widgets\Entry;
use App\Filament\Widgets\Personas;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\EmpleadosEnElBarrio;
use App\Filament\Widgets\InquilinosEnElBarrio;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Widgets\PropietariosEnElBarrio;
use App\Filament\Widgets\TrabajadoresEnElBarrio;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\ActivitiesResource\Widgets\UltimasActividades;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

       
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->brandLogo(asset('images/logo-blue.png'))
            ->darkModeBrandLogo(asset('images/logo.png'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                // Entry::class,
                UltimasActividades::class,
                // Personas::class,
                // PropietariosEnElBarrio::class,
                // EmpleadosEnElBarrio::class,
                // TrabajadoresEnElBarrio::class,
                // InquilinosEnElBarrio::class,
                // \App\Filament\Resources\ActivitiesResource\Widgets\ActivitiesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
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

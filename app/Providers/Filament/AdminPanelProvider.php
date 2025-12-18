<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Filament\Widgets\Entry;
use App\Filament\Widgets\Personas;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\EnElBarrio;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Widgets\EmpleadosEnElBarrio;
use App\Filament\Widgets\InquilinosEnElBarrio;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Widgets\PropietariosEnElBarrio;
use App\Filament\Widgets\TrabajadoresEnElBarrio;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\FormIncidentStatsWidget;
use App\Filament\Widgets\UserTermsConditionsCheck;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Widgets\FormIncidentComplianceWidget;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use App\Filament\Resources\OwnerResource\Widgets\OwnerQr;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Resources\SlidersResource\Widgets\Sliders;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use  App\Filament\Resources\IncidentResource\Widgets\IncidentesStats;
use App\Filament\Resources\FormControlResource\Widgets\FormControlStats;
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
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                ->selectable()
                ->editable()
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                UserTermsConditionsCheck::class,
                EnElBarrio::class,
                FormIncidentComplianceWidget::class,
                FormIncidentStatsWidget::class,
                //UltimasActividades::class,
                // IncidentesStats::class,
                FormControlStats::class,
                Sliders::class,
                OwnerQr::class
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
            ])
            ->databaseNotifications()
            ->renderHook(
                'panels::body.end',
                fn () => view('components.qr-scanner-modal')
            )
            ;
    }
}

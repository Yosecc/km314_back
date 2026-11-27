<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivitiesResource\Widgets\UltimasActividades;
use App\Filament\Resources\FormControlResource\Widgets\FormControlStats;
use App\Filament\Resources\IncidentResource\Widgets\IncidentesStats;
use App\Filament\Resources\InterestedLevelResource;
use App\Filament\Resources\OwnerResource\Widgets\OwnerQr;
use App\Filament\Resources\SlidersResource\Widgets\Sliders;
use App\Filament\Widgets\AccessControlStats;
use App\Filament\Widgets\EmployeeApprovalStats;
use App\Filament\Widgets\FormIncidentComplianceWidget;
use App\Filament\Widgets\FormIncidentStatsWidget;
use App\Filament\Widgets\InterestedFollowUpStats;
use App\Filament\Widgets\PackageReceptionStats;
use App\Filament\Widgets\QuickAccessWidget;
use App\Filament\Widgets\RecurrentVisitorApprovalStats;
use App\Filament\Widgets\ServiceRequestStats;
use App\Filament\Widgets\UserTermsConditionsCheck;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

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
            ->resources([
                InterestedLevelResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                QuickAccessWidget::class,
                InterestedFollowUpStats::class,
                UserTermsConditionsCheck::class,
                AccessControlStats::class,
                FormIncidentComplianceWidget::class,
                FormIncidentStatsWidget::class,
                //UltimasActividades::class,
                // IncidentesStats::class,
                FormControlStats::class,
                PackageReceptionStats::class,
                ServiceRequestStats::class,
                RecurrentVisitorApprovalStats::class,
                EmployeeApprovalStats::class,
                Sliders::class,
                OwnerQr::class,
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
            ->renderHook(
                'panels::body.end',
                fn () => view('components.firebase-web-push')
            );
    }
}

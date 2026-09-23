<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * An indigo ramp anchored so shade 600 is exactly #303191, the brand navy.
     *
     * @var array<int, string>
     */
    private const BRAND_RAMP = [
        50 => 'oklch(0.970 0.018 275.68)',
        100 => 'oklch(0.938 0.036 275.68)',
        200 => 'oklch(0.880 0.068 275.68)',
        300 => 'oklch(0.800 0.104 275.68)',
        400 => 'oklch(0.640 0.155 275.68)',
        500 => 'oklch(0.500 0.170 275.68)',
        600 => 'oklch(0.378 0.154 275.68)',
        700 => 'oklch(0.330 0.134 275.68)',
        800 => 'oklch(0.285 0.112 275.68)',
        900 => 'oklch(0.245 0.092 275.68)',
        950 => 'oklch(0.180 0.070 275.68)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('TravelWheel Admin')
            ->defaultThemeMode(ThemeMode::Light)
            // Operational tables are wide — flight bookings alone carries ten
            // columns — so the panel uses the full window rather than Filament's
            // centred reading column.
            ->maxContentWidth(Width::Full)
            // Lets support staff reclaim the rail when they need the width, and
            // gives the sidebar tooltips on hover once collapsed.
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->colors([
                // config('brand.colors') — the same values the site, the emails
                // and the travel documents use. This panel had drifted to
                // #0d1883/#00a85a, a blue and a green that exist nowhere else.
                //
                // primary is spelled out rather than handed to Color::hex().
                // Color::hex() keeps only the HUE of what you give it and
                // rebuilds the lightness ramp from a fixed template, so
                // #303191 (oklch L 0.378) came back as shade 600 = L 0.598 —
                // and since Filament paints solid buttons with shade 600, every
                // primary button in the panel rendered as a pale periwinkle
                // that read as disabled. The brand navy never appeared at all.
                // Anchored here so 600 IS #303191.
                'primary' => self::BRAND_RAMP,
                'success' => Color::hex('#00a859'),
                'danger' => Color::hex('#d92d20'),
                'warning' => Color::hex('#f79009'),
                'info' => Color::hex('#2e90fa'),
                'gray' => Color::Slate,
            ])
            // Ten groups discovered in alphabetical order buried Flight Bookings —
            // the busiest screen in the panel — below Air Cargo and Car Hire.
            // Ordered by how often ops actually opens them.
            ->navigationGroups([
                NavigationGroup::make('Operations'),
                NavigationGroup::make('Visa Operations'),
                NavigationGroup::make('Support Requests'),
                NavigationGroup::make('Insights'),
                NavigationGroup::make('Air Cargo'),
                NavigationGroup::make('Car Hire & Transfer'),
                NavigationGroup::make('Visa Catalogue'),
                NavigationGroup::make('Insurance'),
                NavigationGroup::make('Lounge'),
                NavigationGroup::make('Protocol'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

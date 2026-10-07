<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\FlightBookings\FlightBookingResource;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * An indigo ramp anchored so shade 600 is exactly #303191, the brand navy
     * from config/brand.php.
     *
     * Not handed to Color::hex(), which keeps only the HUE of what it is given
     * and rebuilds lightness from a fixed template: #303191 (oklch L 0.378)
     * came back as shade 600 = L 0.598, and since Filament paints solid buttons
     * with shade 600, every primary button in the panel rendered as a pale
     * periwinkle that read as disabled. The brand navy never appeared at all.
     *
     * These are the same values the console's --tc-brand-* tokens carry;
     * ConsoleShellTest asserts the two stay in step.
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
            ->brandName('TravelWheel')
            ->defaultThemeMode(ThemeMode::Light)
            // Operational tables carry ten columns; the console uses the whole
            // window rather than Filament's centred reading column.
            ->maxContentWidth(Width::Full)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            // The bell: escalations to you and answers to yours. Sixty
            // seconds for the same reason the dashboard polls at sixty.
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->colors([
                'primary' => self::BRAND_RAMP,
                'success' => Color::hex('#00a859'),
                'danger' => Color::hex('#d92d20'),
                'warning' => Color::hex('#f79009'),
                'info' => Color::hex('#2e90fa'),
                'gray' => Color::Slate,
            ])
            /*
             * The console navigates by GROUP from a 56px icon rail, so every
             * group needs an icon — an icon rail with blank squares in it is
             * just a worse sidebar.
             *
             * ONE PRINCIPLE: a group is a service, and it owns both the queue
             * you work and the data that configures it. The panel used to mix
             * two — cargo, lounge and protocol grouped by service, while visa
             * split by activity into a five-item `Visa Catalogue` and a
             * `Visa Operations` that held exactly one link. `Operations` had
             * become the junk drawer that mixing produces: the flight queue,
             * two pricing tables, a mail log and a diagnostics page.
             *
             * Groups also line up with the departments that work them
             * (App\Support\Admin\DepartmentNavigation), and each member of
             * staff sees their own department's groups first. Two groups
             * exist for that reason rather than for a service:
             *   Finance          — payment confirmation, TravelFlex credit,
             *                      exchange rates and service charges: the
             *                      money decisions, whichever service they
             *                      touch. (Exchange rates used to sit in
             *                      System as "shared"; Finance owns them.)
             *   Customer Support — the four support requests and insurance,
             *                      one team's work in one place.
             *
             * Order is by how often ops opens them, and within a group the
             * queues sort before the setup that feeds them (10..40 against
             * 50+), so the first thing in every flyout is work.
             */
            ->navigationGroups([
                NavigationGroup::make('Flights')->icon(Heroicon::OutlinedPaperAirplane),
                NavigationGroup::make('Visas')->icon(Heroicon::OutlinedIdentification),
                NavigationGroup::make('Finance')->icon(Heroicon::OutlinedBanknotes),
                NavigationGroup::make('Customer Support')->icon(Heroicon::OutlinedLifebuoy),
                NavigationGroup::make('Air Cargo')->icon(Heroicon::OutlinedCube),
                NavigationGroup::make('Travel Connections')->icon(Heroicon::OutlinedTruck),
                NavigationGroup::make('Airport Services')->icon(Heroicon::OutlinedSparkles),
                NavigationGroup::make('Insights')->icon(Heroicon::OutlinedChartBar),
                // Staff, departments and the activity log. Only the CEO sees it.
                NavigationGroup::make('Team')->icon(Heroicon::OutlinedUserGroup),
                NavigationGroup::make('System')->icon(Heroicon::OutlinedCog6Tooth),
            ])
            // Finance's first job is a tab of the flight queue, not a list of
            // its own, so it is a link into that tab.
            ->navigationItems([
                NavigationItem::make('Bank transfers to confirm')
                    ->group('Finance')
                    ->sort(10)
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->url(fn (): string => FlightBookingResource::getUrl('index', ['activeTab' => 'awaiting_transfer']))
                    ->isActiveWhen(fn (): bool => request()->routeIs(FlightBookingResource::getRouteBaseName().'.index')
                        && request()->query('activeTab') === 'awaiting_transfer'),
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

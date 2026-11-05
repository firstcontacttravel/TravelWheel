<?php

namespace App\Providers;

use App\Models\FlightBooking;
use App\Models\FlightSearch;
use App\Services\Corporate\CompanyInvoices;
use App\Services\Corporate\CorporateContext;
use App\Support\Admin\ActivityRecorder;
use App\Workflow\SyncsWorkItems;
use App\Workflow\WorkAccess;
use App\Workflow\WorkflowRegistry;
use Filament\Actions\Action;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Large pages (e.g. return-flight search results with 100+ itineraries)
        // can exceed PHP's default PCRE backtrack limit, which makes
        // Livewire's dev-mode root-element check crash with:
        // "DOMDocument::loadHTML(): Argument #1 ($source) must not be empty".
        ini_set('pcre.backtrack_limit', '10000000');

        $this->app->singleton(WorkflowRegistry::class);
        $this->app->scoped(WorkAccess::CACHE, fn () => new \ArrayObject);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ActivityRecorder::register();
        SyncsWorkItems::register();

        // Any action on a booking the signed-in person may not take is hidden,
        // and Filament refuses a hidden action even if called directly.
        Action::configureUsing(fn (Action $action) => $action->hidden(fn (): bool => WorkAccess::blocks($action)));

        $this->configureFlightRateLimits();
        $this->issueCompanyInvoices();
    }

    /**
     * A booking can become paid and confirmed from several places: checkout, the
     * gateway's callback, the admin. A failure here must never undo a booking,
     * so it's reported, and corporate:housekeeping issues the invoice later.
     */
    private function issueCompanyInvoices(): void
    {
        FlightBooking::saved(function (FlightBooking $booking): void {
            if (! $booking->company_id || ! ($booking->wasRecentlyCreated || $booking->wasChanged(['payment_status', 'booking_status', 'ticket_ordered']))) {
                return;
            }

            try {
                app(CompanyInvoices::class)->issueForBooking($booking);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * The same limits as a plain throttle:N,1 for public customers. A signed-in
     * company counts per person instead, because an office shares one IP address
     * and its bookers would otherwise use up each other's allowance.
     */
    private function configureFlightRateLimits(): void
    {
        foreach (['flights-search' => 12, 'flights-select' => 20, 'flights-book' => 10] as $name => $perMinute) {
            RateLimiter::for($name, function (Request $request) use ($name, $perMinute): Limit {
                $companyUser = app(CorporateContext::class)->user();

                return Limit::perMinute($perMinute)->by($name.':'.($companyUser
                    ? 'company-user:'.$companyUser->id
                    : ($request->user()?->getAuthIdentifier() ?? $request->ip())));
            });
        }

        // This route has no session, so a company's search counts by search.
        RateLimiter::for('flights-supplier', function (Request $request): Limit {
            $searchId = (string) $request->route('search');
            $isCompanySearch = FlightSearch::query()->whereKey($searchId)->whereNotNull('company_id')->exists();

            return Limit::perMinute(60)->by('flights-supplier:'.($isCompanySearch ? 'search:'.$searchId : $request->ip()));
        });
    }
}

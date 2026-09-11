<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->blockTravelNextRequests();
    }

    /**
     * DEMO BRANCH — tripwire.
     *
     * Every TravelNext call site was removed from the customer-facing flow on
     * this branch, but the Filament admin still holds TravelNext-only code
     * (AdminTicketingService, AdminPostTicketingService,
     * AdminReplacementFlightSearchService) that was left untouched rather than
     * risk breaking the admin panel for a throwaway demo.
     *
     * This makes "no TravelNext traffic" provable instead of merely intended:
     * any outbound HTTP request to a TravelNext host throws before it leaves
     * the process, so an overlooked path fails loudly in the log rather than
     * quietly reaching a supplier that should not exist on this deployment.
     *
     * Delete this method along with the branch.
     */
    private function blockTravelNextRequests(): void
    {
        Http::globalRequestMiddleware(function ($request) {
            $host = strtolower((string) $request->getUri()->getHost());

            if (str_contains($host, 'travelnext')) {
                Log::critical('DEMO BRANCH: blocked an outbound TravelNext request', [
                    'host' => $host,
                    'path' => $request->getUri()->getPath(),
                ]);

                throw new RuntimeException(
                    'TravelNext is disabled on this deployment (demo/skylink-only). '
                    .'Blocked a request to: '.$host.$request->getUri()->getPath()
                );
            }

            return $request;
        });
    }
}

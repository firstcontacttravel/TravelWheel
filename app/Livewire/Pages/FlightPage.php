<?php

namespace App\Livewire\Pages;

use App\Services\SkylinkFlightService;
use App\Support\FlightMarkup;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class FlightPage extends Component
{
    /**
     * Nothing on this component is public state.
     *
     * Livewire serialises every public property into the wire:snapshot it
     * embeds in the page AND ships it back and forth on every subsequent
     * request. The search results are the biggest object on this page and are
     * needed exactly once — at first paint, to seed the Alpine component in
     * flight-result.blade.php. Holding them publicly meant the wire:init call
     * that fetches SkyLink first uploaded the entire TravelNext result set and
     * then had it echoed back: measured against a representative mapped flight
     * (2 segments plus fare breakdown, ~2.9 KB), a 40-flight result set is
     * ~117 KB each way, on top of ~199 KB of duplicate snapshot in the initial
     * HTML — pure overhead on a mobile uplink before SkyLink is even called.
     *
     * FlightController::search() writes these durable session keys before
     * redirecting here, so reading them per-render costs nothing and survives
     * refreshes, back-button navigation and Livewire re-renders just as well.
     * Reading the criteria from the session inside the action is also strictly
     * safer than trusting anything that made a round trip through the client.
     */
    protected function flightResults(): array
    {
        return session('flightResultsStore', []);
    }

    protected function searchParams(): array
    {
        return session('searchParamsStore', []);
    }

    /**
     * Fired via wire:init right after the initial render — TravelNext's
     * results are already on screen by the time this runs, so a slow or
     * failing SkyLink call never delays or breaks the page. The mapped
     * flights are pushed to the browser as an event; the Alpine component in
     * flight-result.blade.php merges/dedupes/re-sorts them into the visible
     * list.
     *
     * Kept in its own skylinkResultsStore key (REPLACED, not appended, on
     * every call) rather than flightResultsStore — a page reload re-fires
     * wire:init, and each call is a fresh, authoritative SkyLink search for
     * the same criteria, so the previous batch is simply stale, not a
     * duplicate to preserve. Appending onto flightResultsStore instead grew
     * it unbounded across reloads AND leaked straight into the next page
     * load's initial (undeduped) paint, since that's exactly what seeds
     * Alpine's allFlights at mount — confirmed via live browser testing.
     * FlightBookingController::select() reads this key for SkyLink fares.
     */
    public function loadSkylinkResults(): void
    {
        $this->dispatch('skylink-results-ready', flights: $this->skylinkFlights());
    }

    /**
     * Every failure mode returns an empty list rather than propagating.
     *
     * The whole point of loading SkyLink as a supplement is that it can never
     * degrade the page, so the guarantee has to cover the mapping and session
     * write too — not just the HTTP call. Those two used to sit outside the
     * try, which meant something as ordinary as an ExchangeRate lookup failing
     * inside FlightMarkup::apply() returned a 500 for the wire:init request.
     */
    private function skylinkFlights(): array
    {
        // Kill switch — see config/services.php's skylink.enabled. Off by
        // default: deploying this code changes nothing for real customers
        // until this is explicitly turned on, and flipping it back off is
        // instant (no redeploy) if anything looks wrong during live testing.
        $searchParams = $this->searchParams();

        if (! config('services.skylink.enabled') || $searchParams === []) {
            return [];
        }

        try {
            $result = app(SkylinkFlightService::class)->search($searchParams);

            if ($result['error'] ?? true) {
                return [];
            }

            $flights = array_values(array_map(
                fn (array $flight): array => FlightMarkup::apply($flight),
                (array) data_get($result, 'data.flights', [])
            ));

            session(['skylinkResultsStore' => $flights]);

            return $flights;
        } catch (Throwable $exception) {
            Log::warning('SkyLink supplemental search failed', [
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return [];
        }
    }

    public function render()
    {
        return view('livewire.pages.flight.flight-page-result', [
            'flightResults' => $this->flightResults(),
            'searchParams' => $this->searchParams(),
            'searchSessionId' => session('searchSessionId', ''),
        ]);
    }
}

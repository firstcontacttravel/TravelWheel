<?php

namespace App\Livewire\Pages;

use App\Services\SkylinkFlightService;
use App\Support\FlightMarkup;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class FlightPage extends Component
{
    public array  $flightResults   = [];
    public array  $searchParams    = [];
    public string $searchSessionId = '';

    public function mount(): void
    {
        // ── Read exclusively from the durable session keys ────────────────────
        // FlightController::search() writes these before redirecting here.
        // Because they are durable (not flash), they survive page refreshes,
        // back-button navigation, and Livewire re-renders.
        $this->flightResults   = session('flightResultsStore', []);
        $this->searchParams    = session('searchParamsStore',  []);
        $this->searchSessionId = session('searchSessionId',    '');
    }

    /**
     * Fired via wire:init right after the initial render — TravelNext's
     * results (above) are already on screen by the time this runs, so a
     * slow or failing SkyLink call never delays or breaks the page. The
     * mapped flights are pushed to the browser as an event; the Alpine
     * component in flight-result.blade.php merges/dedupes/re-sorts them
     * into the visible list.
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
        // Kill switch — see config/services.php's skylink.enabled. Off by
        // default: deploying this code changes nothing for real customers
        // until this is explicitly turned on, and flipping it back off is
        // instant (no redeploy) if anything looks wrong during live testing.
        if (! config('services.skylink.enabled') || $this->searchParams === []) {
            $this->dispatch('skylink-results-ready', flights: []);

            return;
        }

        try {
            $result = app(SkylinkFlightService::class)->search($this->searchParams);
        } catch (Throwable $exception) {
            Log::warning('SkyLink supplemental search threw unexpectedly', [
                'error' => $exception->getMessage(),
            ]);
            $this->dispatch('skylink-results-ready', flights: []);

            return;
        }

        if ($result['error'] ?? true) {
            $this->dispatch('skylink-results-ready', flights: []);

            return;
        }

        $flights = array_values(array_map(
            fn (array $flight): array => FlightMarkup::apply($flight),
            (array) data_get($result, 'data.flights', [])
        ));

        session(['skylinkResultsStore' => $flights]);

        $this->dispatch('skylink-results-ready', flights: $flights);
    }

     public function render()
    {
       //dd($this->searchParams);
        return view('livewire.pages.flight.flight-page-result', [
            'flightResults'   => $this->flightResults,
            'searchParams'    => $this->searchParams,
            'searchSessionId' => $this->searchSessionId,
        ]);
    }
}
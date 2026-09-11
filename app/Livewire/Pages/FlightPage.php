<?php

namespace App\Livewire\Pages;

use Livewire\Component;

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
     * DEMO BRANCH — deliberately a no-op.
     *
     * On the main branch this fired via wire:init to search SkyLink as a
     * live supplement to TravelNext's already-rendered results, then pushed
     * the extra flights to the browser for Alpine to merge in.
     *
     * This branch has no TravelNext leg: FlightController::performSearch()
     * queries SkyLink synchronously and hands mount() a complete result set,
     * so there is nothing left to supplement. The wire:init binding is gone
     * from flight-page-result.blade.php, but the method is kept as a no-op
     * so a stale cached view or an in-flight Livewire request can never fire
     * a second, duplicate search against SkyLink — which would double every
     * search in their logs and make the integration look broken.
     */
    public function loadSkylinkResults(): void
    {
        $this->dispatch('skylink-results-ready', flights: []);
    }

    public function render()
    {
        return view('livewire.pages.flight.flight-page-result', [
            'flightResults'   => $this->flightResults,
            'searchParams'    => $this->searchParams,
            'searchSessionId' => $this->searchSessionId,
        ]);
    }
}

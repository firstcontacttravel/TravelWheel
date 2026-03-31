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

     public function render()
    {
        
        return view('livewire.pages.flight.flight-page-result', [
            'flightResults'   => $this->flightResults,
            'searchParams'    => $this->searchParams,
            'searchSessionId' => $this->searchSessionId,
        ]);
    }
}
<?php

namespace App\Livewire\Pages\Lounge;

use App\Models\Lounge as LoungeProduct;
use App\Services\LoungePairCatalogueSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class LoungeResults extends Component
{
    public string $location = '';

    public ?int $airportType = null;

    public ?string $service = null;

    public Collection $lounges;

    public function mount(LoungePairCatalogueSyncService $sync): void
    {
        $this->location = (string) request()->query('state', '');
        $airport = request()->query('airport');
        $this->airportType = $airport !== null ? (int) $airport : null;
        $this->service = request()->query('service');

        $query = LoungeProduct::where('location', $this->location)
            ->where('airport', $this->airportType);

        if ($this->service) {
            $query->where(function ($q) {
                $q->where('service', $this->service)->orWhereNull('service');
            });
        }

        $this->lounges = $query->latest()->get();

        if ($this->lounges->isNotEmpty()) {
            return;
        }

        // No admin-managed lounge for this state/airport — fall back to
        // LoungePair using the IATA code the controller resolved from the
        // selected state, rather than dead-ending on "no lounge found".
        $iata = strtoupper((string) request()->query('iata', ''));

        if (! preg_match('/^[A-Z]{3}$/', $iata)) {
            return;
        }

        Log::info('[Lounge] local search had no results, falling back to LoungePair', [
            'state' => $this->location,
            'iata' => $iata,
        ]);

        try {
            $sync->sync($iata);
            $this->redirect(route('air.lounge.global.results', ['iata' => $iata]));
        } catch (\Throwable $exception) {
            Log::warning('[Lounge] LoungePair fallback failed, showing empty local results instead', [
                'iata' => $iata,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.pages.lounge.lounges', ['lounges' => $this->lounges])
            ->layout('layouts.app', ['title' => 'Available Lounges - TravelWheel']);
    }
}

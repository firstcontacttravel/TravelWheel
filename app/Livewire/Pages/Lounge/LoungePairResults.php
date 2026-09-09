<?php

namespace App\Livewire\Pages\Lounge;

use App\Models\Lounge as LoungeProduct;
use Illuminate\Support\Collection;
use Livewire\Component;

class LoungePairResults extends Component
{
    public string $iata = '';

    public bool $isNigeria = false;

    /** @var ''|'domestic'|'international' */
    public string $typeFilter = '';

    public Collection $lounges;

    public function mount(): void
    {
        $iata = strtoupper((string) request()->query('iata', ''));
        abort_unless(preg_match('/^[A-Z]{3}$/', $iata), 404);

        $this->iata = $iata;
        $this->lounges = LoungeProduct::query()
            ->where('provider', 'loungepair')
            ->where('provider_airport_iata', $this->iata)
            ->latest('provider_synced_at')
            ->get();

        // LoungePair only labels Domestic/International in a way we can
        // trust for Nigerian airports (see LoungePairCatalogueSyncService)
        // so the filter only appears there rather than misclassifying
        // airports elsewhere that don't split lounges this way.
        $this->isNigeria = $this->lounges
            ->contains(fn (LoungeProduct $lounge) => strcasecmp((string) data_get($lounge->provider_payload, 'airport.country'), 'Nigeria') === 0);
    }

    public function render()
    {
        // Named 'filteredLounges' rather than 'lounges' — Livewire auto-exposes
        // every public property to the view by name, so a local variable here
        // called 'lounges' would collide with (and lose to) the public
        // $lounges property holding the full, unfiltered set.
        $filteredLounges = match ($this->typeFilter) {
            'domestic' => $this->lounges->where('airport', 0),
            'international' => $this->lounges->where('airport', 1),
            default => $this->lounges,
        };

        return view('livewire.pages.lounge.lounge-pair-results', ['filteredLounges' => $filteredLounges])
            ->layout('layouts.app', ['title' => 'Available Lounges - TravelWheel']);
    }
}

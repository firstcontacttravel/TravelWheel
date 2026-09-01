<?php

namespace App\Livewire\Pages\Lounge;

use App\Models\Lounge as LoungeProduct;
use Illuminate\Support\Collection;
use Livewire\Component;

class LoungeResults extends Component
{
    public string $iata = '';

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
    }

    public function render()
    {
        return view('livewire.pages.lounge.lounges', ['lounges' => $this->lounges])
            ->layout('layouts.app', ['title' => 'Available Lounges - TravelWheel']);
    }
}

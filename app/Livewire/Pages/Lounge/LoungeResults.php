<?php

namespace App\Livewire\Pages\Lounge;

use App\Models\Lounge as LoungeProduct;
use Illuminate\Support\Collection;
use Livewire\Component;

class LoungeResults extends Component
{
    public string $location = '';

    public ?int $airportType = null;

    public ?string $terminal = null;

    public Collection $lounges;

    public function mount(): void
    {
        $this->location = (string) request()->query('state', '');
        $airport = request()->query('airport');
        $this->airportType = $airport !== null ? (int) $airport : null;
        $this->terminal = request()->query('terminal');

        $query = LoungeProduct::where('location', $this->location)
            ->where('airport', $this->airportType);

        if ($this->terminal) {
            $query->where('terminal', $this->terminal);
        }

        $this->lounges = $query->latest()->get();
    }

    public function render()
    {
        return view('livewire.pages.lounge.lounges', ['lounges' => $this->lounges])
            ->layout('layouts.app', ['title' => 'Available Lounges - TravelWheel']);
    }
}

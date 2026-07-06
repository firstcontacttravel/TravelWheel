<?php

namespace App\Livewire\Pages\Visa;

use App\Models\Country;
use App\Models\VisaDestination;
use Livewire\Component;

class Discovery extends Component
{
    public function render()
    {
        return view('livewire.pages.visa.widget', [
            'countries' => Country::query()->where('is_active', true)->whereNotNull('alpha2')->orderBy('name')->get(['id', 'name', 'alpha2']),
            'destinationCountries' => Country::query()
                ->where('is_active', true)
                ->whereNotNull('alpha2')
                ->whereHas('visaProducts', fn ($query) => $query->currentlyPublished())
                ->orderBy('name')
                ->get(['id', 'name', 'alpha2']),
            'regionalDestinations' => VisaDestination::query()->where('is_active', true)->whereHas('products', fn ($query) => $query->currentlyPublished())->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

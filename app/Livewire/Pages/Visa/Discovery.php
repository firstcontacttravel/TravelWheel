<?php

namespace App\Livewire\Pages\Visa;

use App\Models\Country;
use App\Services\VisaDiscoveryService;
use Livewire\Component;

class Discovery extends Component
{
    public function render(VisaDiscoveryService $discovery)
    {
        $countries = Country::query()->where('is_active', true)->whereNotNull('alpha2')->orderBy('name')->get(['id', 'name', 'alpha2']);
        $defaultNationality = $countries->firstWhere('alpha2', 'NG') ?? $countries->first();
        $selectedNationality = $countries->firstWhere('id', (int) old('nationality_id')) ?? $defaultNationality;
        $destinations = $selectedNationality
            ? $discovery->availableDestinationsForNationality($selectedNationality)
            : ['countries' => collect(), 'regions' => collect()];

        return view('livewire.pages.visa.widget', [
            'countries' => $countries,
            'destinationCountries' => $destinations['countries'],
            'regionalDestinations' => $destinations['regions'],
        ]);
    }
}

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
                ->where(function ($query): void {
                    $query->whereHas('visaProducts', fn ($products) => $products
                        ->currentlyPublished()
                        ->where('family', '!=', 'voa'))
                        ->orWhere(function ($nigeria): void {
                            $nigeria->where('alpha2', 'NG')
                                ->whereHas('visaProducts', fn ($products) => $products
                                    ->currentlyPublished()
                                    ->where('family', 'voa'));
                        });
                })
                ->orderBy('name')
                ->get(['id', 'name', 'alpha2']),
            'regionalDestinations' => VisaDestination::query()->where('is_active', true)->whereHas('products', fn ($query) => $query->currentlyPublished())->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

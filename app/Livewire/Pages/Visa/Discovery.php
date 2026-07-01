<?php

namespace App\Livewire\Pages\Visa;

use App\Models\Country;
use Livewire\Component;

class Discovery extends Component
{
    public function render()
    {
        return view('livewire.pages.visa.widget', [
            'countries' => Country::query()->where('is_active', true)->whereNotNull('alpha2')->orderBy('name')->get(['id', 'name', 'alpha2']),
        ]);
    }
}

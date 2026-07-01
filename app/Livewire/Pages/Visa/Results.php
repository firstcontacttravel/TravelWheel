<?php

namespace App\Livewire\Pages\Visa;

use Livewire\Component;

class Results extends Component
{
    public array $results = [];

    public array $searchParams = [];

    public function mount(): void
    {
        $this->results = session('visaResultsStore', []);
        $this->searchParams = session('visaSearchParamsStore', []);
    }

    public function render()
    {
        return view('livewire.pages.visa.results');
    }
}

<?php

namespace App\Livewire\Pages\CarHire;

use App\Services\CarFleetCatalogService;
use Livewire\Component;

class CarHire extends Component
{
    public array $categories = [];

    public array $transferVehicles = [];

    public array $typeThumbs = [];

    public function mount(CarFleetCatalogService $catalog): void
    {
        $this->categories = $catalog->categories();
        $this->transferVehicles = $catalog->transferVehicles();
        $this->typeThumbs = $catalog->typeThumbs($this->categories, $this->transferVehicles);
    }

    public function render()
    {
        return view('livewire.pages.carhire.carhire', [
            'categories' => $this->categories,
            'transferVehicles' => $this->transferVehicles,
            'typeThumbs' => $this->typeThumbs,
        ])->layout('layouts.app', ['title' => 'Car Hire & Transfers - TravelWheel']);
    }
}

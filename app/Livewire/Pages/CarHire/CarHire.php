<?php

namespace App\Livewire\Pages\CarHire;

use App\Services\CarFleetCatalogService;
use App\Support\GroundStates;
use Livewire\Attributes\Url;
use Livewire\Component;

class CarHire extends Component
{
    /** The state picked on the page; its fleet is what the page offers. */
    #[Url]
    public string $state = '';

    public array $openStates = [];

    public array $categories = [];

    public array $transferVehicles = [];

    public array $typeThumbs = [];

    public function mount(CarFleetCatalogService $catalog): void
    {
        $this->openStates = GroundStates::open();

        if (! in_array($this->state, $this->openStates, true)) {
            $this->state = '';

            return;
        }

        $this->categories = $catalog->categories($this->state);
        $this->transferVehicles = $catalog->transferVehicles($this->state);
        $this->typeThumbs = $catalog->typeThumbs($this->categories, $this->transferVehicles);
    }

    public function render()
    {
        $bookable = CarFleetCatalogService::bookableVehicleTypes();

        return view('livewire.pages.carhire.carhire', [
            'categories' => $this->categories,
            'transferVehicles' => $this->transferVehicles,
            'typeThumbs' => $this->typeThumbs,
            // Only the types with a car in the chosen state, per service
            'hireTypes' => array_values(array_filter($bookable, fn ($t) => ! empty($this->categories[$t]['items']))),
            'transferTypes' => array_values(array_filter($bookable, fn ($t) => ! empty($this->transferVehicles[$t]['items']))),
            'stateGroups' => GroundStates::grouped(),
            'stateName' => $this->state ? GroundStates::name($this->state) : null,
            'addressExample' => GroundStates::example($this->state ?: GroundStates::default()),
            'airportExample' => GroundStates::airportExample($this->state ?: GroundStates::default()),
            'openStateNames' => array_map(fn ($s) => GroundStates::name($s), $this->openStates),
        ])->layout('layouts.app', ['title' => 'Car Hire & Transfers - TravelWheel']);
    }
}

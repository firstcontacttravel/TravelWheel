<?php

namespace App\Livewire\Pages\AirCargo;

use Livewire\Component;

class AirCargoCreate extends Component
{
    public function render()
    {
        return view('livewire.pages.aircargo.create')
            ->layout('layouts.app', ['title' => 'Create Shipment - TravelWheel']);
    }
}

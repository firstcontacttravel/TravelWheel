<?php

namespace App\Livewire\Pages\AirCargo;

use Livewire\Component;

class AirCargo extends Component
{
    public function render()
    {
        return view('livewire.pages.aircargo.aircargo')
            ->layout('layouts.app', ['title' => 'Air Cargo - TravelWheel']);
    }
}

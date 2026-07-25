<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class AirportProtocolServiceTerms extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.airport-protocol-service-terms')
            ->layout('layouts.app', ['title' => 'Airport Protocol Service Terms - TravelWheel']);
    }
}

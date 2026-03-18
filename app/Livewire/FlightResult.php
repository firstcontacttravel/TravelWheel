<?php
namespace App\Livewire\FlightResult;

use Livewire\Component;

class FlightResult extends Component
{
    public function render()
    {
        return view('livewire.pages.flight-page-result')
            ->layout('layouts.app', [
                'title' => 'Flight Results — Lagos to Port Harcourt'
            ]);
    }
}
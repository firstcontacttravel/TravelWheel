<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class TravelInsuranceTerms extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.travel-insurance-terms')
            ->layout('layouts.app', ['title' => 'Travel Insurance Terms - TravelWheel']);
    }
}

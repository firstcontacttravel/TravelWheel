<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class TermsAndConditions extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.terms-and-conditions')
            ->layout('layouts.app', ['title' => 'Terms & Conditions - TravelWheel']);
    }
}

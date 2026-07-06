<?php

namespace App\Livewire\Pages\Insurance;

use Livewire\Component;

class Insurance extends Component
{
    public function render()
    {
        return view('livewire.pages.insurance.insurance')
            ->layout('layouts.app', ['title' => 'Travel Insurance - TravelWheel']);
    }
}

<?php

namespace App\Livewire\Pages\Lounge;

use Livewire\Component;

class LoungeGlobal extends Component
{
    public function render()
    {
        return view('livewire.pages.lounge.lounge-global')
            ->layout('layouts.app', ['title' => 'Worldwide Airport Lounges - TravelWheel']);
    }
}

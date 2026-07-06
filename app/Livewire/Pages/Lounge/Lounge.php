<?php

namespace App\Livewire\Pages\Lounge;

use Livewire\Component;

class Lounge extends Component
{
    public function render()
    {
        return view('livewire.pages.lounge.lounge')
            ->layout('layouts.app', ['title' => 'Airport Lounge - TravelWheel']);
    }
}

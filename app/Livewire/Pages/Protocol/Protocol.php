<?php

namespace App\Livewire\Pages\Protocol;

use Livewire\Component;

class Protocol extends Component
{
    public function render()
    {
        return view('livewire.pages.protocol.protocol')
            ->layout('layouts.app', ['title' => 'Airport Protocol Service - TravelWheel']);
    }
}

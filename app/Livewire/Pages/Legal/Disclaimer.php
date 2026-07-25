<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class Disclaimer extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.disclaimer')
            ->layout('layouts.app', ['title' => 'Disclaimer - TravelWheel']);
    }
}

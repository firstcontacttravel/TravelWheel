<?php

namespace App\Livewire\Pages\Support;

use Livewire\Component;

class Support extends Component
{
    public function render()
    {
        return view('livewire.pages.support.support')
            ->layout('layouts.app', ['title' => 'Support - TravelWheel']);
    }
}

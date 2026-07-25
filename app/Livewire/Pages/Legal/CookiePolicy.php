<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class CookiePolicy extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.cookie-policy')
            ->layout('layouts.app', ['title' => 'Cookie Policy - TravelWheel']);
    }
}

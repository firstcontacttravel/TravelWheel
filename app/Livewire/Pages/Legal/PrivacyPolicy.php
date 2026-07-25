<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class PrivacyPolicy extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.privacy-policy')
            ->layout('layouts.app', ['title' => 'Privacy Policy - TravelWheel']);
    }
}

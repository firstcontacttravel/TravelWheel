<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class PaySmallSmallAgreement extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.pay-small-small-agreement')
            ->layout('layouts.app', ['title' => 'Pay Small Small Agreement - TravelWheel']);
    }
}

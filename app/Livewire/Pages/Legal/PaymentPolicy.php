<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class PaymentPolicy extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.payment-policy')
            ->layout('layouts.app', ['title' => 'Payment Policy - TravelWheel']);
    }
}

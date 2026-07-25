<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class RefundCancellationPolicy extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.refund-cancellation-policy')
            ->layout('layouts.app', ['title' => 'Refund & Cancellation Policy - TravelWheel']);
    }
}

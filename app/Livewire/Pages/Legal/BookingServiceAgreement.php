<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Component;

class BookingServiceAgreement extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.booking-service-agreement')
            ->layout('layouts.app', ['title' => 'Booking & Service Agreement - TravelWheel']);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking;
use Livewire\Livewire;
use Tests\TestCase;

class FlightBookingWizardTest extends TestCase
{
    public function test_customisation_step_advances_to_review_and_back_returns_to_customisation(): void
    {
        session(['bookingFlight' => ['flight' => ['isRefundable' => true, 'price' => 100000]]]);

        Livewire::test(FlightBooking::class)
            ->set('step', 2)
            ->call('proceed')
            ->assertSet('step', 3)
            ->call('back')
            ->assertSet('step', 2);
    }

    public function test_customisation_step_exposes_real_customisation_actions(): void
    {
        Livewire::test(FlightBooking::class)
            ->set('step', 2)
            ->assertSee('Trip Customisation')
            ->assertSee('Add extra check-in bags')
            ->assertSee('Review Booking')
            ->assertDontSee('Traveller Details');
    }
}

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
            ->assertSee('Trip customisation')
            ->assertSee('Add extra check-in bags')
            ->assertSee('Review Booking')
            ->assertDontSee('Traveller details');
    }

    /**
     * The count used to sit in a read-only three-column counter that looked
     * like the adjustable one from the search form but had no controls. That
     * block is gone; the count now rides along on the Traveller details
     * heading, where it orients without implying it can be changed here.
     */
    public function test_passenger_count_is_shown_without_suggesting_it_can_be_adjusted(): void
    {
        Livewire::test(FlightBooking::class)
            ->assertSee('1 passenger')
            ->assertDontSee('adjust if needed')
            ->assertDontSee('bk-pax-counter');
    }
}

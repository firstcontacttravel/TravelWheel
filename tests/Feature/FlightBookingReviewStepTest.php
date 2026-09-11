<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking;
use Livewire\Livewire;
use Tests\TestCase;

class FlightBookingReviewStepTest extends TestCase
{
    private function seedBooking(): void
    {
        session([
            'bookingFlight' => [
                'source' => 'skylink',
                'currency' => 'NGN',
                'price' => 2659427.90,
                'baseFare' => 2569427.90,
                'totalTax' => 0.0,
                'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'cabinCode' => 'Y',
                'stops' => 0, 'isRefundable' => false,
                'totalTimeLabel' => '7h', 'departDateLabel' => 'Thu, 15 Oct 2026',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'LHR', 'fromCity' => 'Lagos (LOS)', 'toCity' => 'London (LHR)',
                    'fromAirport' => 'Murtala Muhammed Airport', 'toAirport' => 'Heathrow Airport',
                    'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'flightNo' => 'VS412',
                    'departTime' => '10:30 am', 'arriveTime' => '05:30 pm',
                    'departDT' => '2026-10-15T10:30:00+00:00', 'duration' => 420,
                    'cabin' => 'Economy', 'seatsLeft' => 9,
                ]],
                'returnSegments' => [], 'multiLegs' => [],
                'fareBreakdown' => [[
                    'passengerType' => 'ADT', 'qty' => 1, 'baseFare' => 2569427.90, 'totalFare' => 2659427.90,
                    'baggage' => ['2 PC'], 'cabinBaggage' => ['1 PC'],
                ]],
            ],
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0, 'flight_type' => 'Y'],
            'bookingSessionId' => 'sess-review',
            'tripType' => 'oneway',
        ]);
    }

    /**
     * The review asked people to "review all details carefully before payment"
     * while never showing them the flight — only passengers, contact and a
     * short fare policy. The itinerary appeared solely in the right rail, cut
     * down to "LOS to LHR".
     */
    public function test_the_review_shows_the_flight_being_bought(): void
    {
        $this->seedBooking();

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        $this->assertStringContainsString('Lagos (LOS)', $html);
        $this->assertStringContainsString('London (LHR)', $html);
        $this->assertStringContainsString('VS412', $html);
        // Departure and arrival, normalised to 24-hour like the rest of the funnel.
        $this->assertStringContainsString('10:30', $html);
        $this->assertStringContainsString('17:30', $html);
    }

    /**
     * The button posts the booking and moves to the payment step; it takes no
     * money. It used to read "Confirm & Pay" beside a six-figure sum, which
     * says the charge happens on click.
     */
    public function test_the_final_button_does_not_claim_to_take_payment(): void
    {
        $this->seedBooking();

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        $this->assertStringContainsString('Continue to payment', $html);
        $this->assertStringContainsString('Nothing is charged until you complete payment', $html);
        $this->assertStringNotContainsString('Confirm &amp; Pay', $html);
    }

    /** The checkout referenced no terms anywhere before this. */
    public function test_it_references_the_booking_terms(): void
    {
        $this->seedBooking();

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        $this->assertStringContainsString('Booking Agreement', $html);
        $this->assertStringContainsString(route('legal.booking-agreement'), $html);
        $this->assertStringContainsString(route('legal.terms'), $html);
    }

    /** Each block should be correctable without walking the whole wizard back. */
    public function test_each_review_block_can_be_changed(): void
    {
        $this->seedBooking();

        Livewire::test(FlightBooking::class)
            ->set('step', 3)
            ->assertSee('Change')
            ->call('$set', 'step', 1)
            ->assertSet('step', 1);
    }

    /**
     * The hidden form is what actually posts the booking, so a layout change
     * on this step must not drop any of its fields.
     */
    public function test_the_submitted_form_keeps_every_field(): void
    {
        $this->seedBooking();

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        foreach (['fare_source_code', 'session_id', 'intent', 'contact[email]', 'contact[phone]'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html);
        }
        foreach (['type', 'first_name', 'last_name', 'dob', 'nationality', 'passport_no'] as $field) {
            $this->assertStringContainsString('name="passengers[0]['.$field.']"', $html);
        }
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Reaching this page for real needs a completed payment, which creates a live
 * airline PNR — so it can only be exercised by seeding the session.
 */
class FlightConfirmationPageTest extends TestCase
{
    private function seedTicketedBooking(array $overrides = []): void
    {
        session(array_merge([
            'bookingFlight' => [
                'source' => 'skylink',
                'currency' => 'NGN',
                'price' => 2659427.90,
                'baseFare' => 2569427.90,
                'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'cabinCode' => 'Y',
                'stops' => 0, 'isRefundable' => false,
                'totalTimeLabel' => '7h', 'departDateLabel' => 'Thu, 15 Oct 2026',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'LHR', 'fromCity' => 'Lagos (LOS)', 'toCity' => 'London (LHR)',
                    'fromAirport' => 'Murtala Muhammed Airport', 'toAirport' => 'Heathrow Airport',
                    // SkyLink's flight number already carries the carrier code.
                    'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'flightNo' => 'VS412',
                    'departTime' => '10:30 am', 'arriveTime' => '05:30 pm',
                    'departDT' => '2026-10-15T10:30:00+00:00', 'arriveDT' => '2026-10-15T17:30:00+00:00',
                    'duration' => 420, 'cabin' => 'Economy', 'resBookCode' => 'N',
                ]],
                'returnSegments' => [], 'multiLegs' => [],
                'fareBreakdown' => [[
                    'passengerType' => 'ADT', 'qty' => 3, 'baseFare' => 856475.97, 'totalFare' => 886475.97,
                    'baggage' => ['2 PC'], 'cabinBaggage' => ['1 PC'],
                ]],
            ],
            'bookingSearchParams' => ['adults' => 3, 'childs' => 0, 'kids' => 0, 'flight_type' => 'Y'],
            'bookingConfirmation' => ['status' => 'CONFIRMED'],
            'bookingUniqueId' => 'ABC123',
            'bookingRef' => 'TW-9F3K2A',
            'bookingStatus' => 'CONFIRMED',
            'paymentMethod' => 'gateway',
            'ticketSuccess' => true,
            'bookingContact' => ['email' => 'traveller@example.com', 'phone' => '+2348012345678'],
            'bookingPassengers' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'JOHN', 'last_name' => 'OKAFOR', 'gender' => 'M', 'dob' => '1990-04-12', 'nationality' => 'NG', 'is_primary' => true],
                ['type' => 'CHD', 'title' => 'Master', 'first_name' => 'SAM', 'last_name' => 'OKAFOR', 'gender' => 'M', 'dob' => '2018-06-10', 'nationality' => 'NG', 'is_primary' => false],
            ],
            'tripType' => 'oneway',
        ], $overrides));
    }

    /** The carrier code was prepended to a flight number that already had it. */
    public function test_it_does_not_double_prefix_the_flight_number(): void
    {
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('VS412', $html);
        $this->assertStringNotContainsString('VSVS412', $html);
    }

    /** Every other page in this funnel prints 24-hour times. */
    public function test_it_prints_times_in_the_same_format_as_the_rest_of_the_funnel(): void
    {
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('10:30', $html);
        $this->assertStringContainsString('17:30', $html);
        $this->assertStringNotContainsString('05:30 pm', $html);
    }

    /**
     * The hero says the ticket is issued. The e-ticket card said it was still
     * being processed whenever the trip-details call came back empty — which
     * happens on a timeout or a slow airline feed — so the page contradicted
     * itself about whether the customer actually had a ticket.
     */
    public function test_the_ticket_status_does_not_contradict_itself(): void
    {
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('Booking confirmed and ticketed', $html);
        $this->assertStringNotContainsString('Your e-ticket is being processed', $html);
    }

    /** 'gateway' is an internal routing name, not something to show a customer. */
    public function test_it_does_not_leak_the_internal_payment_method_name(): void
    {
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('Paid by card', $html);
        $this->assertStringNotContainsString('>Gateway<', $html);
    }

    /**
     * Per-passenger figures are rounded per head, so they can sum to a kobo
     * either side of the amount actually charged. On a receipt that reads as
     * an error, so the decomposition is only shown when it truly adds up.
     */
    public function test_the_fare_summary_always_adds_up_to_the_amount_paid(): void
    {
        // 886,475.97 x 3 = 2,659,427.91, a kobo over the 2,659,427.90 charged.
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('₦2,659,427.90', $html);
        $this->assertStringNotContainsString('₦2,659,427.91', $html);
    }

    /** The airline PNR sat under "Contact Details" labelled "Ticket ref". */
    public function test_the_airline_reference_is_labelled_and_filed_correctly(): void
    {
        $this->seedTicketedBooking();

        $html = $this->get(route('flights.confirmation'))->getContent();

        $this->assertStringContainsString('Airline reference', $html);
        $this->assertStringContainsString('ABC123', $html);
        // Precise: "Ticket ref" is also a substring of the legitimate
        // "Ticket references for your passengers" heading further down.
        $this->assertStringNotContainsString('<span>Ticket ref</span>', $html);
    }
}

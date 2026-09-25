<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * trip_details is a TravelNext endpoint and only recognises TravelNext booking
 * references. A SkyLink booking's unique_id is its SkyLink PNR, so sending it
 * there is a request that can only fail — and it fails silently, because
 * _callTripDetailsApi() turns every failure into an empty array. Nothing
 * visibly breaks; it is just real cross-supplier traffic on every SkyLink
 * confirmation, latency the customer waits through, and misleading entries in
 * TravelNext's logs.
 *
 * Every call site used to gate on the reference merely being present, which is
 * true of every booking whoever issued it.
 */
class TripDetailsSupplierIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.travelnext.base_url' => 'https://travelnext.test/api/aeroVE5/']);
        Http::preventStrayRequests();
        Http::fake(['*trip_details' => Http::response($this->tripDetailsResponse())]);
    }

    public function test_a_skylink_confirmation_never_calls_travelnext(): void
    {
        $this->bookingFor('skylink');

        $this->get(route('flights.confirmation'))->assertOk();

        Http::assertNothingSent();
    }

    public function test_a_travelnext_confirmation_still_calls_travelnext(): void
    {
        // The guard has to be a supplier check, not a blanket removal — this is
        // what would fail if someone "fixed" the leak by deleting the call.
        $this->bookingFor('travelnext');

        $this->get(route('flights.confirmation'))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'trip_details')
            && $request['UniqueID'] === 'TN-UNIQUE-1');
    }

    public function test_a_booking_with_no_recorded_supplier_is_treated_as_travelnext(): void
    {
        // Every booking predating the supplier column is a TravelNext one, so
        // an unknown supplier must keep working rather than silently losing its
        // trip details.
        $booking = $this->bookingFor('travelnext');
        $booking->forceFill(['supplier' => ''])->save();
        session()->forget('bookingFlight');

        $this->get(route('flights.confirmation'))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'trip_details'));
    }

    private function bookingFor(string $supplier): FlightBooking
    {
        $booking = FlightBooking::create([
            'booking_ref' => 'TW-'.strtoupper($supplier).'-1',
            'fare_source_code' => 'fsc-1',
            'supplier' => $supplier,
            'fare_type' => 'Public',
            'unique_id' => $supplier === 'skylink' ? 'SKY-PNR-1' : 'TN-UNIQUE-1',
            'payment_reference' => 'PAY-1',
            'payment_gateway' => 'seerbit',
            'payment_amount' => 750000,
            'payment_currency' => 'NGN',
            'total_price' => 750000,
            'currency' => 'NGN',
            'payment_method' => 'gateway',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
            'contact_email' => 'traveller@example.test',
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
            ]],
            'flight_snapshot' => [
                'source' => $supplier,
                'currency' => 'NGN',
                'price' => 750000,
                'segments' => [['from' => 'LOS', 'to' => 'DXB']],
            ],
        ]);

        session([
            'flightBookingDbId' => $booking->id,
            'bookingUniqueId' => $booking->unique_id,
            'paymentMethod' => 'gateway',
            'bookingFlight' => ['flight' => $booking->flight_snapshot],
        ]);

        return $booking;
    }

    private function tripDetailsResponse(): array
    {
        return ['TripDetailsResponse' => ['TripDetailsResult' => [
            'Success' => true,
            'TravelItinerary' => ['BookingStatus' => 'TICKETED', 'TicketStatus' => 'TICKETED'],
        ]]];
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking;
use App\Services\Flights\FlightSupplierControl;
use App\Services\SkylinkFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The review step and the payment page each describe the trip being bought.
 * The payment page printed the outbound's first flight number on every leg
 * (a return read AT554 for AT801) and the review listed the outbound only —
 * blank for a SkyLink multi-city, whose top-level segments are empty.
 */
class FlightCheckoutLegsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded off (phpunit.xml); the payment page refuses a switched-off API.
        app(FlightSupplierControl::class)->enable(SkylinkFlightService::KEY, null);
    }

    public function test_the_payment_page_names_each_legs_own_flights(): void
    {
        $html = $this->withSession($this->bookingSession($this->roundTrip('skylink')))
            ->get(route('flights.payment.gateway'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/Outbound.*Royal Air Maroc AT554, AT800.*Return.*Royal Air Maroc AT801/s', $html);
        $this->assertStringContainsString('Royal Air Maroc AT801', $html);
        $this->assertSame(1, substr_count($html, 'AT554'), 'The outbound flight number is printed on the return too.');
    }

    /** SkyLink issues tickets outside its API; paying reserves the seat, nothing more. */
    public function test_the_payment_page_does_not_promise_skylink_an_automatic_ticket(): void
    {
        $this->withSession($this->bookingSession($this->roundTrip('skylink')))
            ->get(route('flights.payment.gateway'))
            ->assertOk()
            ->assertDontSeeText('issued automatically')
            ->assertDontSeeText('Ticket issued as soon as payment clears')
            ->assertSeeText('Seat reserved as soon as payment clears')
            ->assertSeeText('your e-ticket is emailed to you once the airline issues it');
    }

    public function test_the_payment_page_keeps_the_travelnext_wording(): void
    {
        $this->withSession($this->bookingSession($this->roundTrip('travelnext')))
            ->get(route('flights.payment.gateway'))
            ->assertOk()
            ->assertSeeText('Ticket issued as soon as payment clears');
    }

    public function test_the_review_lists_the_return_of_a_round_trip(): void
    {
        session($this->bookingSession($this->roundTrip('skylink')));

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        $this->assertStringContainsString('AT554, AT800', $html);
        $this->assertStringContainsString('AT801', $html);
        $this->assertStringContainsString('Return', $html);
        $this->assertStringContainsString('Tue, 08 Dec 2026', $html);
    }

    public function test_the_review_lists_every_leg_of_a_skylink_multi_city(): void
    {
        $leg = fn (string $from, string $to, string $no, string $date) => [
            'segments' => [$this->segment($from, $to, $no, '09:00 am', '01:00 pm')],
            'departDateLabel' => $date, 'totalTimeLabel' => '4h',
        ];
        session($this->bookingSession(array_merge($this->roundTrip('skylink'), [
            'segments' => [], 'returnSegments' => [],
            'multiLegs' => [
                $leg('LOS', 'LHR', 'AT1', 'Fri, 20 Nov 2026'),
                $leg('LHR', 'DXB', 'AT2', 'Fri, 27 Nov 2026'),
                $leg('DXB', 'LOS', 'AT3', 'Sat, 05 Dec 2026'),
            ],
        ])));

        $html = Livewire::test(FlightBooking::class)->set('step', 3)->html();

        foreach (['Leg 1', 'Leg 2', 'Leg 3', 'AT1', 'AT2', 'AT3', 'Dubai (DXB)', 'Sat, 05 Dec 2026'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    private function roundTrip(string $source): array
    {
        return [
            'fareSourceCode' => 'fs-legs',
            'source' => $source,
            'fareType' => $source === 'travelnext' ? 'WebFare' : 'Public',
            'currency' => 'NGN',
            'price' => 846219.68,
            'baseFare' => 816219.68,
            'totalTax' => 0.0,
            'airline' => 'Royal Air Maroc', 'airlineCode' => 'AT', 'cabinCode' => 'Y',
            'stops' => 1, 'isRefundable' => false,
            'departDateLabel' => 'Tue, 24 Nov 2026', 'totalTimeLabel' => '11h 5m',
            'returnDateLabel' => 'Tue, 08 Dec 2026', 'returnTotalTimeLabel' => '11h 20m',
            'segments' => [
                $this->segment('LOS', 'CMN', 'AT554', '06:45 am', '11:30 am'),
                $this->segment('CMN', 'LHR', 'AT800', '01:40 pm', '04:50 pm'),
            ],
            'returnSegments' => [$this->segment('LHR', 'LOS', 'AT801', '05:10 pm', '05:30 am')],
            'multiLegs' => [],
            'fareBreakdown' => [['passengerType' => 'ADT', 'qty' => 1, 'baseFare' => 816219.68, 'totalFare' => 846219.68]],
        ];
    }

    private function segment(string $from, string $to, string $flightNo, string $departs, string $arrives): array
    {
        $cities = ['LOS' => 'Lagos', 'CMN' => 'Casablanca', 'LHR' => 'London', 'DXB' => 'Dubai'];

        return [
            'from' => $from, 'to' => $to,
            'fromCity' => $cities[$from].' ('.$from.')', 'toCity' => $cities[$to].' ('.$to.')',
            'airline' => 'Royal Air Maroc', 'airlineCode' => 'AT', 'flightNo' => $flightNo,
            'departTime' => $departs, 'arriveTime' => $arrives, 'cabin' => 'Economy',
        ];
    }

    private function bookingSession(array $flight): array
    {
        return [
            'bookingFlight' => $flight,
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0, 'flight_type' => 'Y'],
            'bookingSessionId' => 'sess-legs',
            'tripType' => empty($flight['multiLegs']) ? 'return' : 'multi',
        ];
    }
}

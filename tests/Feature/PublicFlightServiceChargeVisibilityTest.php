<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class PublicFlightServiceChargeVisibilityTest extends TestCase
{
    private array $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flight = [
            'airline' => 'Test Air',
            'currency' => 'NGN',
            'price' => 120000,
            'supplierPrice' => 100000,
            'markupAmount' => 20000,
            'markupPassengerCount' => 1,
            'fareBreakdown' => [[
                'passengerType' => 'ADT',
                'qty' => 1,
                'baseFare' => 80000,
                'totalFare' => 100000,
                'serviceTax' => 20000,
                'taxBreakdown' => [],
            ]],
            'segments' => [[
                'from' => 'LOS',
                'to' => 'ABV',
                'fromCity' => 'Lagos',
                'toCity' => 'Abuja',
                'departDT' => '2026-09-10T08:00:00',
                'arriveDT' => '2026-09-10T09:15:00',
                'departTime' => '08:00',
                'arriveTime' => '09:15',
                'duration' => 75,
                'flightNo' => 'TA101',
                'airline' => 'Test Air',
                'airlineCode' => 'TA',
                'cabin' => 'Economy',
                'cabinCode' => 'Y',
            ]],
        ];
    }

    public function test_booking_rail_hides_service_charge_but_keeps_it_in_total(): void
    {
        session([
            'bookingFlight' => ['flight' => $this->flight],
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
        ]);

        Livewire::test(FlightBooking::class)
            ->assertDontSee('Service charge')
            ->assertSee('120,000.00');
    }

    public function test_payment_rail_hides_service_charge_but_keeps_it_in_total(): void
    {
        $this->view('livewire.pages.flight.flight-payment-options', [
            'flight' => $this->flight,
            'extrasTotal' => 0,
            'errors' => new ViewErrorBag,
        ])
            ->assertDontSee('Service charge')
            ->assertSee('120,000.00');
    }

    public function test_confirmation_rail_hides_service_charge_but_keeps_it_in_total(): void
    {
        $this->view('livewire.pages.flight.flight-confirmation', [
            'flight' => $this->flight,
            'bookingRef' => 'TW-TEST-001',
            'paymentMethod' => 'gateway',
            'ticketSuccess' => true,
            'tripDetails' => [],
        ])
            ->assertDontSee('Service charge')
            ->assertSee('120,000.00');
    }
}

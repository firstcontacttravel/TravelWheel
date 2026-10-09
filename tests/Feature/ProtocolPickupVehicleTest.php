<?php

namespace Tests\Feature;

use App\Http\Controllers\ProtocolController;
use App\Models\Protocol;
use App\Support\ProtocolVehicles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProtocolPickupVehicleTest extends TestCase
{
    use RefreshDatabase;

    private function protocol(string $airport): Protocol
    {
        // Fill whatever required columns this test doesn't care about
        $required = collect(Schema::getColumns('protocols'))
            ->filter(fn ($c) => ! $c['nullable'] && $c['default'] === null && ! $c['auto_increment'])
            ->mapWithKeys(fn ($c) => [$c['name'] => preg_match('/int|decimal|double|float|numeric/', $c['type']) ? 0 : 'x'])
            ->except(['id', 'created_at', 'updated_at']);

        return Protocol::forceCreate([...$required->all(), 'id' => 1, 'location' => 'Lagos', 'airport' => $airport, 'service' => 'Departure']);
    }

    private function checkoutData(array $extra): array
    {
        return [
            'location' => 'Lagos', 'airport' => 'International Airport', 'service' => 'Departure', 'plan' => '1',
            'travel_date' => '2026-11-02', 'd_time' => '10:00', 'airline' => 'EMIRATES', 'nop' => 1,
            'phone_no' => '8011111111', 'email' => 'ada@example.com', 'c_amount' => '50000', 'amount' => '50000',
            ...$extra,
        ];
    }

    public function test_the_form_offers_each_vehicle_with_its_price(): void
    {
        ProtocolVehicles::setPrice('saloon', 25000);
        ProtocolVehicles::setPrice('suv', 40000);
        $this->protocol('International');

        $this->withSession(['protocol_data' => ['location' => 'Lagos', 'airport' => 'International Airport', 'service' => 'Departure']])
            ->get(route('air.protocolForm', ['plan' => 1]))
            ->assertOk()
            ->assertSee('Saloon Comfort')->assertSee('₦25,000')
            ->assertSee('SUV Business')->assertSee('₦40,000')
            ->assertSee('Mini Van')->assertSee('Price on request')     // no price set yet
            ->assertSee('name="optional_vehicleD"', false)
            ->assertSee('not included in the protocol amount');
    }

    public function test_checkout_shows_the_chosen_vehicle_without_adding_it_to_the_total(): void
    {
        ProtocolVehicles::setPrice('suv', 40000);

        $this->post(route('air.protocol_checkout'), $this->checkoutData(['optinal_requestD' => 'Pick-up & Escort', 'optional_vehicleD' => 'suv']))
            ->assertOk()
            ->assertSee('Pick-Up Details')
            ->assertSee('value="SUV Business"', false)
            ->assertSee('name="vehicle_price" value="40000"', false)
            ->assertSee('not included in the amount above')
            ->assertDontSee('₦90,000');   // the vehicle is not added to the protocol amount
    }

    public function test_a_pickup_without_a_vehicle_is_sent_back(): void
    {
        $this->from(route('air.protocolForm', ['plan' => 1]))
            ->post(route('air.protocol_checkout'), $this->checkoutData(['optinal_requestD' => 'Pick-up']))
            ->assertRedirect(route('air.protocolForm', ['plan' => 1]))
            ->assertSessionHas('error');
    }

    public function test_police_escort_alone_needs_no_vehicle(): void
    {
        $this->post(route('air.protocol_checkout'), $this->checkoutData(['optinal_requestD' => 'Police Escort']))
            ->assertOk()
            ->assertDontSee('Pick-Up Details');
    }

    public function test_the_booking_records_the_vehicle_and_its_quoted_price(): void
    {
        $summary = (fn (array $form) => $this->vehicleSummary($form))
            ->call(new ProtocolController, ['pickUpVehicle' => 'Mini Van', 'seaters' => 'Up to 5 Seaters', 'vehicle_price' => '55000']);

        $this->assertSame('Mini Van (Up to 5 Seaters) - ₦55,000, not included in protocol amount', $summary);
    }
}

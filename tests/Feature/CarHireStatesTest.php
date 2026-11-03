<?php

namespace Tests\Feature;

use App\Filament\Resources\FleetCars\Pages\CreateFleetCar;
use App\Livewire\Pages\CarHire\CarHire as CarHirePage;
use App\Models\CarHire;
use App\Models\FleetCar;
use App\Models\Transfer;
use App\Models\User;
use App\Services\SeerbitPaymentService;
use App\Support\GroundStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CarHireStatesTest extends TestCase
{
    use RefreshDatabase;

    private function car(string $state, string $service, string $type, int $year, string $name = 'Toyota Corolla'): FleetCar
    {
        return FleetCar::create([
            'service_type' => $service,
            'state' => $state,
            'vehicle_type' => $type,
            'year' => $year,
            'car_name' => $name,
            'is_active' => true,
        ]);
    }

    private function fakeSeerbit(): void
    {
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldReceive('initializePayment')
            ->andReturn(['redirect_link' => 'https://pay.example.test/checkout']));
    }

    private function carHireRequest(array $overrides = []): array
    {
        return array_merge([
            'state' => 'abuja',
            'car_type' => 'saloon',
            'category' => 'Standard',
            'rental_hours' => 4,
            'full_name' => 'Ada Obi',
            'email' => 'ada@example.com',
            'phone_number' => '08030000000',
            'passengers' => 2,
            'pickup_location' => 'Wuse 2, Abuja',
            'pickup_date' => now()->addDays(3)->toDateString(),
            'pickup_time' => '10:00',
            'payment_option' => 'seerbit',
        ], $overrides);
    }

    public function test_existing_cars_are_in_lagos_and_only_states_with_cars_are_open(): void
    {
        $car = FleetCar::create(['service_type' => 'car_hire', 'vehicle_type' => 'saloon', 'year' => 2018, 'car_name' => 'Toyota Camry', 'is_active' => true]);
        $this->assertSame('lagos', $car->fresh()->state);

        $this->car('abuja', 'transfer', 'suv', 2021)->update(['is_active' => false]);

        $this->assertSame(['lagos'], GroundStates::open());
    }

    public function test_adding_a_car_in_abuja_opens_abuja_with_only_its_own_vehicles(): void
    {
        $this->car('lagos', 'car_hire', 'suv', 2021, 'Toyota Prado');
        $this->car('abuja', 'car_hire', 'saloon', 2018, 'Honda Accord');

        $this->assertSame(['lagos', 'abuja'], GroundStates::open());

        Livewire::withQueryParams(['state' => 'abuja'])->test(CarHirePage::class)
            ->assertSet('state', 'abuja')
            ->assertViewHas('hireTypes', ['saloon'])          // Lagos's SUV isn't offered in Abuja
            ->assertViewHas('transferTypes', [])
            ->assertViewHas('categories', fn ($c) => array_column($c['saloon']['items'], 'name') === ['Standard'])
            ->assertSee('Honda Accord')
            ->assertDontSee('Toyota Prado')
            ->assertSee('e.g. Wuse 2, Abuja')
            ->assertSee("Pick up 'n' Drop off isn't available in FCT Abuja yet", false);

        Livewire::withQueryParams(['state' => 'lagos'])->test(CarHirePage::class)
            ->assertViewHas('hireTypes', ['suv']);
    }

    public function test_the_picker_lists_only_states_with_an_active_car(): void
    {
        $this->car('lagos', 'car_hire', 'saloon', 2018);
        $this->car('abuja', 'transfer', 'suv', 2021);
        $this->car('kano', 'car_hire', 'saloon', 2018)->update(['is_active' => false]);

        $this->assertSame(['South West' => ['lagos' => 'Lagos'], 'North Central' => ['abuja' => 'FCT Abuja']], GroundStates::groupedOpen());
        $this->assertArrayHasKey('kano', GroundStates::all());       // admin can still pick any state

        $this->get(route('air.carhire'))
            ->assertOk()
            ->assertSee('<option value="lagos" >Lagos</option>', false)
            ->assertSee('<option value="abuja" >FCT Abuja</option>', false)
            ->assertDontSee('<option value="kano"', false)
            ->assertDontSee('<optgroup label="North West">', false);

        // An old link to a state with no cars just opens the picker
        Livewire::withQueryParams(['state' => 'kano'])->test(CarHirePage::class)
            ->assertSet('state', '')
            ->assertDontSee('<option value="kano"', false);
    }

    public function test_the_page_says_so_when_no_state_has_cars(): void
    {
        $this->get(route('air.carhire'))
            ->assertOk()
            ->assertSee('unavail-box show', false)
            ->assertSee('Ground transport', false)
            ->assertDontSee('<option value="lagos"', false);
    }

    public function test_a_car_hire_booking_records_its_state(): void
    {
        $this->car('abuja', 'car_hire', 'saloon', 2018);
        $this->fakeSeerbit();

        $this->post(route('air.carhire.submit'), $this->carHireRequest())
            ->assertRedirect('https://pay.example.test/checkout');

        $booking = CarHire::sole();
        $this->assertSame('abuja', $booking->state);
        $this->assertSame('New Car Hire Booking (FCT Abuja) — Ada Obi', (new \App\Mail\CarHireNotificationMail($booking))->build()->subject);
        $this->assertStringContainsString('FCT Abuja', (new \App\Mail\CarHireSuccessMail($booking))->render());
    }

    public function test_a_car_hire_category_with_no_car_in_that_state_is_refused(): void
    {
        $this->car('abuja', 'car_hire', 'saloon', 2018);
        $this->car('lagos', 'car_hire', 'suv', 2018);
        $this->fakeSeerbit();

        $this->from(route('air.carhire', ['state' => 'abuja']))
            ->post(route('air.carhire.submit'), $this->carHireRequest(['car_type' => 'suv']))
            ->assertRedirect(route('air.carhire', ['state' => 'abuja']))
            ->assertSessionHas('error');

        $this->post(route('air.carhire.submit'), $this->carHireRequest(['state' => 'kano']))
            ->assertSessionHasErrors('state');

        $this->assertSame(0, CarHire::count());
    }

    public function test_a_transfer_booking_is_checked_against_its_state(): void
    {
        $this->car('lagos', 'transfer', 'saloon', 2018);
        $this->car('abuja', 'transfer', 'saloon', 2021);
        $this->fakeSeerbit();

        $transfer = [
            'state' => 'abuja', 'vehicle_type' => 'saloon', 'category' => 'Standard',
            'distance_km' => 12.5, 'duration_mins' => 30,
            'pickup_location' => 'Nnamdi Azikiwe Airport', 'dropoff_location' => 'Wuse 2',
            'full_name' => 'Ada Obi', 'email' => 'ada@example.com', 'phone_number' => '08030000000',
            'passengers' => 2, 'pickup_date' => now()->addDays(3)->toDateString(), 'pickup_time' => '10:00',
            'payment_option' => 'seerbit',
        ];

        // Abuja has an Executive saloon only; the Standard one is in Lagos
        $this->post(route('air.transfer.submit'), $transfer)->assertSessionHas('error');
        $this->assertSame(0, Transfer::count());

        $this->post(route('air.transfer.submit'), ['category' => 'Executive'] + $transfer)
            ->assertRedirect('https://pay.example.test/checkout');
        $this->assertSame('abuja', Transfer::sole()->state);
    }

    public function test_admin_adds_a_vehicle_to_abuja_from_the_fleet_form(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateFleetCar::class)
            ->assertSchemaStateSet(['state' => 'lagos'])
            ->fillForm([
                'service_type' => 'car_hire',
                'state' => 'abuja',
                'vehicle_type' => 'suv',
                'year' => 2022,
                'car_name' => 'Toyota Highlander',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('abuja', FleetCar::sole()->state);
        $this->assertContains('abuja', GroundStates::open('car_hire'));
    }

    public function test_abuja_shows_its_own_fare_rules_and_lagos_keeps_the_usual_ones(): void
    {
        $abuja = GroundStates::fareRules('abuja');
        $titles = array_column($abuja, 'title');

        // Abuja's cancellation replaces the usual one, in the same place
        $this->assertSame('Cancellation', $titles[0]);
        $this->assertStringContainsString('40% convenience fee', implode(' ', $abuja[0]['items']));
        $this->assertStringNotContainsString('free of charge up to 5 hours', json_encode($abuja));
        $this->assertContains('Airport Trips (Abuja)', $titles);
        $this->assertContains('Baggage Allowance', $titles);                 // the usual sections stay
        $this->assertSame(count(array_unique($titles)), count($titles));

        $this->assertSame(config('carhire.fare_rules'), GroundStates::fareRules('lagos'));
        $this->assertSame(config('carhire.fare_rules'), GroundStates::fareRules(null));

        $this->car('abuja', 'car_hire', 'saloon', 2018);
        $this->car('lagos', 'car_hire', 'saloon', 2018);
        Livewire::withQueryParams(['state' => 'abuja'])->test(CarHirePage::class)
            ->assertSee('Rentals below 10 hours do not include an airport pick-up or drop-off.');
        Livewire::withQueryParams(['state' => 'lagos'])->test(CarHirePage::class)
            ->assertDontSee('Rentals below 10 hours')
            ->assertSee('Cancellation is free of charge up to 5 hours prior to the trip.');
    }

    public function test_the_confirmation_email_carries_the_fare_rules_of_the_booking_state(): void
    {
        $booking = new CarHire(['state' => 'abuja', 'car_type' => 'saloon', 'category' => 'Standard', 'full_name' => 'Ada Obi',
            'email' => 'ada@example.com', 'pickup_location' => 'Wuse 2', 'rental_hours' => 10, 'amount' => 100000,
            'payment_reference' => 'CARHIRETEST', 'payment_option' => 'seerbit', 'pickup_date' => now()->toDateString(), 'pickup_time' => '10:00']);
        $html = (new \App\Mail\CarHireSuccessMail($booking))->render();
        $this->assertStringContainsString('Ride-along airport trips are charged at 100% of the airport fee.', $html);
        $this->assertStringNotContainsString('free of charge up to 5 hours', $html);

        $transfer = new Transfer(['state' => 'lagos', 'vehicle_type' => 'saloon', 'vehicle_name' => 'Standard Saloon', 'category' => 'Standard',
            'full_name' => 'Ada Obi', 'email' => 'ada@example.com', 'pickup_location' => 'Airport', 'dropoff_location' => 'VI',
            'amount' => 50000, 'distance_km' => 20, 'duration_mins' => 40, 'payment_reference' => 'TRANSFERTEST',
            'payment_option' => 'seerbit', 'pickup_date' => now()->toDateString(), 'pickup_time' => '10:00']);
        $html = (new \App\Mail\TransferSuccessMail($transfer))->render();
        $this->assertStringContainsString('free of charge up to 5 hours', $html);
        $this->assertStringNotContainsString('(Abuja)', $html);
    }
}

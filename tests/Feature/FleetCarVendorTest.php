<?php

namespace Tests\Feature;

use App\Filament\Resources\FleetCars\Pages\CreateFleetCar;
use App\Filament\Resources\FleetCars\Pages\EditFleetCar;
use App\Livewire\Pages\CarHire\CarHire as CarHirePage;
use App\Models\FleetCar;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class FleetCarVendorTest extends TestCase
{
    use RefreshDatabase;

    /** An approved partner, with every required column filled. */
    private function approvedVendor(array $attributes = []): VendorApplication
    {
        $vendor = new VendorApplication;
        foreach (Schema::getColumns('vendor_applications') as $column) {
            if (! $column['nullable'] && $column['default'] === null && ! $column['auto_increment']) {
                $vendor->{$column['name']} = match (true) {
                    str_contains($column['type'], 'json') => [],
                    str_contains($column['type'], 'date') || str_contains($column['type'], 'time') => now(),
                    default => 'x'.$column['name'],
                };
            }
        }
        $vendor->forceFill(array_merge([
            'reference' => 'VND-TEST1',
            'status' => 'approved',
            'registered_name' => 'Cooperate Foot Limited',
            'trading_name' => 'Cooperate Foot',
            'contact_name' => 'Musa Bello',
            'contact_phone' => '+2349099990917',
            'contact_email' => 'info@cooperatefoot.ng',
            'booking_email' => 'bookings@cooperatefoot.ng',
            'vendor_code' => 'TWV-0001',
        ], $attributes))->save();

        return $vendor;
    }

    public function test_admin_records_the_vendor_and_rate_card_for_a_car(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $vendor = $this->approvedVendor();

        Livewire::test(CreateFleetCar::class)
            ->fillForm([
                'service_type' => 'car_hire',
                'state' => 'abuja',
                'vehicle_type' => 'saloon',
                'year' => 2013,
                'car_name' => 'Toyota Corolla',
                'colour' => 'Black, White and Grey',
                'is_active' => true,
                'vendor_application_id' => $vendor->id,
            ])
            // Picking the partner copies their contact details in
            ->assertSchemaStateSet([
                'vendor_name' => 'Cooperate Foot',
                'vendor_contact_name' => 'Musa Bello',
                'vendor_phone' => '+2349099990917',
                'vendor_email' => 'bookings@cooperatefoot.ng',
            ])
            ->set('data.vendor_hourly_rates', [
                ['hours' => 1, 'price' => 15000, 'note' => 'Extension only'],
                ['hours' => 10, 'price' => 100000, 'note' => null],
                ['hours' => 24, 'price' => 200000, 'note' => null],
            ])
            ->set('data.vendor_trip_rates', [
                ['service' => 'Domestic airport pickup / drop-off', 'price' => 50000],
                ['service' => 'International airport pickup', 'price' => 60000],
                ['service' => 'Train station pickup / drop-off', 'price' => 50000],
            ])
            ->set('data.vendor_notes', 'Model year 2013. Priority chauffeur +10,000.')
            ->call('create')
            ->assertHasNoFormErrors();

        $car = FleetCar::sole();
        $this->assertSame('Black, White and Grey', $car->colour);
        $this->assertSame($vendor->id, $car->vendor->id);
        $this->assertSame('Cooperate Foot', $car->vendor_name);
        $this->assertEquals([1, 10, 24], array_column($car->vendor_hourly_rates, 'hours'));
        $this->assertEquals(60000, collect($car->vendor_trip_rates)->firstWhere('service', 'International airport pickup')['price']);

        Livewire::test(EditFleetCar::class, ['record' => $car->getRouteKey()])
            ->assertSchemaStateSet(['vendor_name' => 'Cooperate Foot', 'vendor_notes' => 'Model year 2013. Priority chauffeur +10,000.']);
    }

    public function test_a_vendor_who_has_not_registered_can_be_typed_in(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->approvedVendor(['status' => 'received', 'trading_name' => 'Pending Motors']);

        Livewire::test(CreateFleetCar::class)
            ->assertDontSee('Pending Motors')                // only approved partners are offered
            ->fillForm([
                'service_type' => 'transfer',
                'state' => 'abuja',
                'vehicle_type' => 'suv',
                'year' => 2023,
                'car_name' => 'Toyota Prado',
                'is_active' => true,
                'vendor_name' => 'Cooperate Foot',
                'vendor_phone' => '+2349155556563',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $car = FleetCar::sole();
        $this->assertNull($car->vendor_application_id);
        $this->assertSame('+2349155556563', $car->vendor_phone);
    }

    public function test_customers_never_see_vendor_details(): void
    {
        FleetCar::create([
            'service_type' => 'car_hire', 'state' => 'abuja', 'vehicle_type' => 'saloon', 'year' => 2019,
            'car_name' => 'Toyota Corolla', 'is_active' => true,
            'vendor_name' => 'Cooperate Foot', 'vendor_phone' => '+2349099990917',
            'vendor_hourly_rates' => [['hours' => 10, 'price' => 123456]],
        ]);

        Livewire::withQueryParams(['state' => 'abuja'])->test(CarHirePage::class)
            ->assertViewHas('hireTypes', ['saloon'])
            ->assertDontSee('Cooperate Foot')
            ->assertDontSee('2349099990917')
            ->assertDontSee('123456');
    }
}

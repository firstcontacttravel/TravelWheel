<?php

namespace Tests\Feature;

use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Resources\FleetCars\FleetCarResource;
use App\Filament\Resources\FleetCars\Pages\ListFleetCars;
use App\Filament\Resources\Lounges\LoungeResource;
use App\Filament\Resources\Lounges\Pages\ListLounges;
use App\Filament\Resources\Protocols\Pages\ListProtocols;
use App\Filament\Resources\TransportRates\Pages\ListTransportRates;
use App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication;
use App\Models\Department;
use App\Models\FleetCar;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPriceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $department, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function car(): FleetCar
    {
        return FleetCar::create(['service_type' => 'car_hire', 'vehicle_type' => 'saloon', 'year' => 2018, 'car_name' => 'Toyota Camry', 'is_active' => true]);
    }

    public function test_ceo_operations_and_finance_can_edit_prices(): void
    {
        $car = $this->car();

        foreach ([User::factory()->create(['is_admin' => true]), $this->staff('operations'), $this->staff('finance')] as $user) {
            $this->actingAs($user);
            $this->get(ExchangeRateResource::getUrl('create'))->assertOk();
            $this->get(FleetCarResource::getUrl('edit', ['record' => $car]))->assertOk();
            $this->get(LoungeResource::getUrl('create'))->assertOk();
        }
    }

    public function test_other_staff_can_view_prices_but_not_change_them(): void
    {
        $car = $this->car();

        foreach ([$this->staff('customer-support'), $this->staff('it')] as $user) {
            $this->actingAs($user);

            $this->get(ExchangeRateResource::getUrl('index'))->assertOk();
            $this->get(ExchangeRateResource::getUrl('create'))->assertForbidden();
            $this->get(FleetCarResource::getUrl('index'))->assertOk()->assertSee('Toyota Camry');
            $this->get(FleetCarResource::getUrl('create'))->assertForbidden();
            $this->get(FleetCarResource::getUrl('edit', ['record' => $car]))->assertForbidden();

            Livewire::test(ListFleetCars::class)
                ->assertActionHidden('create')
                ->assertTableActionHidden('edit', $car)
                ->assertTableActionHidden('delete', $car);
        }
    }

    public function test_price_buttons_on_list_pages_are_refused_for_other_staff(): void
    {
        $this->actingAs($this->staff('customer-support'));
        Livewire::test(ListTransportRates::class)->assertActionHidden('fuelPumpPrice');
        Livewire::test(ListLounges::class)->assertActionHidden('loungePairMarkup');
        Livewire::test(ListProtocols::class)->assertActionHidden('vehiclePrices');

        $this->actingAs($this->staff('operations'));
        Livewire::test(ListTransportRates::class)->assertActionVisible('fuelPumpPrice');
        Livewire::test(ListLounges::class)->assertActionVisible('loungePairMarkup');
        Livewire::test(ListProtocols::class)->assertActionVisible('vehiclePrices');
    }

    public function test_only_the_ceo_approves_or_rejects_a_vendor(): void
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
        $vendor->forceFill(['reference' => 'VND-TEST1', 'status' => 'compliance_completed', 'services' => ['car_hire'], 'business_types' => [], 'booking_channels' => []])->save();

        foreach ([$this->staff('operations', ['is_department_head' => true]), $this->staff('finance')] as $user) {
            $this->actingAs($user);
            Livewire::test(ViewVendorApplication::class, ['record' => $vendor->getRouteKey()])
                ->assertOk()
                ->assertActionHidden('approve')
                ->assertActionHidden('reject')
                ->assertActionVisible('requestInformation');
        }

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ViewVendorApplication::class, ['record' => $vendor->getRouteKey()])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject');
    }
}

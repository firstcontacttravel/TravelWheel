<?php

namespace Tests\Feature;

use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PricingAdminConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uses_one_shared_exchange_rate_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(ExchangeRateResource::getUrl('index'))
            ->assertOk()
            ->assertSee('USD')
            ->assertSee('GBP')
            ->assertSee('EUR');

        $this->actingAs($admin)
            ->get(ExchangeRateResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Source currency')
            ->assertSee('NGN rate');

        $this->assertFalse(Route::has('filament.admin.resources.visa-exchange-rates.index'));
    }

    public function test_admin_can_open_all_per_passenger_flight_service_charges(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(FlightServiceChargeResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Domestic (within Nigeria)')
            ->assertSee('Starts in Nigeria')
            ->assertSee('Inbound / touches Nigeria')
            ->assertSee('Per passenger');

        $this->assertDatabaseCount('flight_service_charges', 16);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * DEMO BRANCH — the live supplement is gone.
 *
 * On the main branch SkyLink loaded as a supplement to TravelNext's already
 * painted results, fired via wire:init (FlightPage::loadSkylinkResults()).
 * This branch has no TravelNext leg: FlightController::performSearch()
 * queries SkyLink synchronously and mount() receives a complete result set,
 * so the supplement is a deliberate no-op and the wire:init binding is gone
 * from flight-page-result.blade.php.
 *
 * What still matters is that the method cannot fire a *second* SkyLink
 * search — a stale cached view or an in-flight Livewire request calling it
 * would otherwise double every search in SkyLink's own logs and make the
 * integration look broken during their review.
 */
class FlightPageSkylinkSupplementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_supplement_is_a_no_op_and_never_calls_skylink(): void
    {
        $this->configureSkylink();
        session([
            'searchParamsStore' => $this->searchParams(),
            'flightResultsStore' => [['fareSourceCode' => 'btk_test123', 'source' => 'skylink']],
            'skylinkResultsStore' => [['fareSourceCode' => 'btk_test123', 'source' => 'skylink']],
        ]);

        // Any outbound call at all fails the test: the supplement must not
        // reach the network, not even to log in.
        Http::preventStrayRequests();

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertDispatched('skylink-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);

        // The synchronous search's results are left exactly as they were.
        $this->assertSame(
            [['fareSourceCode' => 'btk_test123', 'source' => 'skylink']],
            session('skylinkResultsStore')
        );
    }
    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            // Explicit rather than relying on the real .env's value — this is
            // the production kill switch (default off), so tests that
            // exercise the enabled behavior must not depend on what happens
            // to be in the developer's own .env.
            'services.skylink.enabled' => true,
        ]);

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    private function searchParams(): array
    {
        return [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'Dubai (DXB)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
        ];
    }

    private function loginResponse(): array
    {
        return [
            'status' => 'success',
            'code' => 'LOGIN_SUCCESS',
            'data' => [
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'token_type' => 'Bearer',
                'expires_in' => 900,
            ],
        ];
    }

    private function searchResponse(): array
    {
        return [
            'success' => true,
            'data' => [
                'meta' => ['search_id' => 'abc12345', 'origin' => 'LOS', 'destination' => 'DXB', 'currency' => 'NGN'],
                'flights' => [[
                    'price' => 500000,
                    'actual_adult_base' => 500000,
                    'currency' => 'NGN',
                    'seats_left' => 4,
                    'booking_token' => 'btk_test123',
                    'segments' => [[
                        [
                            'img' => 'AT',
                            'flight_no' => 'AT576',
                            'airline' => 'Royal Air Maroc',
                            'class' => 'economy',
                            'class_letter' => 'O',
                            'baggage' => '30kg',
                            'departure_code' => 'LOS',
                            'departure_time' => '07:15 pm',
                            'departure_date' => '05-10-2026',
                            'arrival_code' => 'DXB',
                            'arrival_time' => '02:00 am',
                            'arrival_date' => '06-10-2026',
                            'duration_time' => '6h 45m',
                            'total_duration' => '6h 45m',
                            'seg_duration' => '6h 45m',
                            'seats_left' => 4,
                            'refundable' => 0,
                        ],
                    ]],
                ]],
            ],
        ];
    }
}

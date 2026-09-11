<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * DEMO BRANCH — the SkyLink-only search flow.
 *
 * On the main branch FlightController::performSearch() queries TravelNext
 * synchronously and SkyLink arrives afterwards as a wire:init supplement, so
 * SkyLink inventory can never stand on its own. This branch inverts that:
 * the search calls SkyLink directly and the supplement is gone.
 *
 * These tests pin the two properties the demo deployment depends on — that a
 * search really is served from SkyLink end to end, and that nothing on the
 * flow can reach TravelNext.
 */
class DemoSkylinkOnlySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_search_is_served_entirely_from_skylink(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        $this->post(route('flights.search'), $this->searchCriteria())
            ->assertRedirect(route('flights.search.loading'));

        $this->get(route('flights.search.run'))
            ->assertRedirect(route('air.flight-s'));

        $flights = session('flightResultsStore');

        $this->assertCount(1, $flights);
        $this->assertSame('skylink', $flights[0]['source']);
        $this->assertSame('btk_test123', $flights[0]['fareSourceCode']);

        // Markup ran: the supplier's NGN fare came back as USD and was
        // converted and marked up into the retail NGN price shown on the card.
        $this->assertSame('NGN', $flights[0]['currency']);
        $this->assertGreaterThan(0, $flights[0]['price']);

        // The results page keys its x-for on flight.id. SkyLink's mapper does
        // not set one, so performSearch() has to — without it Alpine renders
        // every card against an undefined key.
        $this->assertSame(0, $flights[0]['id']);

        // _selectSkylinkFare() resolves the chosen fare from this separate
        // key; on main it is FlightPage::loadSkylinkResults() that writes it.
        $this->assertSame($flights, session('skylinkResultsStore'));

        // SkyLink has no session-id concept — select() and book() accept ''.
        $this->assertSame('', session('searchSessionId'));

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'travelnext'));
    }

    public function test_a_skylink_failure_returns_a_customer_safe_error(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response(['success' => false, 'message' => 'upstream exploded'], 500),
        ]);

        $this->post(route('flights.search'), $this->searchCriteria())
            ->assertRedirect(route('flights.search.loading'));

        $this->get(route('flights.search.run'))
            ->assertRedirect(route('air'));

        $this->assertNull(session('flightResultsStore'));
    }

    /**
     * The tripwire in AppServiceProvider is what makes "this deployment sends
     * no TravelNext traffic" provable rather than merely intended: the
     * Filament admin still holds TravelNext-only services that were left
     * untouched, so an overlooked path has to fail loudly instead of quietly
     * reaching a supplier that should not exist here.
     */
    public function test_any_outbound_travelnext_request_is_blocked(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TravelNext is disabled on this deployment');

        Http::post('https://travelnext.works/api/aeroVE5/availability', []);
    }

    private function searchCriteria(): array
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

    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.enabled' => true,
        ]);

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    private function loginResponse(): array
    {
        return ['status' => 'success', 'data' => ['access_token' => 'jwt-token']];
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

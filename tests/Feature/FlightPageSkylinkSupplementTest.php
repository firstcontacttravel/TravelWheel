<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 — SkyLink loads as a live supplement to TravelNext's results,
 * fired via wire:init after the page has already painted. These tests cover
 * the server side of that (FlightPage::loadSkylinkResults()); the client-side
 * merge/dedupe/scoring lives in flight-result.blade.php's Alpine component.
 */
class FlightPageSkylinkSupplementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_mapped_and_marked_up_skylink_flights(): void
    {
        $this->configureSkylink();
        session([
            'searchParamsStore' => $this->searchParams(),
            'flightResultsStore' => [['fareSourceCode' => 'tn-existing', 'source' => 'travelnext']],
            // A stale batch from a previous page load — must be replaced, not
            // appended to, otherwise repeated reloads accumulate duplicates.
            'skylinkResultsStore' => [['fareSourceCode' => 'btk_stale', 'source' => 'skylink']],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertDispatched('skylink-results-ready', function (string $name, array $params): bool {
                $flights = $params['flights'];

                return count($flights) === 1
                    && $flights[0]['source'] === 'skylink'
                    && $flights[0]['currency'] === 'NGN'
                    && $flights[0]['price'] > 0;
            });

        // flightResultsStore (what seeds the next page load's initial paint)
        // must never be touched by SkyLink — TravelNext's entries stay exactly
        // as they were.
        $this->assertSame([['fareSourceCode' => 'tn-existing', 'source' => 'travelnext']], session('flightResultsStore'));

        // select() resolves SkyLink fares from this separate, replaced-not-
        // appended key.
        $stored = session('skylinkResultsStore');
        $this->assertCount(1, $stored);
        $this->assertSame('btk_test123', $stored[0]['fareSourceCode']);
    }

    public function test_the_kill_switch_prevents_any_skylink_call_when_disabled(): void
    {
        $this->configureSkylink();
        config(['services.skylink.enabled' => false]);
        session(['searchParamsStore' => $this->searchParams()]);

        Http::fake();

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertDispatched('skylink-results-ready', function (string $name, array $params): bool {
                return $params['flights'] === [];
            });

        Http::assertNothingSent();
    }

    public function test_it_dispatches_an_empty_list_when_skylink_errors(): void
    {
        $this->configureSkylink();
        session(['searchParamsStore' => $this->searchParams()]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response(['success' => false, 'message' => 'down'], 500),
        ]);

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertDispatched('skylink-results-ready', function (string $name, array $params): bool {
                return $params['flights'] === [];
            });
    }

    public function test_it_dispatches_an_empty_list_when_there_is_no_pending_search(): void
    {
        $this->configureSkylink();

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertDispatched('skylink-results-ready', function (string $name, array $params): bool {
                return $params['flights'] === [];
            });
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

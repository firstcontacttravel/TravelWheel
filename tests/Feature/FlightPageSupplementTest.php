<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use App\Services\Flights\FlightSupplierControl;
use App\Services\SkylinkFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The APIs the loading page didn't reach load as live supplements, fired via
 * wire:init after the page has already painted. These tests cover the server
 * side of that (FlightPage::loadSupplementalResults()); the client-side
 * merge/dedupe/scoring lives in flight-result.blade.php's Alpine component.
 */
class FlightPageSupplementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_mapped_and_marked_up_supplement_flights(): void
    {
        $this->configureSkylink();
        session([
            'searchParamsStore' => $this->searchParams(),
            'searchSupplementSuppliers' => ['skylink'],
            'flightResultsStore' => [['fareSourceCode' => 'tn-existing', 'source' => 'travelnext']],
            // A stale batch from a previous page load — must be replaced, not
            // appended to, otherwise repeated reloads accumulate duplicates.
            'supplementResultsStore' => ['skylink' => [['fareSourceCode' => 'btk_stale', 'source' => 'skylink']]],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', function (string $name, array $params): bool {
                $flights = $params['flights'];

                return count($flights) === 1
                    && $flights[0]['source'] === 'skylink'
                    && $flights[0]['currency'] === 'NGN'
                    && $flights[0]['price'] > 0;
            });

        // flightResultsStore (what seeds the next page load's initial paint)
        // must never be touched by a supplement.
        $this->assertSame([['fareSourceCode' => 'tn-existing', 'source' => 'travelnext']], session('flightResultsStore'));

        // select() resolves supplement fares from this separate, replaced-not-
        // appended key.
        $stored = session('supplementResultsStore.skylink');
        $this->assertCount(1, $stored);
        $this->assertSame('btk_test123', $stored[0]['fareSourceCode']);
    }

    public function test_an_api_switched_off_after_the_search_began_is_skipped(): void
    {
        $this->configureSkylink();
        app(FlightSupplierControl::class)->disable(SkylinkFlightService::KEY, 'Supplier outage', null);
        session([
            'searchParamsStore' => $this->searchParams(),
            'searchSupplementSuppliers' => ['skylink'],
        ]);

        Http::fake();

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);

        Http::assertNothingSent();
    }

    public function test_an_api_the_loading_page_already_tried_is_not_searched_again(): void
    {
        $this->configureSkylink();
        session([
            'searchParamsStore' => $this->searchParams(),
            // The loading page reached every switched-on API.
            'searchSupplementSuppliers' => [],
        ]);

        Http::fake();

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);

        Http::assertNothingSent();
    }

    public function test_a_search_from_before_supplements_were_listed_still_gets_them(): void
    {
        $this->configureSkylink();
        // No searchSupplementSuppliers: a results page opened before the
        // deploy. Every switched-on API not already on the page is searched.
        session([
            'searchParamsStore' => $this->searchParams(),
            'flightResultsStore' => [['fareSourceCode' => 'tn-existing', 'source' => 'travelnext']],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', fn (string $name, array $params): bool => count($params['flights']) === 1);
    }

    public function test_travelnext_as_a_supplement_keeps_its_own_search_session(): void
    {
        session([
            'searchParamsStore' => $this->searchParams(),
            'searchSupplementSuppliers' => ['travelnext'],
            'searchSessionId' => '',
        ]);

        Http::fake([
            'travelnext.works/*' => Http::response([
                'AirSearchResponse' => [
                    'session_id' => 'tn-supplement-session',
                    'AirSearchResult' => ['FareItineraries' => []],
                ],
            ]),
        ]);

        Livewire::test(FlightPage::class)->call('loadSupplementalResults');

        // select() needs TravelNext's own session for a TravelNext fare, even
        // though another API filled the page.
        $this->assertSame('tn-supplement-session', session('supplierSearchMeta.travelnext.session_id'));
    }

    public function test_it_dispatches_an_empty_list_when_the_supplement_errors(): void
    {
        $this->configureSkylink();
        session([
            'searchParamsStore' => $this->searchParams(),
            'searchSupplementSuppliers' => ['skylink'],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response(['success' => false, 'message' => 'down'], 500),
        ]);

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);
    }

    public function test_it_dispatches_an_empty_list_when_there_is_no_pending_search(): void
    {
        $this->configureSkylink();

        Livewire::test(FlightPage::class)
            ->call('loadSupplementalResults')
            ->assertDispatched('supplier-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);
    }

    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            // See SkylinkFlightServiceTest::configureSkylink() — off so these
            // assert against a real call rather than a cache hit.
            'services.skylink.search_cache_ttl' => 0,
        ]);

        // Switched on explicitly: phpunit.xml seeds it off, as the production
        // default was, so nothing depends on the developer's own .env.
        app(FlightSupplierControl::class)->enable(SkylinkFlightService::KEY, null);

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

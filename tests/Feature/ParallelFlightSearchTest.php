<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use App\Models\FlightSearch;
use App\Models\FlightSearchResult;
use App\Services\Flights\FlightSearchStore;
use App\Services\Flights\FlightSupplierControl;
use App\Support\FlightMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ParallelFlightSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'flights.parallel_search' => true,
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.search_cache_ttl' => 0,
        ]);
        app(FlightSupplierControl::class)->enable('skylink', null);
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    // ── Starting a search ─────────────────────────────────────────────────

    public function test_search_goes_straight_to_the_results_page_without_calling_any_api(): void
    {
        Http::fake();

        $this->post(route('flights.search'), $this->searchForm())
            ->assertRedirect(route('air.flight-s'));

        $searchId = session(FlightSearchStore::SESSION_ID);
        $this->assertSame('parallel', session(FlightSearchStore::SESSION_MODE));
        $this->assertSame('Lagos (LOS)', FlightSearch::query()->findOrFail($searchId)->criteria['from']);
        $this->assertNull(session('pendingFlightSearch'));
        Http::assertNothingSent();
    }

    public function test_with_the_switch_off_the_loading_page_is_used_as_before(): void
    {
        config(['flights.parallel_search' => false]);

        $this->post(route('flights.search'), $this->searchForm())
            ->assertRedirect(route('flights.search.loading'));

        $this->assertDatabaseCount('flight_searches', 0);
    }

    public function test_the_results_page_hands_the_browser_one_request_per_api(): void
    {
        $searchId = $this->startSearch();

        Livewire::test(FlightPage::class)
            ->assertViewHas('flightResults', [])
            ->assertViewHas('parallel', fn (array $parallel): bool => $parallel['searchId'] === $searchId
                && array_keys($parallel['endpoints']) === ['travelnext', 'skylink'])
            ->assertViewHas('supplierOrder', ['travelnext', 'skylink'])
            ->assertDontSeeHtml('wire:init="loadSupplementalResults"');
    }

    public function test_a_parallel_search_stays_parallel_if_the_switch_is_turned_off_mid_search(): void
    {
        $this->startSearch();
        config(['flights.parallel_search' => false]);

        Livewire::test(FlightPage::class)
            ->assertViewHas('parallel', fn (?array $parallel): bool => $parallel !== null);
    }

    public function test_an_api_switched_off_before_the_page_opens_is_not_requested(): void
    {
        $this->startSearch();
        app(FlightSupplierControl::class)->disable('skylink', 'Outage', null);

        Livewire::test(FlightPage::class)
            ->assertViewHas('parallel', fn (array $parallel): bool => array_keys($parallel['endpoints']) === ['travelnext']);
    }

    // ── One API's answer ──────────────────────────────────────────────────

    public function test_an_api_answers_with_marked_up_tagged_flights_and_they_are_kept(): void
    {
        $searchId = $this->startSearch();
        $this->fakeTravelnext();

        $response = $this->getJson($this->endpoint($searchId, 'travelnext'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('flights.0.source', 'travelnext')
            ->assertJsonPath('flights.0.currency', 'NGN');

        $this->assertNotEmpty($response->json('flights.0.matchKey'));
        $this->assertSame(1, FlightSearchResult::query()->where('flight_search_id', $searchId)->value('flight_count'));
        $this->assertSame('tn-session', app(FlightSearchStore::class)->result($searchId, 'travelnext')['meta']['session_id']);

        // A reload is served from what was kept — the API isn't asked again.
        Http::fake();
        $this->getJson($this->endpoint($searchId, 'travelnext'))->assertJsonPath('status', 'ok')->assertJsonCount(1, 'flights');
        Http::assertNothingSent();
    }

    public function test_the_per_api_request_runs_without_a_session(): void
    {
        $searchId = $this->startSearch();
        $this->fakeTravelnext();

        // No session cookie means no session was started, so nothing this
        // request did could be saved over the customer's real session.
        $this->getJson($this->endpoint($searchId, 'travelnext'))
            ->assertOk()
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_a_switched_off_api_answers_off_without_being_called(): void
    {
        $searchId = $this->startSearch();
        app(FlightSupplierControl::class)->disable('skylink', 'Outage', null);
        Http::fake();

        $this->getJson($this->endpoint($searchId, 'skylink'))->assertOk()->assertJson(['status' => 'off', 'flights' => []]);
        Http::assertNothingSent();
    }

    public function test_a_failing_api_is_just_one_with_no_flights(): void
    {
        $searchId = $this->startSearch();
        Http::fake(['travelnext.works/*' => Http::response([], 500)]);

        $this->getJson($this->endpoint($searchId, 'travelnext'))->assertOk()->assertJson(['status' => 'error', 'flights' => []]);
        $this->assertDatabaseCount('flight_search_results', 0);
    }

    public function test_unknown_apis_and_expired_searches_are_refused(): void
    {
        $searchId = $this->startSearch();

        $this->getJson($this->endpoint($searchId, 'amadeus'))->assertNotFound();

        FlightSearch::query()->whereKey($searchId)->update(['expires_at' => now()->subMinute()]);
        $this->getJson($this->endpoint($searchId, 'travelnext'))->assertNotFound()->assertJsonPath('status', 'expired');
    }

    // ── Booking from a parallel search ────────────────────────────────────

    public function test_select_finds_the_fare_and_its_apis_own_search_session(): void
    {
        $searchId = $this->startSearch();
        $this->fakeTravelnext();
        $this->getJson($this->endpoint($searchId, 'travelnext'))->assertOk();

        $this->post(route('flights.select'), [
            'fare_source_code' => 'TN-1',
            // The page has no TravelNext session id to post back.
            'session_id' => '',
            'source' => 'travelnext',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'revalidate')
            && $request['session_id'] === 'tn-session'
            && $request['fare_source_code'] === 'TN-1');
    }

    public function test_a_refused_fare_is_offered_from_another_apis_kept_results(): void
    {
        $searchId = $this->startSearch();
        $store = app(FlightSearchStore::class);
        $store->put($searchId, 'travelnext', FlightMatch::tag([$this->flight('travelnext', 'TN-SAME', 820000)]));
        $store->put($searchId, 'skylink', FlightMatch::tag([$this->flight('skylink', 'btk_same', 790000)]));
        app(FlightSupplierControl::class)->disable('skylink', 'Outage', null);

        $this->post(route('flights.select'), ['fare_source_code' => 'btk_same', 'source' => 'skylink'])
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable', fn (array $notice): bool => $notice['alternate']['fareSourceCode'] === 'TN-SAME');
    }

    // ── Housekeeping ──────────────────────────────────────────────────────

    public function test_results_are_stored_compressed_and_read_back_intact(): void
    {
        $searchId = $this->startSearch();
        $flights = array_fill(0, 50, $this->flight('travelnext', 'TN-1', 820000.5));

        app(FlightSearchStore::class)->put($searchId, 'travelnext', $flights);

        $stored = FlightSearchResult::query()->where('flight_search_id', $searchId)->value('flights');
        $this->assertLessThan(strlen(json_encode($flights)) / 4, strlen($stored));
        $this->assertSame($flights, app(FlightSearchStore::class)->result($searchId, 'travelnext')['flights']);
    }

    public function test_expired_searches_and_results_are_pruned(): void
    {
        $searchId = $this->startSearch();
        app(FlightSearchStore::class)->put($searchId, 'travelnext', [$this->flight('travelnext', 'TN-1', 1.0)]);

        $this->travelTo(now()->addMinutes((int) config('flights.search_minutes') + 1));
        $this->artisan('model:prune', ['--model' => [FlightSearch::class, FlightSearchResult::class]])->assertSuccessful();

        $this->assertDatabaseCount('flight_searches', 0);
        $this->assertDatabaseCount('flight_search_results', 0);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function startSearch(): string
    {
        $this->post(route('flights.search'), $this->searchForm())->assertRedirect(route('air.flight-s'));

        return (string) session(FlightSearchStore::SESSION_ID);
    }

    private function endpoint(string $searchId, string $supplier): string
    {
        return route('flights.search.supplier', ['search' => $searchId, 'supplier' => $supplier]);
    }

    private function searchForm(): array
    {
        return [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
        ];
    }

    private function fakeTravelnext(): void
    {
        $depart = now()->addMonth()->setTime(10, 30);

        Http::fake(['travelnext.works/*' => Http::response(['AirSearchResponse' => [
            'session_id' => 'tn-session',
            'AirSearchResult' => ['FareItineraries' => [[
                'FareItinerary' => [
                    'ValidatingAirlineCode' => 'BA',
                    'AirItineraryFareInfo' => [
                        'FareSourceCode' => 'TN-1',
                        'ItinTotalFares' => [
                            'TotalFare' => ['Amount' => '500', 'CurrencyCode' => 'USD'],
                            'BaseFare' => ['Amount' => '400', 'CurrencyCode' => 'USD'],
                        ],
                        'FareBreakdown' => [],
                    ],
                    'OriginDestinationOptions' => [[
                        'OriginDestinationOption' => [[
                            'FlightSegment' => [
                                'DepartureDateTime' => $depart->toIso8601String(),
                                'ArrivalDateTime' => $depart->copy()->addHours(6)->toIso8601String(),
                                'JourneyDuration' => '360',
                                'MarketingAirlineCode' => 'BA',
                                'FlightNumber' => '75',
                                'DepartureAirportLocationCode' => 'LOS',
                                'ArrivalAirportLocationCode' => 'LHR',
                            ],
                        ]],
                    ]],
                ],
            ]]],
        ]])]);
    }

    private function flight(string $source, string $fareSourceCode, float $price): array
    {
        return [
            'source' => $source,
            'fareSourceCode' => $fareSourceCode,
            'airline' => 'British Airways',
            'cabin' => 'Economy',
            'price' => $price,
            'currency' => 'NGN',
            'segments' => [[
                'airlineCode' => 'BA',
                'flightNo' => 'BA75',
                'departDT' => $source === 'skylink' ? '2026-10-20T10:30:00+00:00' : '2026-10-20T10:30:00',
            ]],
        ];
    }
}

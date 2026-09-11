<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use App\Services\SkylinkFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SkyLink's search is a live supplement fired from wire:init after the results
 * page has already painted, and its latency is bimodal: measured over a week of
 * production traffic, 57 searches averaged 7.2s with five outliers all past 30s
 * (worst 35.2s) and nothing at all in between. Those outliers outlived the
 * request and surfaced to customers as a 500.
 *
 * These cover the three things that keep that from happening: a timeout budget
 * we control, a total refusal to propagate any failure out of the component,
 * and a short cache so an identical search doesn't pay the full round trip
 * twice.
 */
class SkylinkSearchResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_search_budget_stays_inside_a_typical_request_limit(): void
    {
        // 30s is the common shared-hosting max_execution_time. Exceeding it is
        // a PHP fatal, not a Throwable, so no catch block can turn it back into
        // the graceful empty result the supplement contract promises — the
        // budget has to stay under it. A cold token cache pays for a login
        // first, so that counts against the same budget.
        $budget = (int) config('services.skylink.search_timeout')
            + (int) config('services.skylink.auth_timeout');

        $this->assertGreaterThan(0, (int) config('services.skylink.search_timeout'));
        $this->assertLessThan(30, $budget);
    }

    public function test_reserve_is_given_more_room_than_search_not_less(): void
    {
        // Search is cut short deliberately — a customer waiting on supplemental
        // results loses nothing but those results. Reserve runs after payment
        // has been captured and issues a live PNR, so cutting it short leaves
        // us unable to say whether a ticket exists. It must never be the
        // tighter budget.
        $this->assertGreaterThan(
            (int) config('services.skylink.search_timeout'),
            (int) config('services.skylink.reserve_timeout'),
        );
    }

    public function test_a_timed_out_search_is_reported_as_a_failed_call_not_an_exception(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        $result = app(SkylinkFlightService::class)->search($this->searchCriteria());

        $this->assertTrue($result['error']);
        $this->assertDatabaseHas('flight_supplier_calls', [
            'supplier' => 'skylink',
            'call_type' => 'search',
            'success' => false,
        ]);
    }

    public function test_an_identical_search_is_served_from_cache(): void
    {
        $this->configureSkylink(['services.skylink.search_cache_ttl' => 180]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        $service = app(SkylinkFlightService::class);
        $first = $service->search($this->searchCriteria());
        $second = $service->search($this->searchCriteria());

        $this->assertSame($first, $second);
        $this->assertCount(1, $this->searchRequests(), 'The second identical search should not reach SkyLink.');

        // A cache hit is not a supplier call and must not be logged as one —
        // flight_supplier_calls is what we measure real latency from.
        $this->assertDatabaseCount('flight_supplier_calls', 1);
    }

    public function test_a_different_search_is_not_served_from_cache(): void
    {
        $this->configureSkylink(['services.skylink.search_cache_ttl' => 180]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        $service = app(SkylinkFlightService::class);
        $service->search($this->searchCriteria());
        $service->search(array_merge($this->searchCriteria(), ['to' => 'ACC']));

        $this->assertCount(2, $this->searchRequests());
    }

    public function test_a_failed_search_is_never_cached(): void
    {
        $this->configureSkylink(['services.skylink.search_cache_ttl' => 180]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::sequence()
                ->push(['success' => false, 'message' => 'Upstream unavailable.'], 503)
                ->push($this->searchResponse()),
        ]);

        $service = app(SkylinkFlightService::class);
        $this->assertTrue($service->search($this->searchCriteria())['error']);

        // Caching the failure would lock every customer out of SkyLink for the
        // full TTL over one transient blip.
        $this->assertFalse($service->search($this->searchCriteria())['error']);
    }

    public function test_a_broken_exchange_rate_does_not_turn_the_supplement_into_a_500(): void
    {
        $this->configureSkylink(['services.skylink.enabled' => true]);
        session(['searchParamsStore' => $this->searchCriteria()]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        // FlightMarkup::apply() and the session write used to sit outside the
        // try/catch, so anything failing there — an ExchangeRate lookup during
        // a DB blip being the obvious one — returned a 500 for the wire:init
        // request rather than the empty list the supplement contract promises.
        Schema::drop('exchange_rates');

        Livewire::test(FlightPage::class)
            ->call('loadSkylinkResults')
            ->assertOk()
            ->assertDispatched('skylink-results-ready', fn (string $name, array $params): bool => $params['flights'] === []);
    }

    public function test_the_results_page_does_not_ship_the_flight_list_through_livewire_state(): void
    {
        session([
            'searchParamsStore' => $this->searchCriteria(),
            'flightResultsStore' => [['fareSourceCode' => 'tn-1', 'source' => 'travelnext']],
        ]);

        // Public properties are serialised into the wire:snapshot and sent up
        // and back on every subsequent request — including the wire:init that
        // fetches SkyLink. The result set is needed once, at first paint, so it
        // is read from the session rather than held as component state.
        $component = Livewire::test(FlightPage::class);

        $this->assertSame(
            [],
            $component->snapshot['data'],
            'FlightPage should hold no public state, so nothing rides along in the snapshot.',
        );

        // Still rendered, just from the session rather than component state.
        $component->assertSee('tn-1', false);
    }

    /**
     * The 500s reported on round-trip searches, reproduced on staging.
     *
     * The server caps request bodies at post_max_size = 1M. Livewire POSTs the
     * whole component snapshot on every request, and the snapshot carried the
     * result set, so the wire:init call that fetches SkyLink grew with the
     * number of flights found. Past 1 MiB, PHP discards the body, Livewire
     * cannot find its payload, and Laravel returns a 500. Measured live, same
     * route and date, one passenger:
     *
     *   LOS->JFK one way     36 flights    192 KB snapshot   223 KB body   200
     *   LOS->JFK round trip 126 flights    982 KB snapshot 1,142 KB body   500
     *
     * Round trips crossed the line first because they return roughly 3.5x the
     * flights and each is ~1.6x larger (it carries returnSegments too), but
     * nothing about the trip type was ever the real cause — size was. So the
     * guarantee worth testing is not "smaller" but "does not grow at all":
     * the body has to stay flat no matter how many flights a search returns.
     */
    public function test_the_livewire_payload_does_not_grow_with_the_number_of_flights(): void
    {
        session(['searchParamsStore' => $this->searchCriteria()]);

        $measure = function (int $flights): int {
            session(['flightResultsStore' => array_fill(0, $flights, $this->bulkyFlight())]);

            return strlen(json_encode(Livewire::test(FlightPage::class)->snapshot));
        };

        $small = $measure(1);
        $huge = $measure(200);

        $this->assertSame(
            $small,
            $huge,
            'The snapshot grew with the result count — the payload is back in Livewire state.',
        );

        // Far below the 1 MiB body cap even before Livewire's own wrapper.
        $this->assertLessThan(4096, $huge);
    }

    /**
     * Shaped and sized like a real round-trip result (~7.5 KB each, as measured
     * on staging) so 200 of them would blow well past the cap if they were
     * still being serialised into component state.
     */
    private function bulkyFlight(): array
    {
        $segment = array_fill_keys(
            ['from', 'to', 'fromCity', 'toCity', 'fromAirport', 'toAirport', 'airline', 'airlineLogo'],
            str_repeat('x', 120),
        );

        return [
            'fareSourceCode' => str_repeat('f', 180),
            'segments' => [$segment, $segment],
            'returnSegments' => [$segment, $segment],
            'fareBreakdown' => [array_fill_keys(['baggage', 'cabinBaggage'], str_repeat('b', 120))],
        ];
    }

    private function searchRequests(): Collection
    {
        return collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn ($request) => str_contains($request->url(), '/flights/search'));
    }

    private function configureSkylink(array $overrides = []): void
    {
        config(array_merge([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.search_cache_ttl' => 0,
        ], $overrides));

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    private function searchCriteria(): array
    {
        return [
            'trip' => 'oneway',
            'from' => 'LOS',
            'to' => 'NBO',
            'depart' => '25/09/2026',
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
        ];
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
                'flights' => [[
                    'booking_token' => 'btk_test123',
                    'total_price' => 750000,
                    'currency' => 'NGN',
                    'segments' => [
                        'outbound' => [[
                            'departure_code' => 'LOS',
                            'arrival_code' => 'NBO',
                            'departure_date' => '25-09-2026',
                            'departure_time' => '07:15 pm',
                            'arrival_date' => '26-09-2026',
                            'arrival_time' => '01:20 am',
                            'seg_duration' => '6h 5m',
                            'flight_no' => '533',
                            'img' => 'KQ',
                        ]],
                    ],
                ]],
                'meta' => [],
            ],
        ];
    }
}

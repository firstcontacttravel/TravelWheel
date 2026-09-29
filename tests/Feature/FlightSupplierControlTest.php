<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightSuppliers\FlightSupplierResource;
use App\Filament\Resources\FlightSuppliers\Pages\ListFlightSuppliers;
use App\Models\FlightSupplierEvent;
use App\Models\FlightSupplierSetting;
use App\Models\User;
use App\Services\Flights\FlightSupplierControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class FlightSupplierControlTest extends TestCase
{
    use RefreshDatabase;

    private const SEARCH = [
        'trip' => 'oneway',
        'from' => 'Lagos (LOS)',
        'to' => 'London (LHR)',
        'adults' => 1,
        'childs' => 0,
        'kids' => 0,
        'flight_type' => 'Y',
    ];

    // ── The switches ──────────────────────────────────────────────────────

    public function test_the_migration_keeps_travelnext_first_and_skylink_as_it_was(): void
    {
        // phpunit.xml sets SKYLINK_ENABLED=false, standing in for production.
        $this->assertSame(['travelnext'], $this->control()->enabledKeys());
        $this->assertSame(['travelnext', 'skylink'], FlightSupplierSetting::query()->orderBy('priority')->pluck('key')->all());
        $this->assertSame(2, FlightSupplierEvent::query()->where('action', 'registered')->count());
    }

    public function test_switching_off_takes_effect_on_the_next_read_and_is_recorded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->control()->enable('skylink', $admin);
        $this->assertSame(['travelnext', 'skylink'], $this->control()->enabledKeys());

        $this->control()->disable('travelnext', 'Wallet top-up pending', $admin);

        // Cached state was dropped by the save — no wait for a TTL.
        $this->assertSame(['skylink'], $this->control()->enabledKeys());

        $setting = FlightSupplierSetting::query()->where('key', 'travelnext')->sole();
        $this->assertSame('Wallet top-up pending', $setting->disabled_reason);
        $this->assertSame($admin->id, $setting->disabled_by);

        $event = FlightSupplierEvent::query()->where('supplier_key', 'travelnext')->latest('id')->first();
        $this->assertSame('disabled', $event->action);
        $this->assertSame('Wallet top-up pending', $event->reason);
        $this->assertSame($admin->id, $event->user_id);
    }

    public function test_a_reason_is_required_and_a_switch_on_time_must_be_ahead(): void
    {
        try {
            $this->control()->disable('travelnext', '   ', null);
            $this->fail('A blank reason was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $this->control()->disable('travelnext', 'Maintenance', null, now()->subMinute());
    }

    public function test_switching_on_clears_the_reason_and_remembers_it_in_history(): void
    {
        $this->control()->disable('travelnext', 'Maintenance window', null);
        $this->control()->enable('travelnext', null);

        $setting = FlightSupplierSetting::query()->where('key', 'travelnext')->sole();
        $this->assertTrue($setting->enabled);
        $this->assertNull($setting->disabled_reason);
        $this->assertNull($setting->disabled_at);

        $event = FlightSupplierEvent::query()->latest('id')->first();
        $this->assertSame('enabled', $event->action);
        $this->assertSame('Maintenance window', $event->details['was_off_for']);
    }

    public function test_a_scheduled_switch_on_counts_from_its_time_and_the_job_records_it(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->control()->disable('travelnext', 'Supplier maintenance', null, now()->addHour());
        $this->assertSame([], $this->control()->enabledKeys());

        $this->travelTo(now()->addHour());

        // On for customers from the due time, before the job has run.
        $this->assertSame(['travelnext'], $this->control()->enabledKeys());

        $this->artisan('flights:re-enable-suppliers')->assertSuccessful();

        $this->assertTrue(FlightSupplierSetting::query()->where('key', 'travelnext')->value('enabled'));
        $this->assertSame('re_enabled_on_schedule', FlightSupplierEvent::query()->latest('id')->value('action'));

        // Nothing left to do on the next run.
        $this->assertSame(0, $this->control()->reEnableDue());
    }

    public function test_moving_changes_the_search_order_and_is_recorded(): void
    {
        $this->control()->enable('skylink', null);
        $this->control()->move('skylink', -1, null);

        $this->assertSame(['skylink', 'travelnext'], $this->control()->enabledKeys());
        $this->assertSame(
            ['skylink' => 1, 'travelnext' => 2],
            FlightSupplierSetting::query()->pluck('priority', 'key')->sort()->all(),
        );

        $event = FlightSupplierEvent::query()->latest('id')->first();
        $this->assertSame('moved', $event->action);
        $this->assertSame(['from' => 2, 'to' => 1], array_intersect_key($event->details, ['from' => 0, 'to' => 0]));

        // Already first: nothing moves, nothing is recorded.
        $events = FlightSupplierEvent::query()->count();
        $this->control()->move('skylink', -1, null);
        $this->assertSame($events, FlightSupplierEvent::query()->count());
    }

    public function test_a_newly_registered_api_arrives_switched_off_and_last(): void
    {
        config(['flights.suppliers.second_travelnext' => \Tests\Fixtures\SecondTravelnextSupplier::class]);

        $this->assertNotContains('second_travelnext', $this->control()->enabledKeys());

        $this->control()->ensureRows();

        $row = FlightSupplierSetting::query()->where('key', 'second_travelnext')->sole();
        $this->assertFalse($row->enabled);
        $this->assertSame(3, $row->priority);
    }

    // ── The search, in priority order ─────────────────────────────────────

    public function test_a_failing_first_api_hands_the_search_to_the_next(): void
    {
        $this->control()->enable('skylink', null);
        $this->fakeSuppliers(travelnext: Http::response(['error' => 'down'], 500), skylink: $this->skylinkFlights());

        $this->search()->assertRedirect(route('air.flight-s'));

        $this->assertSame(['skylink'], array_unique(array_column(session('flightResultsStore'), 'source')));
        // Tried on the loading page already, so nothing is left to supplement.
        $this->assertSame([], session('searchSupplementSuppliers'));
    }

    public function test_an_empty_first_api_hands_the_search_to_the_next(): void
    {
        $this->control()->enable('skylink', null);
        $this->fakeSuppliers(travelnext: $this->travelnextFlights([]), skylink: $this->skylinkFlights());

        $this->search()->assertRedirect(route('air.flight-s'));

        $this->assertNotEmpty(session('flightResultsStore'));
        $this->assertSame('skylink', session('flightResultsStore.0.source'));
    }

    public function test_the_first_api_with_flights_leaves_the_rest_as_supplements(): void
    {
        $this->control()->enable('skylink', null);
        $this->fakeSuppliers(travelnext: $this->travelnextFlights(), skylink: $this->skylinkFlights());

        $this->search()->assertRedirect(route('air.flight-s'));

        $this->assertSame('travelnext', session('flightResultsStore.0.source'));
        $this->assertSame(['skylink'], session('searchSupplementSuppliers'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'flights/search'));
    }

    public function test_a_switched_off_api_is_never_called(): void
    {
        $this->control()->enable('skylink', null);
        $this->control()->disable('travelnext', 'Supplier outage', null);
        $this->fakeSuppliers(travelnext: $this->travelnextFlights(), skylink: $this->skylinkFlights());

        $this->search()->assertRedirect(route('air.flight-s'));

        $this->assertSame('skylink', session('flightResultsStore.0.source'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'travelnext'));
    }

    public function test_priority_decides_which_api_fills_the_page(): void
    {
        $this->control()->enable('skylink', null);
        $this->control()->move('skylink', -1, null);
        $this->fakeSuppliers(travelnext: $this->travelnextFlights(), skylink: $this->skylinkFlights());

        $this->search()->assertRedirect(route('air.flight-s'));

        $this->assertSame('skylink', session('flightResultsStore.0.source'));
        $this->assertSame(['travelnext'], session('searchSupplementSuppliers'));
    }

    public function test_every_api_switched_off_refuses_the_search_up_front(): void
    {
        $this->control()->disable('travelnext', 'Supplier outage', null);
        Http::fake();

        $this->post(route('flights.search'), self::SEARCH + ['depart' => now()->addMonth()->format('d/m/Y')])
            ->assertRedirect(route('air'))
            ->assertSessionHasErrors(['error' => 'Flight search is temporarily unavailable. Please try again later.']);

        $this->assertNull(session('pendingFlightSearch'));
        Http::assertNothingSent();
    }

    public function test_every_api_failing_reports_the_first_apis_error(): void
    {
        $this->control()->enable('skylink', null);
        $this->fakeSuppliers(travelnext: Http::response([], 500), skylink: Http::response(['success' => false], 500));

        $this->search()
            ->assertRedirect(route('air'))
            ->assertSessionHasErrors(['error' => 'Flight search failed. Please try again.']);
    }

    // ── The admin screen ──────────────────────────────────────────────────

    public function test_only_full_administrators_can_open_flight_apis(): void
    {
        $support = User::factory()->create(['is_admin' => false, 'visa_role' => 'support']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($support)->get(FlightSupplierResource::getUrl('index'))->assertForbidden();

        $this->actingAs($admin)
            ->get(FlightSupplierResource::getUrl('index'))
            ->assertOk()
            ->assertSee('TravelNext')
            ->assertSee('SkyLink');
    }

    public function test_an_admin_switches_an_api_off_with_a_reason_and_on_again(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $travelnext = FlightSupplierSetting::query()->where('key', 'travelnext')->sole();

        Livewire::test(ListFlightSuppliers::class)
            ->callTableAction('switchOff', $travelnext, data: ['reason' => 'Funding', 're_enable_at' => null])
            ->assertHasNoTableActionErrors();

        $this->assertFalse($travelnext->fresh()->enabled);
        $this->assertSame('Funding', $travelnext->fresh()->disabled_reason);

        Livewire::test(ListFlightSuppliers::class)
            ->assertTableActionHidden('switchOff', $travelnext)
            ->callTableAction('switchOn', $travelnext);

        $this->assertTrue($travelnext->fresh()->enabled);
    }

    public function test_switching_off_without_a_reason_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $travelnext = FlightSupplierSetting::query()->where('key', 'travelnext')->sole();

        Livewire::test(ListFlightSuppliers::class)
            ->callTableAction('switchOff', $travelnext, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);

        $this->assertTrue($travelnext->fresh()->enabled);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function control(): FlightSupplierControl
    {
        return app(FlightSupplierControl::class);
    }

    private function search()
    {
        $this->post(route('flights.search'), self::SEARCH + ['depart' => now()->addMonth()->format('d/m/Y')])
            ->assertRedirect(route('flights.search.loading'));

        return $this->get(route('flights.search.run'));
    }

    private function fakeSuppliers($travelnext, $skylink): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.search_cache_ttl' => 0,
        ]);

        Http::fake([
            'travelnext.works/*' => $travelnext,
            '*/api/login' => Http::response(['data' => ['access_token' => 't', 'refresh_token' => 'r', 'expires_in' => 900]]),
            '*/api/flights/search' => $skylink,
        ]);
    }

    private function travelnextFlights(?array $itineraries = null)
    {
        $depart = now()->addMonth()->setTime(10, 30);

        return Http::response(['AirSearchResponse' => [
            'session_id' => 'tn-session',
            'AirSearchResult' => ['FareItineraries' => $itineraries ?? [[
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
        ]]);
    }

    private function skylinkFlights()
    {
        $depart = now()->addMonth();

        return Http::response(['success' => true, 'data' => ['flights' => [[
            'price' => 750000,
            'booking_token' => 'btk_1',
            'segments' => [[[
                'img' => 'BA',
                'flight_no' => 'BA75',
                'airline' => 'British Airways',
                'class' => 'economy',
                'departure_code' => 'LOS',
                'departure_time' => '10:30 am',
                'departure_date' => $depart->format('d-m-Y'),
                'arrival_code' => 'LHR',
                'arrival_time' => '04:30 pm',
                'arrival_date' => $depart->format('d-m-Y'),
                'seg_duration' => '6h 0m',
            ]]],
        ]]]]);
    }
}

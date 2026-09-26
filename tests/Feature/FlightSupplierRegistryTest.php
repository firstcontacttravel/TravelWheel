<?php

namespace Tests\Feature;

use App\Contracts\FlightSupplier;
use App\Models\FlightSupplierCall;
use App\Services\Flights\FlightSupplierRegistry;
use App\Services\SkylinkFlightService;
use App\Services\TravelnextFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class FlightSupplierRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_configured_supplier_resolves_under_its_own_key(): void
    {
        $registry = app(FlightSupplierRegistry::class);

        $this->assertSame(['travelnext', 'skylink'], $registry->keys());

        foreach ($registry->all() as $key => $supplier) {
            $this->assertInstanceOf(FlightSupplier::class, $supplier);
            $this->assertSame($key, $supplier->key());
            $this->assertNotSame('', $supplier->label());
        }
    }

    public function test_hold_capability_matches_how_each_supplier_books(): void
    {
        $registry = app(FlightSupplierRegistry::class);

        $this->assertTrue($registry->get('travelnext')->supportsHold());
        $this->assertFalse($registry->get('skylink')->supportsHold());
    }

    public function test_an_unknown_supplier_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(FlightSupplierRegistry::class)->get('amadeus');
    }

    public function test_select_rejects_a_source_that_is_not_a_registered_supplier(): void
    {
        Http::fake();

        $this->post(route('flights.select'), [
            'fare_source_code' => 'FSC-1',
            'source' => 'amadeus',
        ])->assertSessionHasErrors('source');

        Http::assertNothingSent();
    }

    public function test_select_never_hands_one_supplier_another_suppliers_flight(): void
    {
        // Same fare code at both suppliers — only the SkyLink one may reach
        // SkyLink's select.
        $travelnextFlight = ['fareSourceCode' => 'SHARED', 'source' => 'travelnext', 'airline' => 'TravelNext copy'];
        $skylinkFlight = ['fareSourceCode' => 'SHARED', 'source' => 'skylink', 'airline' => 'SkyLink copy'];

        $skylink = Mockery::mock(SkylinkFlightService::class)->makePartial();
        $skylink->shouldReceive('select')
            ->once()
            ->withArgs(fn (string $code, ?array $searched): bool => $code === 'SHARED' && ($searched['airline'] ?? null) === 'SkyLink copy')
            ->andReturn(['error' => true, 'message' => 'stop here', 'data' => [], 'surface' => 'flash']);
        $this->app->instance(SkylinkFlightService::class, $skylink);

        $this->withSession([
            'flightResultsStore' => [$travelnextFlight],
            'skylinkResultsStore' => [$skylinkFlight],
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1],
        ])->post(route('flights.select'), [
            'fare_source_code' => 'SHARED',
            'source' => 'skylink',
        ])->assertSessionHas('error', 'stop here');
    }

    public function test_travelnext_calls_are_recorded_alongside_skylinks(): void
    {
        Http::fake([
            'travelnext.works/*' => Http::response(['AirSearchResponse' => ['AirSearchResult' => ['FareItineraries' => []]]]),
        ]);

        app(TravelnextFlightService::class)->search([
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'Abuja (ABV)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 2,
            'childs' => 1,
            'kids' => 0,
            'flight_type' => 'Y',
        ], ['search_id' => 'search-123']);

        $call = FlightSupplierCall::sole();
        $this->assertSame('travelnext', $call->supplier);
        $this->assertSame('search', $call->call_type);
        $this->assertSame('LOS-ABV', $call->route);
        $this->assertSame('search-123', $call->search_id);
        $this->assertTrue($call->success);
        $this->assertSame(200, $call->http_status);
        $this->assertSame(['adults' => 2, 'children' => 1, 'infants' => 0], $call->passenger_counts);
    }

    public function test_an_unreachable_travelnext_is_recorded_as_a_failed_call(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $result = app(TravelnextFlightService::class)->search([
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'Abuja (ABV)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 1,
            'flight_type' => 'Y',
        ]);

        $this->assertTrue($result['error']);
        $call = FlightSupplierCall::sole();
        $this->assertFalse($call->success);
        $this->assertStringContainsString('connection refused', $call->error_message);
    }

    public function test_credentials_are_sent_where_they_always_were_and_never_recorded(): void
    {
        config([
            'services.travelnext.user_id' => 'tn-user',
            'services.travelnext.password' => 'tn-secret',
        ]);
        // An error that echoes the request back, password and all.
        Http::fake(['travelnext.works/*' => Http::response('bad request: user_password=tn-secret', 500)]);
        $travelnext = app(TravelnextFlightService::class);

        $travelnext->post('trip_details', ['UniqueID' => 'TN1']);
        $travelnext->post('revalidate', ['session_id' => 's', 'fare_source_code' => 'f'], withCredentials: false);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'trip_details')
            && $request['user_password'] === 'tn-secret'
            && $request['UniqueID'] === 'TN1');
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'revalidate')
            && ! isset($request['user_password']));

        $this->assertSame(['user_id' => '[redacted]', 'user_password' => '[redacted]', 'UniqueID' => 'TN1'],
            $travelnext->redact(['user_id' => 'tn-user', 'user_password' => 'tn-secret', 'UniqueID' => 'TN1']));
        $this->assertStringNotContainsString('tn-secret', FlightSupplierCall::all()->toJson());
    }
}

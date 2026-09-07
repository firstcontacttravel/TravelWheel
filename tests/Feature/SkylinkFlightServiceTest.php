<?php

namespace Tests\Feature;

use App\Models\FlightSupplierCall;
use App\Services\SkylinkFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SkylinkFlightServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_maps_results_and_logs_a_successful_call(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        $result = app(SkylinkFlightService::class)->search($this->searchCriteria());

        $this->assertFalse($result['error']);
        $this->assertCount(1, $result['data']['flights']);

        $flight = $result['data']['flights'][0];
        $this->assertSame('skylink', $flight['source']);
        $this->assertSame('summary', $flight['detailLevel']);
        $this->assertSame('btk_test123', $flight['fareSourceCode']);
        $this->assertSame('btk_test123', $flight['skylinkBookingToken']);
        // 500,000 NGN raw / 1500 NGN-per-USD test rate == 333.33 USD.
        $this->assertSame(333.33, $flight['price']);
        $this->assertSame('USD', $flight['currency']);
        $this->assertSame('Royal Air Maroc', $flight['airline']);
        $this->assertSame('AT', $flight['airlineCode']);
        // Real per-airline logo from airline.json's reference data — not the
        // generic placeholder — since 'AT' is a recognized IATA code.
        $this->assertSame('https://travelnext.works/api/airlines/AT.gif', $flight['airlineLogo']);
        $this->assertSame($flight['airlineLogo'], $flight['segments'][0]['airlineLogo']);
        $this->assertSame('LOS', $flight['segments'][0]['from']);
        $this->assertSame('DXB', $flight['segments'][0]['to']);
        $this->assertSame(405, $flight['segments'][0]['duration']);
        $this->assertSame('07:15 pm', $flight['segments'][0]['departTime']);
        $this->assertStringStartsWith('2026-10-05T19:15:00', $flight['segments'][0]['departDT']);

        $this->assertDatabaseHas('flight_supplier_calls', [
            'supplier' => 'skylink',
            'call_type' => 'search',
            'route' => 'LOS-DXB',
            'success' => true,
        ]);
    }

    public function test_bom_prefixed_supplier_response_is_still_parsed(): void
    {
        // SkyLink prefixes every response body with a UTF-8 BOM, which makes
        // PHP's json_decode() silently return null unless it's stripped first.
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response("\xEF\xBB\xBF".json_encode($this->loginResponse())),
            '*/api/flights/search' => Http::response("\xEF\xBB\xBF".json_encode($this->searchResponse())),
        ]);

        $result = app(SkylinkFlightService::class)->search($this->searchCriteria());

        $this->assertFalse($result['error']);
        $this->assertCount(1, $result['data']['flights']);
        $this->assertSame('btk_test123', $result['data']['flights'][0]['fareSourceCode']);
    }

    public function test_an_unrecognized_airline_code_falls_back_to_the_generic_logo(): void
    {
        $this->configureSkylink();

        $response = $this->searchResponse();
        $response['data']['flights'][0]['segments'][0][0]['img'] = 'ZZ';

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($response),
        ]);

        $result = app(SkylinkFlightService::class)->search($this->searchCriteria());

        $this->assertSame('/assets/img/airlines/default.png', $result['data']['flights'][0]['airlineLogo']);
    }

    public function test_access_token_is_cached_across_multiple_calls(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/search' => Http::response($this->searchResponse()),
        ]);

        $service = app(SkylinkFlightService::class);
        $service->search($this->searchCriteria());
        $service->search($this->searchCriteria());

        $loginRequests = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn ($request) => str_contains($request->url(), '/api/login'));

        $this->assertCount(1, $loginRequests);
    }

    public function test_expired_token_is_refreshed_and_the_request_retried_once(): void
    {
        $this->configureSkylink();

        $searchAttempts = 0;

        Http::fake(function ($request) use (&$searchAttempts) {
            if (str_contains($request->url(), '/api/login')) {
                return Http::response($this->loginResponse());
            }

            if (str_contains($request->url(), '/api/flights/search')) {
                $searchAttempts++;

                if ($searchAttempts === 1) {
                    return Http::response(['success' => false, 'message' => 'Unauthenticated.'], 401);
                }

                return Http::response($this->searchResponse());
            }

            return Http::response([], 404);
        });

        $result = app(SkylinkFlightService::class)->search($this->searchCriteria());

        $this->assertFalse($result['error']);
        $this->assertSame(2, $searchAttempts);

        $loginRequests = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn ($request) => str_contains($request->url(), '/api/login'));

        $this->assertCount(2, $loginRequests);
    }

    public function test_pricing_normalizes_the_supplier_response(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            // Real sandbox shape: SkyLink always prices in NGN regardless of
            // what was requested, and includes several fields the PDF
            // documentation's example omitted (per_passenger, reason, etc.).
            '*/api/flights/pricing' => Http::response([
                'success' => true,
                'data' => [
                    'booking_token' => 'btk_refreshed',
                    'verified' => true,
                    'verification_skipped' => false,
                    'reason' => '',
                    'price_changed' => true,
                    'class_letter_changed' => false,
                    'original_class_letter' => '',
                    'new_class_letter' => 'O',
                    'cabin_class_shifted' => false,
                    'original_price' => 750000,
                    'verified_price' => 780000,
                    'currency' => 'NGN',
                    'per_passenger' => ['adult' => 780000, 'child' => 0, 'infant' => 0],
                    'expires_at' => '2026-05-17 14:30:00',
                    'response_time_ms' => 864,
                    'message' => 'Price verified — fare increased.',
                ],
            ]),
        ]);

        $result = app(SkylinkFlightService::class)->price('btk_test123', ['adults' => 1, 'children' => 0, 'infants' => 0]);

        $this->assertFalse($result['error']);
        $this->assertSame('btk_refreshed', $result['data']['bookingToken']);
        $this->assertTrue($result['data']['priceChanged']);
        $this->assertSame('USD', $result['data']['currency']);
        // 780,000 NGN / 1500 test rate == 520 USD.
        $this->assertSame(520.0, $result['data']['verifiedPrice']);
        $this->assertSame(500.0, $result['data']['originalPrice']);
        $this->assertSame('O', $result['data']['newClassLetter']);
        $this->assertSame(520.0, $result['data']['perPassenger']['adult']);

        $this->assertDatabaseHas('flight_supplier_calls', [
            'supplier' => 'skylink',
            'call_type' => 'pricing',
            'success' => true,
        ]);
    }

    public function test_pricing_accepts_the_apps_native_childs_kids_passenger_naming(): void
    {
        // passengerPayload() used to only read {children, infants} — passing
        // the app-wide search-criteria naming {childs, kids} straight through
        // (as select() now does) silently dropped child/infant counts.
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/pricing' => function ($request) {
                $this->assertSame(2, $request['passengers']['adults']);
                $this->assertSame(1, $request['passengers']['children']);
                $this->assertSame(1, $request['passengers']['infants']);

                return Http::response([
                    'success' => true,
                    'data' => ['booking_token' => 'btk_test123', 'verified' => true, 'verified_price' => 0, 'original_price' => 0],
                ]);
            },
        ]);

        $result = app(SkylinkFlightService::class)->price('btk_test123', ['adults' => 2, 'childs' => 1, 'kids' => 1]);

        $this->assertFalse($result['error']);
    }

    public function test_reserve_returns_a_normalized_pnr_on_success(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/reserve' => Http::response([
                'success' => true,
                'data' => [
                    'pnr' => 'ABC123',
                    'booking_reference' => 'ABC123',
                    'booking_token' => 'btk_refreshed',
                    'carrier' => 'EK',
                    'status' => 'confirmed',
                    'ticket_time_limit_hours' => 48,
                    'ticket_deadline' => '2026-05-19 14:30:00',
                    'message' => 'PNR generated successfully',
                ],
            ]),
        ]);

        $result = app(SkylinkFlightService::class)->reserve(
            'btk_refreshed',
            ['primary_guest' => ['title' => 'Mr', 'first_name' => 'John', 'last_name' => 'Doe']],
            ['adults' => 1, 'children' => 0, 'infants' => 0],
        );

        $this->assertFalse($result['error']);
        $this->assertSame('ABC123', $result['data']['pnr']);
        $this->assertSame('confirmed', $result['data']['status']);

        $this->assertDatabaseHas('flight_supplier_calls', [
            'supplier' => 'skylink',
            'call_type' => 'reserve',
            'success' => true,
        ]);
    }

    public function test_carrier_block_is_surfaced_as_a_clean_error_and_logged(): void
    {
        $this->configureSkylink();

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/reserve' => Http::response([
                'success' => false,
                'blocked' => true,
                'carrier' => 'KQ',
                'message' => 'KQ reservations are not available at this time.',
            ], 403),
        ]);

        $result = app(SkylinkFlightService::class)->reserve(
            'btk_test123',
            ['primary_guest' => ['title' => 'Mr', 'first_name' => 'John', 'last_name' => 'Doe']],
            ['adults' => 1, 'children' => 0, 'infants' => 0],
        );

        $this->assertTrue($result['error']);
        $this->assertSame('KQ reservations are not available at this time.', $result['message']);

        $this->assertDatabaseHas('flight_supplier_calls', [
            'supplier' => 'skylink',
            'call_type' => 'reserve',
            'success' => false,
            'http_status' => 403,
        ]);
    }

    public function test_a_logging_failure_never_breaks_the_caller(): void
    {
        // "supplier" is a required column with no default — omitting it forces
        // a DB-level failure that record() must swallow rather than throw.
        FlightSupplierCall::record(['call_type' => 'search']);

        $this->assertDatabaseCount('flight_supplier_calls', 0);
    }

    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
        ]);

        // SkyLink always prices in NGN; the service converts back to USD
        // using this rate so tests get a deterministic, round number.
        \App\Models\ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
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

    private function loginResponse(): array
    {
        return [
            'status' => 'success',
            'code' => 'LOGIN_SUCCESS',
            'data' => [
                'user_id' => 'USR-001',
                'email' => 'partner@example.test',
                'name' => 'Test Partner',
                'role' => 'api',
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'token_type' => 'Bearer',
                'expires_in' => 900,
            ],
        ];
    }

    /**
     * Modeled on the real sandbox response (confirmed via live testing
     * against https://247travels.cloud), not the PDF documentation's
     * flat-shape example, which doesn't match what the API actually returns:
     * nested segments[direction][leg], full airline names + a separate
     * 2-letter `img` code, 12h "hh:mm am/pm" times, "dd-mm-Y" dates, and a
     * `duration_time` that's the whole direction's total rather than the
     * individual leg's own time (that's `seg_duration`).
     */
    private function searchResponse(): array
    {
        return [
            'success' => true,
            'data' => [
                'meta' => [
                    'search_id' => 'abc12345',
                    'response_time_ms' => 2840,
                    'origin' => 'LOS',
                    'destination' => 'DXB',
                    // Confirmed: SkyLink always returns NGN, ignoring the
                    // requested currency.
                    'currency' => 'NGN',
                ],
                'flights' => [[
                    'price' => 500000,
                    'actual_adult_base' => 500000,
                    'currency' => 'NGN',
                    'seats_left' => 4,
                    'is_private_fare' => false,
                    'booking_token' => 'btk_test123',
                    'segments' => [[
                        [
                            'img' => 'AT',
                            'flight_no' => 'AT576',
                            'airline' => 'Royal Air Maroc',
                            'class' => 'economy',
                            'class_letter' => 'O',
                            'baggage' => '30kg',
                            'departure_airport' => 'Murtala Muhammed International',
                            'departure_city' => 'Lagos',
                            'departure_code' => 'LOS',
                            'departure_time' => '07:15 pm',
                            'departure_date' => '05-10-2026',
                            'arrival_airport' => 'Dubai International',
                            'arrival_city' => 'Dubai',
                            'arrival_code' => 'DXB',
                            'arrival_time' => '02:00 am',
                            'arrival_date' => '06-10-2026',
                            // Whole-itinerary total, repeated on every leg.
                            'duration_time' => '6h 45m',
                            'total_duration' => '6h 45m',
                            // This one leg's own flight time.
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

<?php

namespace Tests\Feature;

use App\Support\FlightMarkup;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the exact output of the TravelNext search and select mapping.
 *
 * The golden files under tests/Fixtures/travelnext-golden were generated from
 * the mapping as it stood inside FlightController / FlightBookingController,
 * before it moved into TravelnextFlightService. Any difference at all — a key
 * renamed, a float that became an int, a segment in a different order — fails
 * here, which is the point: the move is meant to change nothing customers see.
 *
 * Regenerate deliberately with UPDATE_GOLDEN=1, and only when the mapping is
 * meant to change.
 */
class TravelnextGoldenMappingTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://travelnext.works/api/aeroVE5/';

    protected function setUp(): void
    {
        parent::setUp();

        // FlightMarkup memoises the exchange rate and service charges in
        // statics that outlive a test; an earlier test's rates would
        // otherwise price these flights and the goldens would depend on
        // which tests happened to run first.
        FlightMarkup::forgetCachedConfiguration();
        $this->travelTo(Carbon::parse('2026-09-26 10:00:00'));
    }

    public function test_one_way_search_mapping_is_unchanged(): void
    {
        $this->fakeAvailability($this->oneWayAvailability());

        $this->runSearch([
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => '20/10/2026',
        ]);

        $this->assertGolden('search-oneway', $this->searchSession());
    }

    public function test_return_search_mapping_is_unchanged(): void
    {
        $this->fakeAvailability($this->returnAvailability());

        $this->runSearch([
            'trip' => 'return',
            'from' => 'Lagos (LOS)',
            'to' => 'Dubai (DXB)',
            'depart' => '20/10/2026',
            'returning' => '03/11/2026',
        ]);

        $this->assertGolden('search-return', $this->searchSession());
    }

    public function test_flat_multi_city_search_mapping_is_unchanged(): void
    {
        $this->fakeAvailability($this->flatMultiCityAvailability());

        $this->runSearch([
            'trip' => 'multi',
            'multi_legs' => json_encode([
                ['from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026'],
                ['from' => 'London (LHR)', 'to' => 'Abuja (ABV)', 'depart' => '27/10/2026'],
            ]),
        ]);

        $this->assertGolden('search-multi-flat', $this->searchSession());
    }

    public function test_one_way_select_mapping_is_unchanged(): void
    {
        $this->fakeAvailability($this->oneWayAvailability(), [
            'revalidate' => $this->revalidate($this->oneWayAvailability(), withPenalties: false),
        ]);

        $this->runSearch([
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => '20/10/2026',
        ]);

        $this->post(route('flights.select'), [
            'fare_source_code' => 'FSC-ONEWAY-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('flights.booking'));

        $this->assertGolden('select-oneway', $this->selectSession());
    }

    public function test_return_select_mapping_is_unchanged(): void
    {
        $this->fakeAvailability($this->returnAvailability(), [
            'revalidate' => $this->revalidate($this->returnAvailability(), withPenalties: true),
        ]);

        $this->runSearch([
            'trip' => 'return',
            'from' => 'Lagos (LOS)',
            'to' => 'Dubai (DXB)',
            'depart' => '20/10/2026',
            'returning' => '03/11/2026',
        ]);

        $this->post(route('flights.select'), [
            'fare_source_code' => 'FSC-RETURN-1',
            'session_id' => 'tn-session-1',
            'intent' => 'travelflex',
        ])->assertRedirect(route('flights.booking'));

        $this->assertGolden('select-return', $this->selectSession());
    }

    public function test_multi_city_select_mapping_is_unchanged(): void
    {
        $revalidate = $this->revalidate($this->flatMultiCityAvailability(), withPenalties: true);
        // Revalidate answers multi-city with one OriginDestinationOptions entry
        // per leg, unlike availability's single flat entry.
        $odo = data_get($revalidate, 'AirRevalidateResponse.AirRevalidateResult.FareItineraries.FareItinerary.OriginDestinationOptions.0.OriginDestinationOption');
        data_set($revalidate, 'AirRevalidateResponse.AirRevalidateResult.FareItineraries.FareItinerary.OriginDestinationOptions', [
            ['TotalStops' => 0, 'OriginDestinationOption' => [$odo[0]]],
            ['TotalStops' => 1, 'OriginDestinationOption' => [$odo[1], $odo[2]]],
        ]);

        $this->fakeAvailability($this->flatMultiCityAvailability(), ['revalidate' => $revalidate]);

        $this->runSearch([
            'trip' => 'multi',
            'multi_legs' => json_encode([
                ['from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026'],
                ['from' => 'London (LHR)', 'to' => 'Abuja (ABV)', 'depart' => '27/10/2026'],
            ]),
        ]);

        $this->post(route('flights.select'), [
            'fare_source_code' => 'FSC-MULTI-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('flights.booking'));

        $this->assertGolden('select-multi', $this->selectSession());
    }

    public function test_search_error_response_returns_to_the_search_form(): void
    {
        Http::fake([self::BASE.'availability' => Http::response(['error' => 'down'], 500)]);

        $this->runSearch([
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => '20/10/2026',
        ], expectResults: false)
            ->assertRedirect(route('air'))
            ->assertSessionHasErrors(['error' => 'Flight search failed. Please try again.']);
    }

    public function test_select_rejects_a_fare_that_no_longer_revalidates(): void
    {
        $revalidate = $this->revalidate($this->oneWayAvailability(), withPenalties: true);
        data_set($revalidate, 'AirRevalidateResponse.AirRevalidateResult.IsValid', false);
        $this->fakeAvailability($this->oneWayAvailability(), ['revalidate' => $revalidate]);
        $this->runSearch(['trip' => 'oneway', 'from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026']);

        $this->from(route('air.flight-s'))->post(route('flights.select'), [
            'fare_source_code' => 'FSC-ONEWAY-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('error', 'This fare is no longer available. Please select another flight.');
    }

    public function test_select_reports_a_failed_revalidation_request(): void
    {
        $this->fakeAvailability($this->oneWayAvailability(), [
            'revalidate' => Http::response(['error' => 'down'], 502),
        ]);
        $this->runSearch(['trip' => 'oneway', 'from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026']);

        $this->from(route('air.flight-s'))->post(route('flights.select'), [
            'fare_source_code' => 'FSC-ONEWAY-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('error', 'Revalidation failed. Please try again.');
    }

    public function test_select_contains_an_unreachable_revalidation_endpoint(): void
    {
        $this->fakeAvailability($this->oneWayAvailability(), [
            'revalidate' => fn () => throw new ConnectionException('down'),
        ]);
        $this->runSearch(['trip' => 'oneway', 'from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026']);

        $this->from(route('air.flight-s'))->post(route('flights.select'), [
            'fare_source_code' => 'FSC-ONEWAY-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors(['error' => 'Fare revalidation is temporarily unavailable. Please try again shortly.']);
    }

    public function test_select_reports_failed_fare_rules(): void
    {
        $this->fakeAvailability($this->oneWayAvailability(), [
            'revalidate' => $this->revalidate($this->oneWayAvailability(), withPenalties: true),
            'fare_rules' => Http::response([], 500),
        ]);
        $this->runSearch(['trip' => 'oneway', 'from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => '20/10/2026']);

        $this->from(route('air.flight-s'))->post(route('flights.select'), [
            'fare_source_code' => 'FSC-ONEWAY-1',
            'session_id' => 'tn-session-1',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors(['error' => 'Fare rules fetch failed.']);
    }

    // ── Harness ─────────────────────────────────────────────────────────────

    private function runSearch(array $search, bool $expectResults = true)
    {
        $this->post(route('flights.search'), array_merge([
            'adults' => 2,
            'childs' => 1,
            'kids' => 0,
            'flight_type' => 'Y',
        ], $search))->assertRedirect(route('flights.search.loading'));

        $response = $this->get(route('flights.search.run'));

        if ($expectResults) {
            $response->assertRedirect(route('air.flight-s'));
        }

        return $response;
    }

    private function searchSession(): array
    {
        return [
            'requests' => $this->sentRequests(),
            'flightResultsStore' => session('flightResultsStore'),
            'searchParamsStore' => session('searchParamsStore'),
            'searchSessionId' => session('searchSessionId'),
        ];
    }

    /**
     * Every request that reached TravelNext, in order, with its exact body.
     * Credentials are replaced by a marker rather than dropped, so the golden
     * file still records WHICH calls carry them — revalidate, extra_services
     * and fare_rules never have.
     */
    private function sentRequests(): array
    {
        return collect(Http::recorded())->map(function (array $pair): array {
            $body = $pair[0]->data();
            foreach (['user_id', 'user_password', 'access', 'ip_address'] as $key) {
                if (array_key_exists($key, $body)) {
                    $body[$key] = '[credential]';
                }
            }

            return ['url' => $pair[0]->url(), 'body' => $body];
        })->values()->all();
    }

    private function selectSession(): array
    {
        return [
            'requests' => $this->sentRequests(),
            'bookingFlight' => session('bookingFlight'),
            'bookingSessionId' => session('bookingSessionId'),
            'bookingSearchParams' => session('bookingSearchParams'),
            'extraServices' => session('extraServices'),
            'fareRules' => session('fareRules'),
            'tripType' => session('tripType'),
            'bookingIntent' => session('bookingIntent'),
        ];
    }

    private function assertGolden(string $name, array $actual): void
    {
        $path = base_path("tests/Fixtures/travelnext-golden/{$name}.json");
        $json = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n";

        if (env('UPDATE_GOLDEN')) {
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $json);
        }

        $this->assertFileExists($path, 'Golden file missing — run once with UPDATE_GOLDEN=1 against the reference mapping.');
        $this->assertSame(file_get_contents($path), $json);
    }

    private function fakeAvailability(array $availability, array $overrides = []): void
    {
        $responses = array_merge([
            'availability' => $availability,
            'extra_services' => ['ExtraServicesResponse' => ['ExtraServicesResult' => ['success' => true, 'ExtraServicesData' => ['DynamicBaggage' => []]]]],
            'fare_rules' => ['FareRules1_1Response' => ['FareRules1_1Result' => ['FareRules' => [['FareRule' => ['Category' => 'PENALTIES', 'Rules' => 'Changes permitted for a fee.']]]]]],
        ], $overrides);

        Http::fake(collect($responses)->mapWithKeys(fn ($body, string $endpoint): array => [
            self::BASE.$endpoint => is_array($body) ? Http::response($body) : $body,
        ])->all());
    }

    // ── TravelNext response fixtures ─────────────────────────────────────────

    private function oneWayAvailability(): array
    {
        return $this->availability([
            // Connecting itinerary with a codeshare second leg.
            $this->itinerary('FSC-ONEWAY-1', 812.40, 700.00, 112.40, 'Yes', 'Public', [[
                'TotalStops' => 1,
                'OriginDestinationOption' => [
                    $this->segment('LOS', 'CDG', '2026-10-20T23:05:00', '2026-10-21T05:40:00', 'AF', '149', 395),
                    $this->segment('CDG', 'LHR', '2026-10-21T08:15:00', '2026-10-21T08:35:00', 'AF', '1080', 80, operating: 'KL', seats: 3),
                ],
            ]], validating: 'AF'),
            // Direct flight whose only segment arrives as a bare object.
            $this->itinerary('FSC-ONEWAY-2', 655.10, 590.00, 65.10, 'No', 'WebFare', [[
                'TotalStops' => 0,
                'OriginDestinationOption' => $this->segment('LOS', 'LHR', '2026-10-20T10:30:00', '2026-10-20T16:55:00', 'BA', '75', 385),
            ]], validating: 'BA'),
            // Malformed entry — must be skipped, not crash the mapping.
            ['FareItinerary' => ['unexpected' => true]],
        ]);
    }

    private function returnAvailability(): array
    {
        return $this->availability([
            $this->itinerary('FSC-RETURN-1', 1240.00, 1010.00, 230.00, 'Yes', 'Private', [
                [
                    'TotalStops' => 0,
                    'OriginDestinationOption' => [
                        $this->segment('LOS', 'DXB', '2026-10-20T14:10:00', '2026-10-21T00:40:00', 'EK', '784', 450),
                    ],
                ],
                [
                    'TotalStops' => 1,
                    'OriginDestinationOption' => [
                        $this->segment('DXB', 'ADD', '2026-11-03T04:20:00', '2026-11-03T07:45:00', 'ET', '601', 265),
                        $this->segment('ADD', 'LOS', '2026-11-03T10:15:00', '2026-11-03T14:05:00', 'ET', '901', 350),
                    ],
                ],
            ], validating: 'EK'),
        ]);
    }

    private function flatMultiCityAvailability(): array
    {
        return $this->availability([
            $this->itinerary('FSC-MULTI-1', 1502.75, 1300.00, 202.75, 'Yes', 'Public', [[
                'TotalStops' => 1,
                'OriginDestinationOption' => [
                    $this->segment('LOS', 'LHR', '2026-10-20T10:30:00', '2026-10-20T16:55:00', 'BA', '75', 385),
                    $this->segment('LHR', 'IST', '2026-10-27T07:00:00', '2026-10-27T13:05:00', 'TK', '1980', 245),
                    $this->segment('IST', 'ABV', '2026-10-27T15:40:00', '2026-10-27T20:50:00', 'TK', '623', 370),
                ],
            ]], validating: 'TK'),
        ]);
    }

    private function revalidate(array $availability, bool $withPenalties): array
    {
        $fareItinerary = data_get($availability, 'AirSearchResponse.AirSearchResult.FareItineraries.0.FareItinerary');

        if (! $withPenalties) {
            foreach (array_keys($fareItinerary['AirItineraryFareInfo']['FareBreakdown']) as $index) {
                unset($fareItinerary['AirItineraryFareInfo']['FareBreakdown'][$index]['PenaltyDetails']);
            }
        }

        return [
            'AirRevalidateResponse' => [
                'AirRevalidateResult' => [
                    'IsValid' => true,
                    'FareItineraries' => ['FareItinerary' => $fareItinerary],
                ],
            ],
        ];
    }

    private function availability(array $itineraries): array
    {
        return [
            'AirSearchResponse' => [
                'session_id' => 'tn-session-1',
                'AirSearchResult' => ['FareItineraries' => $itineraries],
            ],
        ];
    }

    private function itinerary(string $fareSourceCode, float $total, float $base, float $tax, string $refundable, string $fareType, array $odos, string $validating): array
    {
        return [
            'FareItinerary' => [
                'ValidatingAirlineCode' => $validating,
                'TicketType' => 'eTicket',
                'IsPassportMandatory' => true,
                'DirectionInd' => 'OneWay',
                'TicketAdvisory' => '  Ticket within 24 hours.  ',
                'AirItineraryFareInfo' => [
                    'FareSourceCode' => $fareSourceCode,
                    'IsRefundable' => $refundable,
                    'FareType' => $fareType,
                    'ItinTotalFares' => [
                        'TotalFare' => ['Amount' => (string) $total, 'CurrencyCode' => 'USD'],
                        'BaseFare' => ['Amount' => (string) $base, 'CurrencyCode' => 'USD'],
                        'TotalTax' => ['Amount' => (string) $tax, 'CurrencyCode' => 'USD'],
                    ],
                    'FareBreakdown' => [
                        $this->fareBreakdown('ADT', 2, $total * 0.4, $base * 0.4, 150, true),
                        $this->fareBreakdown('CHD', 1, $total * 0.2, $base * 0.2, 75, false),
                    ],
                ],
                'OriginDestinationOptions' => $odos,
            ],
        ];
    }

    private function fareBreakdown(string $type, int $qty, float $total, float $base, float $penalty, bool $changeAllowed): array
    {
        return [
            'PassengerTypeQuantity' => ['Code' => $type, 'Quantity' => $qty],
            'PassengerFare' => [
                'BaseFare' => ['Amount' => (string) round($base, 2)],
                'TotalFare' => ['Amount' => (string) round($total, 2), 'CurrencyCode' => 'USD'],
                'Taxes' => [['TaxCode' => 'YQ', 'Amount' => '42.00']],
                'ServiceTax' => ['Amount' => '3.50'],
                'Surcharges' => ['Amount' => '1.25'],
            ],
            'Baggage' => ['2PC', '1PC'],
            'CabinBaggage' => ['SB', '7KG'],
            'PenaltyDetails' => [
                'ChangeAllowed' => $changeAllowed,
                'ChangePenaltyAmount' => (string) $penalty,
                'RefundAllowed' => true,
                'RefundPenaltyAmount' => (string) ($penalty * 2),
            ],
        ];
    }

    private function segment(string $from, string $to, string $depart, string $arrive, string $airline, string $number, int $duration, ?string $operating = null, int $seats = 9): array
    {
        return [
            'ResBookDesigCode' => 'Q',
            'SeatsRemaining' => ['Number' => $seats, 'BelowMinimum' => $seats < 4],
            'FlightSegment' => [
                'DepartureDateTime' => $depart,
                'ArrivalDateTime' => $arrive,
                'JourneyDuration' => (string) $duration,
                'MarketingAirlineCode' => $airline,
                'MarketingAirlineName' => null,
                'FlightNumber' => $number,
                'DepartureAirportLocationCode' => $from,
                'ArrivalAirportLocationCode' => $to,
                'OperatingAirline' => [
                    'Code' => $operating ?? $airline,
                    'Name' => $operating ? 'Operated by '.$operating : '',
                    'Equipment' => '359',
                    'FlightNumber' => $operating ? '2001' : $number,
                ],
                'CabinClassText' => $airline === 'TK' ? '' : 'Economy',
                'CabinClassCode' => 'Y',
                'MealCode' => 'M',
                'Eticket' => true,
            ],
        ];
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultiCityTwoOdoSmokeTest extends TestCase
{
    public function test_multi_city_uses_each_origin_destination_option_as_a_leg(): void
    {
        Http::fake([
            'https://travelnext.works/api/aeroVE5/availability' => Http::response([
                'AirSearchResponse' => [
                    'session_id' => 'test-session-two-odos',
                    'AirSearchResult' => [
                        'FareItineraries' => [[
                            'FareItinerary' => [
                                'ValidatingAirlineCode' => 'AF',
                                'TicketType' => 'eTicket',
                                'IsPassportMandatory' => false,
                                'DirectionInd' => 'Circle',
                                'AirItineraryFareInfo' => [
                                    'FareSourceCode' => 'test-fare-source',
                                    'IsRefundable' => 'Yes',
                                    'FareType' => 'Public',
                                    'ItinTotalFares' => [
                                        'TotalFare' => ['Amount' => '1000000', 'CurrencyCode' => 'NGN'],
                                        'BaseFare' => ['Amount' => '900000', 'CurrencyCode' => 'NGN'],
                                        'TotalTax' => ['Amount' => '100000', 'CurrencyCode' => 'NGN'],
                                    ],
                                    'FareBreakdown' => [[
                                        'PassengerTypeQuantity' => ['Code' => 'ADT', 'Quantity' => 1],
                                        'PassengerFare' => [
                                            'BaseFare' => ['Amount' => '900000'],
                                            'TotalFare' => ['Amount' => '1000000', 'CurrencyCode' => 'NGN'],
                                            'Taxes' => [],
                                            'ServiceTax' => ['Amount' => '0'],
                                            'Surcharges' => ['Amount' => '0'],
                                        ],
                                        'Baggage' => ['23kg'],
                                        'CabinBaggage' => ['7kg'],
                                        'PenaltyDetails' => [
                                            'RefundAllowed' => true,
                                            'ChangeAllowed' => true,
                                        ],
                                    ]],
                                ],
                                'OriginDestinationOptions' => [
                                    [
                                        'TotalStops' => 0,
                                        'OriginDestinationOption' => [
                                            $this->segment('CDG', 'LOS', '2026-07-20T08:00:00', '2026-07-20T14:00:00', '001'),
                                        ],
                                    ],
                                    [
                                        'TotalStops' => 0,
                                        'OriginDestinationOption' => [
                                            $this->segment('LOS', 'CDG', '2026-07-24T22:00:00', '2026-07-25T06:00:00', '002'),
                                        ],
                                    ],
                                ],
                            ],
                        ]],
                    ],
                ],
            ]),
        ]);

        $this->post(route('flights.search'), [
            'trip' => 'multi',
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
            'multi_legs' => json_encode([
                ['from' => 'Paris (CDG)', 'to' => 'Lagos (LOS)', 'depart' => '20/07/2026'],
                ['from' => 'Lagos (LOS)', 'to' => 'Paris (CDG)', 'depart' => '24/07/2026'],
            ]),
        ])->assertRedirect(route('flights.search.loading'));

        $this->get(route('flights.search.run'))
            ->assertRedirect(route('air.flight-s'));

        $legs = session('flightResultsStore.0.multiLegs');

        $this->assertCount(2, $legs);
        $this->assertSame('CDG', $legs[0]['from']);
        $this->assertSame('LOS', $legs[0]['to']);
        $this->assertSame('LOS', $legs[1]['from']);
        $this->assertSame('CDG', $legs[1]['to']);
    }

    private function segment(string $from, string $to, string $depart, string $arrive, string $number): array
    {
        return [
            'ResBookDesigCode' => 'Y',
            'SeatsRemaining' => ['Number' => 9, 'BelowMinimum' => false],
            'FlightSegment' => [
                'DepartureDateTime' => $depart,
                'ArrivalDateTime' => $arrive,
                'JourneyDuration' => '360',
                'MarketingAirlineCode' => 'AF',
                'MarketingAirlineName' => 'Air France',
                'FlightNumber' => $number,
                'DepartureAirportLocationCode' => $from,
                'ArrivalAirportLocationCode' => $to,
                'OperatingAirline' => [
                    'Code' => 'AF',
                    'Name' => 'Air France',
                    'Equipment' => '777',
                    'FlightNumber' => $number,
                ],
                'CabinClassText' => 'Economy',
                'CabinClassCode' => 'Y',
                'MealCode' => '',
                'Eticket' => true,
            ],
        ];
    }
}

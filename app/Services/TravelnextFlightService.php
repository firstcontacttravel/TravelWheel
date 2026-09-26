<?php

namespace App\Services;

use App\Contracts\FlightSupplier;
use App\Models\ExchangeRate;
use App\Models\FlightSupplierCall;
use App\Support\FlightDisplay;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client for the TravelNext (aeroVE5) flight API.
 *
 * TravelNext holds a booking with the airline first (booking), then tickets it
 * once payment clears (ticket_order) — the opposite order to SkyLink. Every
 * call to TravelNext in the app goes through post() here, so credentials are
 * attached in one place and every call is recorded in flight_supplier_calls.
 *
 * search() and select() were moved here unchanged from FlightController and
 * FlightBookingController; tests/Feature/TravelnextGoldenMappingTest pins
 * their output and the exact requests they send.
 */
class TravelnextFlightService implements FlightSupplier
{
    public const KEY = 'travelnext';

    private const CREDENTIAL_KEYS = ['user_id', 'user_password', 'access', 'ip_address'];

    /**
     * flight_supplier_calls.call_type is 20 characters; SkyLink's rows use
     * search / pricing / reserve, so the equivalent TravelNext calls share
     * those names and the two suppliers can be compared directly.
     */
    private const CALL_TYPES = [
        'availability' => 'search',
        'revalidate' => 'pricing',
        'booking' => 'book',
        'ticket_order' => 'ticket',
        'search_post_ticket_status' => 'ptr_status',
        'reissue_ticket_quote' => 'reissue_quote',
    ];

    private ?Collection $airports = null;

    private ?Collection $airlines = null;

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TravelNext';
    }

    public function supportsHold(): bool
    {
        return true;
    }

    // =========================================================================
    //  search() — POST availability
    // =========================================================================
    public function search(array $criteria, array $context = []): array
    {
        $tripType = strtolower((string) ($criteria['trip'] ?? 'oneway'));

        // ── Build origin-destination payload ──────────────────────────────────
        $originDestination = [];
        $journeyType = match ($tripType) {
            'oneway' => 'OneWay',
            'return' => 'Return',
            'multi' => 'Circle',
            default => 'OneWay',
        };

        $fromCode = Str::between($criteria['from'] ?? '', '(', ')');
        $toCode = Str::between($criteria['to'] ?? '', '(', ')');

        if ($tripType === 'oneway') {
            $originDestination[] = [
                'departureDate' => Carbon::createFromFormat('d/m/Y', $criteria['depart'])->format('Y-m-d'),
                'airportOriginCode' => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($tripType === 'return') {
            $originDestination[] = [
                'departureDate' => Carbon::createFromFormat('d/m/Y', $criteria['depart'])->format('Y-m-d'),
                'returnDate' => Carbon::createFromFormat('d/m/Y', $criteria['returning'])->format('Y-m-d'),
                'airportOriginCode' => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($tripType === 'multi') {
            foreach ($criteria['multi_legs'] ?? [] as $leg) {
                $lFrom = Str::between($leg['from'], '(', ')');
                $lTo = Str::between($leg['to'], '(', ')');
                $originDestination[] = [
                    'departureDate' => Carbon::createFromFormat('d/m/Y', $leg['depart'])->format('Y-m-d'),
                    'airportOriginCode' => $lFrom,
                    'airportDestinationCode' => $lTo,
                ];
            }
        }

        $this->logStep($context, 'origin-destination payload built', [
            'journeyType' => $journeyType,
            'legs' => count($originDestination),
            'routes' => array_map(
                fn (array $od): string => ($od['airportOriginCode'] ?? '?').'-'.($od['airportDestinationCode'] ?? '?'),
                $originDestination
            ),
        ]);

        $payload = [
            'requiredCurrency' => 'USD',
            'journeyType' => $journeyType,
            'OriginDestinationInfo' => $originDestination,

            'class' => $this->mapCabin((string) $criteria['flight_type']),
            'adults' => (int) $criteria['adults'],
            'childs' => (int) ($criteria['childs'] ?? 0),
            'infants' => (int) ($criteria['kids'] ?? 0),
        ];

        // Credentials are added by post() and never reach the log.
        $this->logStep($context, 'availability payload prepared', ['payload' => $payload]);
        $this->logStep($context, 'calling availability API');
        $apiStart = microtime(true);

        try {
            $response = $this->post('availability', $payload, 60, log: [
                'route' => $this->routeLabel($originDestination),
                'cabin' => $payload['class'],
                'trip_type' => $tripType,
                'currency' => 'USD',
                'passenger_counts' => [
                    'adults' => $payload['adults'],
                    'children' => $payload['childs'],
                    'infants' => $payload['infants'],
                ],
                'search_id' => $context['search_id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            $this->logStep($context, 'availability request threw an exception', [
                'error' => $exception->getMessage(),
                'trip' => $tripType,
                'duration_ms' => (int) round((microtime(true) - $apiStart) * 1000),
            ], 'error');

            return $this->errorResult('Flight search is temporarily unavailable. Please try again shortly.');
        }

        $this->logStep($context, 'availability API responded', [
            'status' => $response->status(),
            'duration_ms' => (int) round((microtime(true) - $apiStart) * 1000),
            'body_bytes' => strlen((string) $response->body()),
        ]);

        if ($response->failed()) {
            $this->logStep($context, 'availability returned an error response', [
                'status' => $response->status(),
                'trip' => $tripType,
                'body_excerpt' => Str::limit((string) $response->body(), 1000),
            ], 'warning');

            return $this->errorResult('Flight search failed. Please try again.');
        }

        $jsonData = $response->json();

        $itCount = count(data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []));
        $firstIt = data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries.0.FareItinerary', null);
        $odoCount = $firstIt ? count($firstIt['OriginDestinationOptions'] ?? []) : null;
        $apiErr = data_get($jsonData, 'AirSearchResponse.AirSearchResult.Errors')
                 ?? data_get($jsonData, 'Errors')
                 ?? null;

        $this->logStep($context, 'availability response parsed', [
            'trip' => $tripType,
            'itinerary_count' => $itCount,
            'first_odo_count' => $odoCount,
            'api_errors' => $apiErr,
            'session_id_present' => filled(data_get($jsonData, 'AirSearchResponse.session_id')),
        ]);

        $this->logStep($context, 'reference data loaded', [
            'airlines' => $this->airlines()->count(),
            'airports' => $this->airports()->count(),
        ]);

        $this->logStep($context, 'mapping itineraries started', ['itineraries' => $itCount]);
        $mapStart = microtime(true);

        $flights = $this->mapSearchItineraries(
            data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []),
            $tripType,
            $criteria['multi_legs'] ?? [],
            $context,
        );

        $this->logStep($context, 'mapping complete', [
            'mapped' => count($flights),
            'skipped' => max(0, $itCount - count($flights)),
            'duration_ms' => (int) round((microtime(true) - $mapStart) * 1000),
        ]);

        return [
            'error' => false,
            'message' => null,
            'data' => [
                'flights' => $flights,
                'meta' => ['session_id' => data_get($jsonData, 'AirSearchResponse.session_id', '')],
            ],
        ];
    }

    /**
     * Availability itineraries → canonical flights, at supplier price.
     */
    private function mapSearchItineraries(array $itineraries, string $tripType, array $searchLegs, array $context): array
    {
        $airlines = $this->airlines();
        $mapSegments = fn (array $odo): array => $this->mapSegments($odo);

        $calcLayovers = function (array $segments): array {
            $durations = [];
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $arrive = Carbon::parse($segments[$i]['arriveDT']);
                $depart = Carbon::parse($segments[$i + 1]['departDT']);
                $mins = $arrive->diffInMinutes($depart);
                $durations[] = floor($mins / 60).'h '.($mins % 60).'m';
            }

            return $durations;
        };

        $calcLayoverMins = function (array $segments): int {
            $total = 0;
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $arrive = Carbon::parse($segments[$i]['arriveDT']);
                $depart = Carbon::parse($segments[$i + 1]['departDT']);
                $total += (int) $arrive->diffInMinutes($depart);
            }

            return $total;
        };

        $fmtMins = fn (int $mins): string => floor($mins / 60).'h '.($mins % 60).'m';

        $splitMultiLegs = function (array $allSegments, array $searchLegs): array {
            if (empty($searchLegs)) {
                return [$allSegments];
            }

            $legs = [];
            $remaining = $allSegments;

            foreach ($searchLegs as $legIdx => $legDef) {
                $extractIata = fn (string $val) => preg_match('/\(([A-Z]{3})\)/', $val, $m) ? $m[1] : strtoupper(trim($val));

                $destIata = $extractIata($legDef['to'] ?? '');

                // Last leg — all remaining segments belong here
                if ($legIdx === count($searchLegs) - 1) {
                    $legs[] = $remaining;
                    $remaining = [];
                    break;
                }

                // Find the cut point: last segment whose 'to' == this leg's destination
                $cutAt = -1;
                foreach ($remaining as $si => $seg) {
                    if (strtoupper($seg['to']) === $destIata) {
                        $cutAt = $si;
                        break;
                    }
                }

                $legs[] = ($cutAt === -1)
                    ? array_splice($remaining, 0, 1)          // fallback: take one segment
                    : array_splice($remaining, 0, $cutAt + 1); // normal: up to & including the destination segment
            }

            if (! empty($remaining)) {
                $legs[] = $remaining;
            }

            return $legs;
        };

        return collect($itineraries)->values()->map(
            function ($item, $index) use (
                $tripType, $searchLegs,
                $mapSegments, $calcLayovers, $calcLayoverMins, $fmtMins,
                $splitMultiLegs, $airlines, $context
            ) {
                if (! is_array($item)
                    || ! is_array($item['FareItinerary'] ?? null)
                    || ! is_array(data_get($item, 'FareItinerary.AirItineraryFareInfo'))
                    || ! is_array(data_get($item, 'FareItinerary.OriginDestinationOptions'))) {
                    // Only visible when LOG_LEVEL=debug — avoids noise in production
                    $this->logStep($context, 'itinerary skipped — malformed structure', ['index' => $index], 'debug');

                    return null;
                }

                $fi = $item['FareItinerary'];
                $fareInfo = $fi['AirItineraryFareInfo'];
                $odos = $fi['OriginDestinationOptions'] ?? [];

                $segments = [];
                $totalStops = 0;
                $totalMins = 0;
                $totalTimeMins = 0;
                $returnSegments = [];
                $returnStops = 0;
                $returnDurationLabel = '';
                $returnTotalTimeMins = 0;
                $returnTotalTimeLabel = '';
                $returnDateLabel = '';
                $returnLayoverDurations = [];
                $multiLegs = [];
                $layoverDurations = [];
                $departDateLabel = '';

                if ($tripType === 'oneway') {
                    $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
                    $segments = $mapSegments($odo0);
                    $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
                    $totalMins = array_sum(array_column($segments, 'duration'));
                    $layoverDurations = $calcLayovers($segments);
                    $totalTimeMins = $totalMins + $calcLayoverMins($segments);

                } elseif ($tripType === 'return') {
                    $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
                    $segments = $mapSegments($odo0);
                    $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
                    $totalMins = array_sum(array_column($segments, 'duration'));
                    $layoverDurations = $calcLayovers($segments);
                    $totalTimeMins = $totalMins + $calcLayoverMins($segments);

                    if (! empty($odos[1])) {
                        $odo1 = $odos[1]['OriginDestinationOption'] ?? [];
                        $returnSegments = $mapSegments($odo1);
                        $returnStops = (int) ($odos[1]['TotalStops'] ?? max(0, count($odo1) - 1));
                        $returnMins = array_sum(array_column($returnSegments, 'duration'));
                        $returnDurationLabel = $fmtMins($returnMins);
                        $returnLayoverDurations = $calcLayovers($returnSegments);
                        $returnTotalTimeMins = $returnMins + $calcLayoverMins($returnSegments);
                        $returnTotalTimeLabel = $fmtMins($returnTotalTimeMins);
                        if (! empty($returnSegments[0]['departDT'])) {
                            $returnDateLabel = Carbon::parse($returnSegments[0]['departDT'])->format('D, d M');
                        }
                    }

                } elseif ($tripType === 'multi') {
                    $legArrays = [];

                    if (count($odos) > 1) {
                        foreach ($odos as $odo) {
                            $legSegs = $mapSegments($odo['OriginDestinationOption'] ?? []);
                            if (! empty($legSegs)) {
                                $legArrays[] = $legSegs;
                            }
                        }
                    } else {
                        $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
                        $allSegs = $mapSegments($odo0);
                        $legArrays = $splitMultiLegs($allSegs, $searchLegs);
                    }

                    // Build ALL legs into $multiLegs (leg 0 is included — no more skipping it)
                    $multiLegs = [];
                    foreach ($legArrays as $legSegs) {
                        if (empty($legSegs)) {
                            continue;
                        }

                        $lastSeg = end($legSegs);
                        $legMins = array_sum(array_column($legSegs, 'duration'));
                        $legLayovers = $calcLayovers($legSegs);
                        $legTotalTimeMins = $legMins + $calcLayoverMins($legSegs);

                        $multiLegs[] = [
                            'segments' => $legSegs,
                            'stops' => max(0, count($legSegs) - 1),
                            'durationLabel' => $fmtMins($legMins),
                            'layoverDurations' => $legLayovers,
                            'totalTimeMins' => $legTotalTimeMins,
                            'totalTimeLabel' => $fmtMins($legTotalTimeMins),
                            'departDateLabel' => ! empty($legSegs[0]['departDT'])
                                                    ? Carbon::parse($legSegs[0]['departDT'])->format('D, d M')
                                                    : '',
                            // Convenience fields for the view
                            'from' => $legSegs[0]['from'] ?? '',
                            'to' => $lastSeg['to'] ?? '',
                            'fromCity' => $legSegs[0]['fromCity'] ?? '',
                            'toCity' => $lastSeg['toCity'] ?? '',
                            'departTime' => $legSegs[0]['departTime'] ?? '',
                            'arriveTime' => $lastSeg['arriveTime'] ?? '',
                            'departDT' => $legSegs[0]['departDT'] ?? '',
                            'arriveDT' => $lastSeg['arriveDT'] ?? '',

                        ];

                    }

                    $totalStops = array_sum(array_column($multiLegs, 'stops'));
                    $totalMins = array_sum(array_map(fn ($leg) => array_sum(array_column($leg['segments'], 'duration')), $multiLegs));
                    $totalTimeMins = array_sum(array_column($multiLegs, 'totalTimeMins'));
                }

                $firstSeg = $segments[0] ?? [];
                $lastSeg = ! empty($segments) ? end($segments) : [];
                if ($tripType === 'multi') {
                    // First leg drives the top-level fields (for backward compatibility)
                    $firstSeg = $multiLegs[0]['segments'][0] ?? [];
                    $lastMultiLeg = ! empty($multiLegs) ? $multiLegs[array_key_last($multiLegs)] : [];
                    $lastSeg = ! empty($lastMultiLeg['segments'])
                        ? $lastMultiLeg['segments'][array_key_last($lastMultiLeg['segments'])]
                        : [];

                }

                $deptHour = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
                $arrHour = (int) substr($lastSeg['arriveTime'] ?? '00:00', 0, 2);

                $validatingCode = $fi['ValidatingAirlineCode'] ?? '';
                $validatingAir = $airlines->get($validatingCode);

                if (! empty($firstSeg['departDT'])) {
                    $departDateLabel = Carbon::parse($firstSeg['departDT'])->format('D, d M');
                }

                $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) {
                    return [
                        'passengerType' => $fb['PassengerTypeQuantity']['Code'],
                        'qty' => (int) $fb['PassengerTypeQuantity']['Quantity'],
                        'baseFare' => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                        'totalFare' => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                        'currency' => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
                        'baggage' => $fb['Baggage'] ?? [],
                        'cabinBaggage' => FlightDisplay::cabinBaggageValues($fb['CabinBaggage'] ?? []),
                        'taxes' => $fb['PassengerFare']['Taxes'] ?? [],
                        'serviceTax' => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                        'surcharges' => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
                        'changeAllowed' => $fb['PenaltyDetails']['ChangeAllowed'] ?? false,
                        'changePenalty' => $fb['PenaltyDetails']['ChangePenaltyAmount'] ?? '0.00',
                        'refundAllowed' => $fb['PenaltyDetails']['RefundAllowed'] ?? false,
                        'refundPenalty' => $fb['PenaltyDetails']['RefundPenaltyAmount'] ?? null,
                    ];
                })->values()->toArray();

                return [
                    'id' => $index,
                    'fareSourceCode' => $fareInfo['FareSourceCode'],
                    'source' => self::KEY,
                    'airline' => $firstSeg['airline'] ?? '',
                    'airlineCode' => $firstSeg['airlineCode'] ?? '',
                    'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
                    'validatingCode' => $validatingCode,
                    'validatingAirline' => $validatingAir['AirLineName'] ?? $validatingCode,
                    'validatingLogo' => data_get($validatingAir, 'AirLineLogo') ?: '/assets/img/airlines/default.png',
                    'cabin' => $firstSeg['cabin'] ?? '',
                    'cabinCode' => $firstSeg['cabinCode'] ?? 'Y',
                    'stops' => $totalStops,
                    'price' => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
                    'baseFare' => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
                    'totalTax' => (float) ($fareInfo['ItinTotalFares']['TotalTax']['Amount'] ?? 0),
                    'currency' => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
                    'isRefundable' => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
                    'fareType' => $fareInfo['FareType'] ?? 'Public',
                    'ticketType' => $fi['TicketType'] ?? 'eTicket',
                    'isPassportMandatory' => (bool) ($fi['IsPassportMandatory'] ?? false),
                    'directionInd' => $fi['DirectionInd'] ?? '',
                    'ticketAdvisory' => trim($fi['TicketAdvisory'] ?? ''),
                    'segments' => $segments,
                    'departTime' => $firstSeg['departTime'] ?? '',
                    'arriveTime' => $lastSeg['arriveTime'] ?? '',
                    'departDT' => $firstSeg['departDT'] ?? '',
                    'arriveDT' => $lastSeg['arriveDT'] ?? '',
                    'totalDuration' => $totalMins,
                    'durationLabel' => $fmtMins($totalMins),
                    'layoverDurations' => $layoverDurations,
                    'departDateLabel' => $departDateLabel,
                    'totalTimeMins' => $totalTimeMins,
                    'totalTimeLabel' => $fmtMins($totalTimeMins),
                    'returnSegments' => $returnSegments,
                    'returnStops' => $returnStops,
                    'returnDurationLabel' => $returnDurationLabel,
                    'returnDateLabel' => $returnDateLabel,
                    'returnLayoverDurations' => $returnLayoverDurations,
                    'returnTotalTimeMins' => $returnTotalTimeMins,
                    'returnTotalTimeLabel' => $returnTotalTimeLabel,
                    'multiLegs' => $multiLegs,
                    'departSlot' => $deptHour < 12 ? 'morning' : ($deptHour < 18 ? 'afternoon' : 'evening'),
                    'arrivalSlot' => $arrHour < 12 ? 'morning' : ($arrHour < 18 ? 'afternoon' : 'evening'),
                    'fareBreakdown' => $breakdown,
                ];
            }
        )->filter()->values()->toArray();
    }

    // =========================================================================
    //  select() — revalidate, then extra_services + fare_rules
    // =========================================================================
    public function select(string $fareSourceCode, ?array $searchedFlight, array $criteria, array $context = []): array
    {
        $sessionId = (string) ($context['session_id'] ?? '');

        // Revalidate, extra_services and fare_rules have never been sent the
        // account credentials — only the search session and fare code.
        $payload = [
            'session_id' => $sessionId,
            'fare_source_code' => $fareSourceCode,
        ];

        // ── 1. Revalidate ─────────────────────────────────────────────────────
        try {
            $revalidateResponse = $this->post('revalidate', $payload, 60, withCredentials: false, log: $this->selectLogAttributes($searchedFlight, $criteria));
        } catch (\Throwable $exception) {
            Log::error('Flight revalidation request failed', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResult('Fare revalidation is temporarily unavailable. Please try again shortly.', 'errors');
        }

        if ($revalidateResponse->failed()) {
            return $this->errorResult('Revalidation failed. Please try again.', 'flash');
        }

        $revalidateData = $revalidateResponse->json();

        if (! data_get($revalidateData, 'AirRevalidateResponse.AirRevalidateResult.IsValid')) {
            return $this->errorResult('This fare is no longer available. Please select another flight.', 'flash');
        }

        $fi = data_get(
            $revalidateData,
            'AirRevalidateResponse.AirRevalidateResult.FareItineraries.FareItinerary',
            []
        );

        if (empty($fi)) {
            return $this->errorResult('No fare data returned from revalidation.', 'flash');
        }

        $mappedFlight = $this->mapRevalidatedItinerary($fi, strtolower($criteria['trip'] ?? 'oneway'), $searchedFlight);

        $ancillaryPayload = [
            'session_id' => $sessionId,
            'fare_source_code' => $revalidateData['AirRevalidateResponse']['AirRevalidateResult']['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['FareSourceCode'],
        ];

        // ── 2. Extra services & fare rules, in parallel ───────────────────────
        try {
            $responses = Http::pool(fn (Pool $pool): array => [
                $pool->as('extras')->connectTimeout(10)->timeout(60)
                    ->post($this->url('extra_services'), $ancillaryPayload),
                $pool->as('rules')->connectTimeout(10)->timeout(60)
                    ->post($this->url('fare_rules'), $ancillaryPayload),
            ]);
            $extraResponse = $responses['extras'];
            $fareRulesResponse = $responses['rules'];

            // A pooled request that couldn't connect comes back as the
            // exception itself rather than throwing.
            foreach ([$extraResponse, $fareRulesResponse] as $pooled) {
                if ($pooled instanceof \Throwable) {
                    throw $pooled;
                }
            }
        } catch (\Throwable $exception) {
            Log::error('Flight ancillary request failed', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResult('Fare details are temporarily unavailable. Please try again shortly.', 'errors');
        }

        if ($extraResponse->failed()) {
            return $this->errorResult('Extra services fetch failed.', 'errors');
        }

        if ($fareRulesResponse->failed()) {
            return $this->errorResult('Fare rules fetch failed.', 'errors');
        }

        return [
            'error' => false,
            'message' => null,
            'data' => [
                'flight' => $mappedFlight,
                'extraServices' => $extraResponse->json(),
                'fareRules' => $fareRulesResponse->json(),
            ],
        ];
    }

    /**
     * Revalidated FareItinerary → canonical flight, at supplier price.
     */
    private function mapRevalidatedItinerary(array $fi, string $tripType, ?array $searchedFlight): array
    {
        $airlines = $this->airlines();
        $mapSegments = fn (array $odo): array => $this->mapSegments($odo, revalidated: true);

        $calcLayovers = function (array $segs): array {
            $out = [];
            for ($i = 0; $i < count($segs) - 1; $i++) {
                $mins = Carbon::parse($segs[$i]['arriveDT'])
                    ->diffInMinutes(Carbon::parse($segs[$i + 1]['departDT']));
                $out[] = floor($mins / 60).'h '.($mins % 60).'m';
            }

            return $out;
        };

        $calcLayoverMins = function (array $segs): int {
            $total = 0;
            for ($i = 0; $i < count($segs) - 1; $i++) {
                $total += (int) Carbon::parse($segs[$i]['arriveDT'])
                    ->diffInMinutes(Carbon::parse($segs[$i + 1]['departDT']));
            }

            return $total;
        };

        $fmtMins = fn (int $m): string => floor($m / 60).'h '.($m % 60).'m';

        $fareInfo = $fi['AirItineraryFareInfo'];
        $odos = $fi['OriginDestinationOptions'] ?? [];

        $segments = [];
        $layoverDurations = [];
        $returnSegments = [];
        $returnLayoverDurations = [];
        $multiLegs = [];
        $totalStops = 0;
        $totalMins = 0;
        $totalTimeMins = 0;
        $returnStops = 0;
        $returnTotalTimeMins = 0;
        $returnDurationLabel = '';
        $returnTotalTimeLabel = '';
        $returnDateLabel = '';
        $departDateLabel = '';

        if ($tripType === 'oneway') {

            $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
            $segments = $mapSegments($odo0);
            $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins = $totalMins + $calcLayoverMins($segments);

        } elseif ($tripType === 'return') {

            $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
            $segments = $mapSegments($odo0);
            $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins = $totalMins + $calcLayoverMins($segments);

            if (! empty($odos[1])) {
                $odo1 = $odos[1]['OriginDestinationOption'] ?? [];
                $returnSegments = $mapSegments($odo1);
                $returnStops = (int) ($odos[1]['TotalStops'] ?? max(0, count($odo1) - 1));
                $returnMins = array_sum(array_column($returnSegments, 'duration'));
                $returnDurationLabel = $fmtMins($returnMins);
                $returnLayoverDurations = $calcLayovers($returnSegments);
                $returnTotalTimeMins = $returnMins + $calcLayoverMins($returnSegments);
                $returnTotalTimeLabel = $fmtMins($returnTotalTimeMins);

                if (! empty($returnSegments[0]['departDT'])) {
                    $returnDateLabel = Carbon::parse($returnSegments[0]['departDT'])->format('D, d M');
                }
            }

            // The revalidate API returns each multi-city leg in its OWN
            // OriginDestinationOptions entry — unlike search, which puts all
            // segments in one flat ODO[0] — so the ODOs are iterated directly
            // instead of splitting a flat list.
        } elseif ($tripType === 'multi') {

            $totalStops = 0;

            foreach ($odos as $odoEntry) {

                $odo = $odoEntry['OriginDestinationOption'] ?? [];
                if (empty($odo)) {
                    continue;
                }

                $legSegs = $mapSegments($odo);
                $legFirst = $legSegs[0] ?? [];
                $legLast = ! empty($legSegs) ? end($legSegs) : [];
                $legMins = array_sum(array_column($legSegs, 'duration'));
                $legLayovers = $calcLayovers($legSegs);
                $legLayoverMs = $calcLayoverMins($legSegs);
                $legTotalTime = $legMins + $legLayoverMs;
                $legStops = (int) ($odoEntry['TotalStops'] ?? max(0, count($odo) - 1));

                $totalStops += $legStops;

                $multiLegs[] = [
                    'segments' => $legSegs,
                    'stops' => $legStops,
                    'durationLabel' => $fmtMins($legMins),
                    'layoverDurations' => $legLayovers,
                    'totalTimeMins' => $legTotalTime,
                    'totalTimeLabel' => $fmtMins($legTotalTime),
                    'departDateLabel' => ! empty($legFirst['departDT'])
                                            ? Carbon::parse($legFirst['departDT'])->format('D, d M')
                                            : '',
                    // ── Shortcut fields so the blade never digs into segments[] ──
                    'from' => $legFirst['from'] ?? '',
                    'to' => $legLast['to'] ?? '',
                    'fromCity' => $legFirst['fromCity'] ?? ($legFirst['from'] ?? ''),
                    'toCity' => $legLast['toCity'] ?? ($legLast['to'] ?? ''),
                    'departTime' => $legFirst['departTime'] ?? '',
                    'arriveTime' => $legLast['arriveTime'] ?? '',
                    'departDT' => $legFirst['departDT'] ?? '',
                    'arriveDT' => $legLast['arriveDT'] ?? '',
                ];
            }

            // Aggregate totals across all legs
            $totalMins = array_sum(array_map(fn ($leg) => array_sum(array_column($leg['segments'], 'duration')), $multiLegs));
            $totalTimeMins = array_sum(array_column($multiLegs, 'totalTimeMins'));
            $layoverDurations = [];
        }

        $firstSeg = $segments[0] ?? [];
        $lastSeg = ! empty($segments) ? end($segments) : [];

        // For multi-city: first seg of trip = multiLegs[0].segments[0]
        //                 last  seg of trip = last segment of final leg
        if ($tripType === 'multi' && ! empty($multiLegs)) {
            $firstSeg = $multiLegs[0]['segments'][0] ?? [];
            $lastMultiSegs = end($multiLegs)['segments'] ?? [];
            $lastSeg = ! empty($lastMultiSegs) ? end($lastMultiSegs) : [];
        }

        $deptHour = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
        $arrHour = (int) substr($lastSeg['arriveTime'] ?? '00:00', 0, 2);

        if (! empty($firstSeg['departDT'])) {
            $departDateLabel = Carbon::parse($firstSeg['departDT'])->format('D, d M');
        }

        $validatingCode = $fi['ValidatingAirlineCode'] ?? '';
        $validatingAirline = $airlines->get($validatingCode);

        // TravelNext's `revalidate` response does not repeat `PenaltyDetails` per
        // passenger type — only `availability` (the search step) does. Fall back to
        // the penalty details already captured at search time so refund/TravelFlex
        // eligibility isn't lost just because revalidate omitted the field.
        //
        // The search-stage breakdown was already run through FlightMarkup::apply()
        // (USD → NGN), and the mapped flight goes through apply() again after
        // this, so the fallback amounts are un-converted back to USD here to
        // avoid double conversion.
        $usdToNgnRate = ExchangeRate::rateFor('USD') ?: 1.0;
        $searchedFareBreakdown = collect($searchedFlight['fareBreakdown'] ?? [])
            ->keyBy('passengerType')
            ->map(function ($fb) use ($usdToNgnRate) {
                foreach (['changePenalty', 'refundPenalty'] as $field) {
                    if (isset($fb[$field]) && is_numeric($fb[$field])) {
                        $fb[$field] = round(((float) $fb[$field]) / $usdToNgnRate, 2);
                    }
                }

                return $fb;
            });

        $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) use ($searchedFareBreakdown) {
            $passengerType = $fb['PassengerTypeQuantity']['Code'];
            $penaltyDetails = $fb['PenaltyDetails'] ?? null;
            $fallback = $searchedFareBreakdown->get($passengerType, []);

            return [
                'passengerType' => $passengerType,
                'qty' => (int) $fb['PassengerTypeQuantity']['Quantity'],
                'baseFare' => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                'totalFare' => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                'currency' => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
                'baggage' => $fb['Baggage'] ?? [],
                'cabinBaggage' => FlightDisplay::cabinBaggageValues($fb['CabinBaggage'] ?? []),
                'taxes' => $fb['PassengerFare']['Taxes'] ?? [],
                'serviceTax' => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                'surcharges' => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
                'changeAllowed' => $penaltyDetails['ChangeAllowed'] ?? $fallback['changeAllowed'] ?? false,
                'changePenalty' => $penaltyDetails['ChangePenaltyAmount'] ?? $fallback['changePenalty'] ?? '0.00',
                'refundAllowed' => $penaltyDetails['RefundAllowed'] ?? $fallback['refundAllowed'] ?? false,
                'refundPenalty' => $penaltyDetails['RefundPenaltyAmount'] ?? $fallback['refundPenalty'] ?? null,
            ];
        })->values()->toArray();

        return [
            'fareSourceCode' => $fareInfo['FareSourceCode'],
            'source' => self::KEY,
            'airline' => $firstSeg['airline'] ?? '',
            'airlineCode' => $firstSeg['airlineCode'] ?? '',
            'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
            'validatingCode' => $validatingCode,
            'validatingAirline' => $validatingAirline['AirLineName'] ?? $validatingCode,
            'validatingLogo' => data_get($validatingAirline, 'AirLineLogo') ?: '/assets/img/airlines/default.png',
            'cabin' => FlightDisplay::cabin($firstSeg),
            'cabinCode' => $firstSeg['cabinCode'] ?? 'Y',
            'stops' => $totalStops,
            'price' => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
            'baseFare' => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
            'totalTax' => (float) ($fareInfo['ItinTotalFares']['TotalTax']['Amount'] ?? 0),
            'currency' => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
            'isRefundable' => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
            'fareType' => $fareInfo['FareType'] ?? 'Public',
            'ticketType' => $fi['TicketType'] ?? 'eTicket',
            'isPassportMandatory' => (bool) ($fi['IsPassportMandatory'] ?? false),
            'directionInd' => $tripType ?? '',
            'ticketAdvisory' => trim($fi['TicketAdvisory'] ?? ''),
            'segments' => $segments,
            'departTime' => $firstSeg['departTime'] ?? '',
            'arriveTime' => $lastSeg['arriveTime'] ?? '',
            'departDT' => $firstSeg['departDT'] ?? '',
            'arriveDT' => $lastSeg['arriveDT'] ?? '',
            'totalDuration' => $totalMins,
            'durationLabel' => $fmtMins($totalMins),
            'layoverDurations' => $layoverDurations,
            'departDateLabel' => $departDateLabel,
            'totalTimeMins' => $totalTimeMins,
            'totalTimeLabel' => $fmtMins($totalTimeMins),
            'returnSegments' => $returnSegments,
            'returnStops' => $returnStops,
            'returnDurationLabel' => $returnDurationLabel,
            'returnDateLabel' => $returnDateLabel,
            'returnLayoverDurations' => $returnLayoverDurations,
            'returnTotalTimeMins' => $returnTotalTimeMins,
            'returnTotalTimeLabel' => $returnTotalTimeLabel,
            'multiLegs' => $multiLegs,
            'departSlot' => $deptHour < 12 ? 'morning' : ($deptHour < 18 ? 'afternoon' : 'evening'),
            'arrivalSlot' => $arrHour < 12 ? 'morning' : ($arrHour < 18 ? 'afternoon' : 'evening'),
            'fareBreakdown' => $breakdown,
        ];
    }

    /**
     * Segments of one OriginDestinationOption → canonical segments.
     *
     * $revalidated: the revalidate response can leave CabinClassText empty,
     * so that mapper has always fallen back to a label derived from the cabin
     * code; the search mapper has always passed the raw text through.
     */
    private function mapSegments(array $odo, bool $revalidated = false): array
    {
        $airlines = $this->airlines();
        $airports = $this->airports();

        // Normalize: single-segment direct flights may arrive as an object, not an array of objects
        if (! empty($odo) && isset($odo['FlightSegment'])) {
            $odo = [$odo];
        }

        return collect($odo)
            ->filter(fn ($segment): bool => is_array($segment)
                && is_array($segment['FlightSegment'] ?? null)
                && filled(data_get($segment, 'FlightSegment.DepartureDateTime'))
                && filled(data_get($segment, 'FlightSegment.ArrivalDateTime'))
                && filled(data_get($segment, 'FlightSegment.MarketingAirlineCode'))
                && filled(data_get($segment, 'FlightSegment.DepartureAirportLocationCode'))
                && filled(data_get($segment, 'FlightSegment.ArrivalAirportLocationCode')))
            ->map(function ($seg) use ($airlines, $airports, $revalidated) {
                $fs = $seg['FlightSegment'];
                $dep = Carbon::parse($fs['DepartureDateTime']);
                $arr = Carbon::parse($fs['ArrivalDateTime']);
                $airlineCode = $fs['MarketingAirlineCode'];
                $airline = $airlines->get($airlineCode);
                $fromCode = $fs['DepartureAirportLocationCode'];
                $toCode = $fs['ArrivalAirportLocationCode'];
                $fromAirport = $airports->get($fromCode);
                $toAirport = $airports->get($toCode);
                $opCode = $fs['OperatingAirline']['Code'] ?? $airlineCode;
                $opAirline = $airlines->get($opCode);

                return [
                    'from' => $fromCode,
                    'to' => $toCode,
                    'fromCity' => $fromAirport ? ($fromAirport['City'].' ('.$fromCode.')') : $fromCode,
                    'toCity' => $toAirport ? ($toAirport['City'].' ('.$toCode.')') : $toCode,
                    'fromAirport' => $fromAirport['AirportName'] ?? $fromCode,
                    'toAirport' => $toAirport['AirportName'] ?? $toCode,
                    'fromCountry' => $fromAirport['Country'] ?? '',
                    'toCountry' => $toAirport['Country'] ?? '',
                    'fromLat' => $fromAirport['Latitude'] ?? null,
                    'fromLon' => $fromAirport['Longitude'] ?? null,
                    'toLat' => $toAirport['Latitude'] ?? null,
                    'toLon' => $toAirport['Longitude'] ?? null,
                    'departTime' => $dep->format('H:i'),
                    'arriveTime' => $arr->format('H:i'),
                    'departDate' => $dep->format('D, d M Y'),
                    'arriveDate' => $arr->format('D, d M Y'),
                    'departDT' => $fs['DepartureDateTime'],
                    'arriveDT' => $fs['ArrivalDateTime'],
                    'duration' => (int) $fs['JourneyDuration'],
                    'flightNo' => $airlineCode.$fs['FlightNumber'],
                    'airline' => $fs['MarketingAirlineName'] ?? ($airline['AirLineName'] ?? $airlineCode),
                    'airlineCode' => $airlineCode,
                    // Some airline.json entries carry an empty-string logo
                    // rather than omitting the field — ?? alone won't fall
                    // through that, so use ?: after a null-safe data_get().
                    'airlineLogo' => data_get($airline, 'AirLineLogo') ?: '/assets/img/airlines/default.png',
                    'equipment' => $fs['OperatingAirline']['Equipment'] ?? '',
                    'cabin' => ! $revalidated
                        ? ($fs['CabinClassText'] ?? '')
                        : (trim((string) ($fs['CabinClassText'] ?? '')) !== ''
                            ? $fs['CabinClassText']
                            : FlightDisplay::cabin(['cabinCode' => $fs['CabinClassCode'] ?? 'Y'])),
                    'cabinCode' => $fs['CabinClassCode'] ?? 'Y',
                    'resBookCode' => $seg['ResBookDesigCode'] ?? '',
                    'mealCode' => $fs['MealCode'] ?? '',
                    'seatsLeft' => (int) ($seg['SeatsRemaining']['Number'] ?? 9),
                    'belowMinimum' => (bool) ($seg['SeatsRemaining']['BelowMinimum'] ?? false),
                    'isCodeshare' => $opCode !== $airlineCode,
                    'operatingCode' => $opCode,
                    'operatingAirline' => $fs['OperatingAirline']['Name'] ?? '',
                    'operatingFlightNo' => $opCode.($fs['OperatingAirline']['FlightNumber'] ?? ''),
                    'operatingLogo' => data_get($opAirline, 'AirLineLogo') ?: '/assets/img/airlines/default.png',
                    'eticket' => (bool) ($fs['Eticket'] ?? true),
                ];
            })->values()->toArray();
    }

    // =========================================================================
    //  book() — POST booking (creates the airline hold)
    // =========================================================================
    /**
     * $validated is the booking form (contact, passengers, fare_source_code).
     * $extraBaggage is the customer's selected ['outbound' => ..., 'inbound' => ...].
     */
    public function book(array $flight, array $validated, ?string $flightSessionId, array $extraBaggage = []): array
    {
        $fareSourceCode = $flight['fareSourceCode'] ?? $validated['fare_source_code'];
        $isPassportMand = $flight['isPassportMandatory'] ?? false;
        $fareType = $flight['fareType'] ?? 'Public';
        $contact = $validated['contact'];

        $passengers = collect($validated['passengers']);
        $adults = $passengers->where('type', 'ADT')->values();
        $children = $passengers->where('type', 'CHD')->values();
        $infants = $passengers->where('type', 'INF')->values();

        $baggageOut = $extraBaggage['outbound'] ?? [];
        $baggageIn = $extraBaggage['inbound'] ?? [];

        $buildPaxGroup = function ($list) use ($baggageOut, $baggageIn): array {
            $g = [
                'title' => $list->pluck('title')->toArray(),
                'firstName' => $list->pluck('first_name')->toArray(),
                'lastName' => $list->pluck('last_name')->toArray(),
                'dob' => $list->pluck('dob')->toArray(),
                'nationality' => $list->pluck('nationality')->toArray(),
            ];
            if (array_filter($list->pluck('passport_no')->toArray())) {
                $g['passportNo'] = $list->pluck('passport_no')->toArray();
            }
            if (array_filter($list->pluck('passport_issue_country')->toArray())) {
                $g['passportIssueCountry'] = $list->pluck('passport_issue_country')->toArray();
            }
            if (array_filter($list->pluck('passport_issue_date')->toArray())) {
                $g['passportIssueDate'] = $list->pluck('passport_issue_date')->toArray();
            }
            if (array_filter($list->pluck('passport_exp')->toArray())) {
                $g['passportExpiryDate'] = $list->pluck('passport_exp')->toArray();
            }
            if (array_filter($list->pluck('frequent_flyer_number')->toArray())) {
                $g['frequentFlyrNum'] = $list->pluck('frequent_flyer_number')->toArray();
            }
            if (! empty($baggageOut)) {
                $g['ExtraServiceOutbound'] = array_fill(0, $list->count(), $baggageOut);
            }
            if (! empty($baggageIn)) {
                $g['ExtraServiceInbound'] = array_fill(0, $list->count(), $baggageIn);
            }

            return $g;
        };

        $paxDetails = [[]];
        if ($adults->isNotEmpty()) {
            $paxDetails[0]['adult'] = $buildPaxGroup($adults);
        }
        if ($children->isNotEmpty()) {
            $paxDetails[0]['child'] = $buildPaxGroup($children);
        }
        if ($infants->isNotEmpty()) {
            $paxDetails[0]['infant'] = $buildPaxGroup($infants);
        }

        $payload = [
            'flightBookingInfo' => [
                'flight_session_id' => $flightSessionId,
                'fare_source_code' => $fareSourceCode,
                'IsPassportMandatory' => $isPassportMand ? 'true' : 'false',
                'fareType' => $fareType,
                'areaCode' => $contact['area_code'],
                'countryCode' => $contact['country_code'],
            ],
            'paxInfo' => [
                'customerEmail' => $contact['email'],
                'customerPhone' => $contact['phone'],
                'paxDetails' => $paxDetails,
            ],
        ];

        try {
            $response = $this->post('booking', $payload, 90, log: [
                'route' => FlightDisplay::route($flight),
                'trip_type' => $flight['directionInd'] ?? null,
                'passenger_counts' => [
                    'adults' => $adults->count(),
                    'children' => $children->count(),
                    'infants' => $infants->count(),
                ],
            ]);

            if ($response->failed()) {
                Log::error('FlightBooking API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                return ['error' => true, 'message' => 'Booking request failed. Please try again.', 'data' => []];
            }

            $data = $response->json();
            $bookResult = $data['BookFlightResponse']['BookFlightResult'] ?? [];
            $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $success) {
                Log::warning('FlightBooking API returned unsuccessful result', [
                    'response' => $data,
                    'payload' => $payload,
                ]);
            }

            return ['error' => false, 'message' => '', 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('FlightBooking API error', ['message' => $e->getMessage()]);

            return ['error' => true, 'message' => 'A network error occurred. Please try again.', 'data' => []];
        }
    }

    // =========================================================================
    //  Transport
    // =========================================================================
    /**
     * POST to a TravelNext endpoint with the account credentials prepended
     * (unless $withCredentials is false) and the call recorded in
     * flight_supplier_calls. Connection failures are recorded and rethrown —
     * every caller already decides for itself what a failure means.
     *
     * $log adds route / cabin / trip_type / passenger_counts / search_id to
     * the recorded row.
     */
    public function post(string $endpoint, array $payload = [], int $timeout = 60, bool $withCredentials = true, array $log = []): Response
    {
        $body = $withCredentials ? array_merge($this->credentials(), $payload) : $payload;
        $startedAt = microtime(true);

        try {
            $response = Http::connectTimeout(10)->timeout($timeout)->post($this->url($endpoint), $body);
        } catch (\Throwable $exception) {
            $this->logCall($endpoint, array_merge($log, [
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $this->scrub($exception->getMessage()),
            ]));

            throw $exception;
        }

        $this->logCall($endpoint, array_merge($log, [
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'success' => $response->successful(),
            'http_status' => $response->status(),
            'error_message' => $response->successful() ? null : $this->scrub(Str::limit((string) $response->body(), 500)),
        ]));

        return $response;
    }

    /**
     * An error body or exception message is stored verbatim for diagnosis; a
     * supplier that echoes the request back must not put the account
     * password into flight_supplier_calls with it.
     */
    private function scrub(string $text): string
    {
        $secrets = array_filter(
            array_map('strval', $this->credentials()),
            fn (string $value): bool => strlen($value) >= 4,
        );

        return $secrets === [] ? $text : str_replace($secrets, '[redacted]', $text);
    }

    public function credentials(): array
    {
        return [
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),
        ];
    }

    /**
     * The payload as it may be shown to an admin or stored with a ticketing
     * record — credentials replaced, everything else intact.
     */
    public function redact(array $payload): array
    {
        foreach (self::CREDENTIAL_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }

    public function url(string $endpoint): string
    {
        return config('services.travelnext.base_url').$endpoint;
    }

    public function mapCabin(string $code): string
    {
        return match (strtoupper($code)) {
            'S' => 'PremiumEconomy',
            'C' => 'Business',
            'F' => 'First',
            default => 'Economy',
        };
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================
    private function logCall(string $endpoint, array $attributes): void
    {
        FlightSupplierCall::record(array_merge([
            'supplier' => self::KEY,
            'call_type' => self::CALL_TYPES[$endpoint] ?? Str::limit($endpoint, 20, ''),
        ], $attributes));
    }

    private function selectLogAttributes(?array $searchedFlight, array $criteria): array
    {
        return [
            'route' => $searchedFlight ? FlightDisplay::route($searchedFlight) : null,
            'cabin' => $searchedFlight ? FlightDisplay::cabin($searchedFlight) : null,
            'trip_type' => $criteria['trip'] ?? null,
            'passenger_counts' => [
                'adults' => (int) ($criteria['adults'] ?? 0),
                'children' => (int) ($criteria['childs'] ?? 0),
                'infants' => (int) ($criteria['kids'] ?? 0),
            ],
        ];
    }

    private function routeLabel(array $originDestination): string
    {
        $first = $originDestination[0] ?? [];
        $last = $originDestination[array_key_last($originDestination) ?? 0] ?? [];

        return Str::limit(trim(($first['airportOriginCode'] ?? '').'-'.($last['airportDestinationCode'] ?? ''), '-'), 40, '');
    }

    /**
     * Same '[FlightSearch]' step log FlightController writes, so one search
     * still greps end to end by search_id.
     */
    private function logStep(array $context, string $step, array $data = [], string $level = 'info'): void
    {
        $startedAt = (float) ($context['started_at'] ?? 0.0);

        Log::log($level, '[FlightSearch] '.$step, array_merge([
            'search_id' => ($context['search_id'] ?? '') !== '' ? $context['search_id'] : null,
            'elapsed_ms' => $startedAt > 0.0
                ? (int) round((microtime(true) - $startedAt) * 1000)
                : null,
        ], $data));
    }

    private function errorResult(string $message, ?string $surface = null): array
    {
        $result = ['error' => true, 'message' => $message, 'data' => []];

        if ($surface !== null) {
            $result['surface'] = $surface;
        }

        return $result;
    }

    private function airports(): Collection
    {
        return $this->airports ??= collect(
            json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true)
        )->keyBy('AirportCode');
    }

    private function airlines(): Collection
    {
        return $this->airlines ??= collect(
            json_decode(file_get_contents(public_path('assets/data/airline.json')), true)
        )->keyBy('AirLineCode');
    }
}

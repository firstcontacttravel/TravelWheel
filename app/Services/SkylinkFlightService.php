<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\FlightSupplierCall;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Client for the SkyLink (247Travels) flight API: search → pricing → reserve.
 *
 * Unlike TravelNext, SkyLink's /reserve issues a live, billable PNR
 * immediately with no hold concept — callers are responsible for collecting
 * payment before calling reserve(). See the Phase 4 plan for how that's
 * wired into the checkout flow.
 */
class SkylinkFlightService
{
    private const SUPPLIER = 'skylink';

    private ?Collection $airports = null;

    private ?Collection $airlines = null;

    public function __construct(private readonly SkylinkAuthService $auth) {}

    // =========================================================================
    //  search() — POST /api/flights/search
    // =========================================================================
    public function search(array $criteria): array
    {
        $payload = $this->buildSearchPayload($criteria);
        $startedAt = microtime(true);

        try {
            $response = $this->request(fn ($client) => $client->post('flights/search', $payload));
        } catch (\Throwable $exception) {
            $this->logCall([
                'call_type' => 'search',
                'route' => $this->routeLabelFromCriteria($criteria),
                'cabin' => $payload['class'] ?? null,
                'trip_type' => $criteria['trip'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'passenger_counts' => $this->passengerCounts($criteria),
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $exception->getMessage(),
            ]);

            return $this->errorResult('SkyLink flight search is temporarily unavailable. Please try again shortly.');
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $decoded = SkylinkAuthService::decodeJson($response);

        if ($blocked = $this->blockedError($response, $decoded)) {
            $this->logCall([
                'call_type' => 'search',
                'route' => $this->routeLabelFromCriteria($criteria),
                'cabin' => $payload['class'] ?? null,
                'trip_type' => $criteria['trip'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'passenger_counts' => $this->passengerCounts($criteria),
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $blocked,
            ]);

            return $this->errorResult($blocked);
        }

        if ($response->failed() || ! data_get($decoded, 'success', true)) {
            $message = $this->extractErrorMessage($decoded, 'SkyLink flight search failed. Please try again.');
            $this->logCall([
                'call_type' => 'search',
                'route' => $this->routeLabelFromCriteria($criteria),
                'cabin' => $payload['class'] ?? null,
                'trip_type' => $criteria['trip'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'passenger_counts' => $this->passengerCounts($criteria),
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $message,
            ]);

            return $this->errorResult($message);
        }

        $rawFlights = (array) data_get($decoded, 'data.flights', []);
        $flights = array_values(array_map(
            fn (array $raw) => $this->mapSearchResult($raw, $criteria),
            array_filter($rawFlights, 'is_array')
        ));

        $cheapest = collect($flights)->pluck('price')->filter()->min();

        $this->logCall([
            'call_type' => 'search',
            'route' => $this->routeLabelFromCriteria($criteria),
            'cabin' => $payload['class'] ?? null,
            'trip_type' => $criteria['trip'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'price' => $cheapest,
            'passenger_counts' => $this->passengerCounts($criteria),
            'response_time_ms' => $durationMs,
            'success' => true,
            'http_status' => $response->status(),
        ]);

        return [
            'error' => false,
            'message' => null,
            'data' => [
                'flights' => $flights,
                'meta' => (array) data_get($decoded, 'data.meta', []),
            ],
        ];
    }

    // =========================================================================
    //  price() — POST /api/flights/pricing (re-validate before reserve)
    // =========================================================================
    public function price(string $bookingToken, array $passengers, array $options = []): array
    {
        $payload = [
            'booking_token' => $bookingToken,
            'passengers' => $this->passengerPayload($passengers),
            // Confirmed via live testing: SkyLink ignores this field entirely and
            // always prices in NGN. Sent anyway in case a future API version
            // honors it; mapSearchResult()/price() convert NGN back to USD below
            // so every SkyLink price flows through the same USD-supplier
            // contract FlightMarkup::apply() and cross-supplier logging expect.
            'currency' => $options['currency'] ?? 'USD',
            'class' => $options['class'] ?? 'economy',
        ];
        $context = $options['context'] ?? [];
        $startedAt = microtime(true);

        try {
            $response = $this->request(fn ($client) => $client->post('flights/pricing', $payload));
        } catch (\Throwable $exception) {
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'pricing',
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->errorResult('SkyLink price verification is temporarily unavailable. Please try again shortly.');
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $decoded = SkylinkAuthService::decodeJson($response);

        if ($blocked = $this->blockedError($response, $decoded)) {
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'pricing',
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $blocked,
            ]));

            return $this->errorResult($blocked);
        }

        if ($response->failed() || ! data_get($decoded, 'data.verified', false)) {
            $message = $this->extractErrorMessage($decoded, 'This fare could not be verified. Please select another flight.');
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'pricing',
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $message,
            ]));

            return $this->errorResult($message);
        }

        // SkyLink always answers in NGN regardless of the requested currency —
        // convert back to USD so this stays comparable to the USD figure the
        // matching search() call logged, and so it round-trips correctly
        // through FlightMarkup::apply()'s USD -> NGN conversion later.
        $verifiedPriceNgn = (float) data_get($decoded, 'data.verified_price', 0);
        $verifiedPrice = $this->ngnToUsd($verifiedPriceNgn);

        $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
            'call_type' => 'pricing',
            'price' => $verifiedPrice,
            'response_time_ms' => $durationMs,
            'success' => true,
            'http_status' => $response->status(),
        ]));

        $perPassengerNgn = (array) data_get($decoded, 'data.per_passenger', []);

        return [
            'error' => false,
            'message' => data_get($decoded, 'data.message'),
            'data' => [
                'bookingToken' => data_get($decoded, 'data.booking_token', $bookingToken),
                'priceChanged' => (bool) data_get($decoded, 'data.price_changed', false),
                'classShifted' => (bool) data_get($decoded, 'data.class_letter_changed', false)
                    || (bool) data_get($decoded, 'data.cabin_class_shifted', false),
                'originalClassLetter' => data_get($decoded, 'data.original_class_letter'),
                'newClassLetter' => data_get($decoded, 'data.new_class_letter'),
                'originalPrice' => $this->ngnToUsd((float) data_get($decoded, 'data.original_price', 0)),
                'verifiedPrice' => $verifiedPrice,
                'perPassenger' => array_map(fn ($amount) => $this->ngnToUsd((float) $amount), $perPassengerNgn),
                // Always 'USD' here — see the FlightMarkup contract note above.
                // The API's own currency field (always "NGN") is preserved raw below.
                'currency' => 'USD',
                'verificationSkipped' => (bool) data_get($decoded, 'data.verification_skipped', false),
                'reason' => data_get($decoded, 'data.reason'),
                'expiresAt' => data_get($decoded, 'data.expires_at'),
                'raw' => (array) data_get($decoded, 'data', []),
            ],
        ];
    }

    // =========================================================================
    //  reserve() — POST /api/flights/reserve (creates a live PNR immediately)
    // =========================================================================
    public function reserve(string $bookingToken, array $travellers, array $passengers, array $options = []): array
    {
        $payload = [
            'booking_token' => $bookingToken,
            'passengers' => $this->passengerPayload($passengers),
            'travellers' => $travellers,
            'ticket_time_limit_hours' => $options['ticket_time_limit_hours'] ?? 48,
        ];
        $context = $options['context'] ?? [];
        $startedAt = microtime(true);

        try {
            $response = $this->request(fn ($client) => $client->post('flights/reserve', $payload));
        } catch (\Throwable $exception) {
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'reserve',
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $exception->getMessage(),
            ]));

            return $this->errorResult('SkyLink was unable to confirm this reservation. Please contact support before retrying — payment may already be captured.');
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $decoded = SkylinkAuthService::decodeJson($response);

        if ($blocked = $this->blockedError($response, $decoded)) {
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'reserve',
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $blocked,
            ]));

            return $this->errorResult($blocked);
        }

        if ($response->failed() || ! data_get($decoded, 'success', false)) {
            $message = $this->extractErrorMessage($decoded, 'Reservation failed. Please contact support.');
            $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
                'call_type' => 'reserve',
                'response_time_ms' => $durationMs,
                'success' => false,
                'http_status' => $response->status(),
                'error_message' => $message,
            ]));

            return $this->errorResult($message);
        }

        $this->logCall(array_merge($this->contextLogAttributes($context, $passengers), [
            'call_type' => 'reserve',
            'response_time_ms' => $durationMs,
            'success' => true,
            'http_status' => $response->status(),
        ]));

        return [
            'error' => false,
            'message' => data_get($decoded, 'data.message'),
            'data' => [
                'pnr' => data_get($decoded, 'data.pnr'),
                'bookingReference' => data_get($decoded, 'data.booking_reference'),
                'carrier' => data_get($decoded, 'data.carrier'),
                'status' => data_get($decoded, 'data.status'),
                'ticketDeadline' => data_get($decoded, 'data.ticket_deadline'),
                'raw' => (array) data_get($decoded, 'data', []),
            ],
        ];
    }

    // =========================================================================
    //  Search-result mapper — SkyLink's real (undocumented) shape → the
    //  canonical flight array used across FlightMarkup / FlightDisplay / the
    //  booking views.
    //
    //  The PDF documentation describes a flat per-flight object with a single
    //  segment's fields inlined. The real sandbox API instead returns:
    //    - segments: an array of "directions" (outbound, and for a roundtrip
    //      a second direction for the return), each itself an array of
    //      physical leg objects — i.e. segments[direction][leg].
    //    - full airline names in `airline`, with the 2-letter code in `img`.
    //    - 12-hour "hh:mm am/pm" times and "dd-mm-Y" dates per leg.
    //    - a `duration_time`/`total_duration` on every leg that is actually
    //      the whole direction's total (repeated identically on each leg) —
    //      `seg_duration` is the one field that holds that leg's own time.
    //    - richer flight-level detail (`baggage_allowance`, `fare_rules`,
    //      `amenities`, `flight_details`) not modeled here yet since nothing
    //      downstream consumes it; only what the canonical shape needs is
    //      mapped.
    // =========================================================================
    public function mapSearchResult(array $raw, array $criteria = []): array
    {
        $directions = $this->normalizeSegmentDirections((array) ($raw['segments'] ?? []));
        $outboundLegs = $directions[0] ?? [];
        $returnLegs = $directions[1] ?? [];

        $outboundSegments = array_values(array_map(fn (array $leg) => $this->mapLegSegment($leg), $outboundLegs));
        $returnSegments = array_values(array_map(fn (array $leg) => $this->mapLegSegment($leg), $returnLegs));
        // The mapped (not raw) first leg — carries airlineCode/cabin/etc. under
        // their canonical key names rather than SkyLink's raw field names.
        $firstLeg = $outboundSegments[0] ?? [];
        // The raw first leg, for fields the canonical shape doesn't rename
        // (duration_time's whole-itinerary-total label, etc.).
        $firstRawLeg = $outboundLegs[0] ?? [];

        // SkyLink always prices in NGN regardless of the requested currency —
        // convert back to USD immediately so this flight carries the same
        // USD-supplier-price contract as TravelNext, letting FlightMarkup::apply()
        // and the Phase 2 cross-supplier comparison tool treat both suppliers
        // identically. The original NGN figures are kept under skylink*NGN keys.
        $priceNgn = (float) ($raw['price'] ?? 0);
        $baseFareNgn = (float) ($raw['actual_adult_base'] ?? $priceNgn);
        $price = $this->ngnToUsd($priceNgn);
        $baseFare = $this->ngnToUsd($baseFareNgn);

        return [
            // fareSourceCode kept for compatibility with the existing
            // `fare_source_code` session/validation contract shared by both
            // suppliers — for SkyLink flights it IS the booking_token.
            'fareSourceCode' => (string) ($raw['booking_token'] ?? ''),
            'skylinkBookingToken' => (string) ($raw['booking_token'] ?? ''),
            'skylinkPriceNgn' => $priceNgn,
            'source' => self::SUPPLIER,
            'detailLevel' => 'summary',
            'airline' => $firstLeg['airline'] ?? '',
            'airlineCode' => $firstLeg['airlineCode'] ?? '',
            'airlineLogo' => $firstLeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
            'cabin' => $firstLeg['cabin'] ?? 'Economy',
            'cabinCode' => $firstLeg['cabinCode'] ?? 'Y',
            'stops' => max(count($outboundLegs) - 1, 0),
            'price' => $price,
            'baseFare' => $baseFare,
            'totalTax' => 0.0,
            'currency' => 'USD',
            'isRefundable' => (bool) ($firstLeg['refundable'] ?? false),
            'fareType' => (bool) ($raw['is_private_fare'] ?? false) ? 'Private' : 'Public',
            'ticketType' => 'eTicket',
            'isPassportMandatory' => false,
            'directionInd' => strtolower((string) ($criteria['trip'] ?? 'oneway')),
            'segments' => $outboundSegments,
            'departTime' => $outboundSegments[0]['departTime'] ?? '',
            'arriveTime' => end($outboundSegments)['arriveTime'] ?? '',
            'departDT' => $outboundSegments[0]['departDT'] ?? null,
            'arriveDT' => end($outboundSegments)['arriveDT'] ?? null,
            'totalDuration' => $this->directionTotalMinutes($outboundLegs),
            'durationLabel' => (string) ($firstRawLeg['duration_time'] ?? ''),
            'layoverDurations' => $this->layoverDurations($outboundSegments),
            'departDateLabel' => $outboundSegments[0]['departDate'] ?? '',
            'totalTimeMins' => $this->directionTotalMinutes($outboundLegs),
            'totalTimeLabel' => (string) ($firstRawLeg['duration_time'] ?? ''),
            'returnSegments' => $returnSegments,
            'multiLegs' => [],
            'fareBreakdown' => [],
            'seatsLeft' => (int) ($raw['seats_left'] ?? $firstLeg['seatsLeft'] ?? 9),
            'lastTicketingDate' => $raw['last_ticketing_date'] ?? null,
        ];
    }

    /**
     * SkyLink nests segments as [direction][leg] (outbound direction, and for
     * a roundtrip a second direction for the return). Fall back to treating
     * the array as a single flat direction if it doesn't look nested, so a
     * shape drift degrades gracefully instead of silently dropping segments.
     */
    private function normalizeSegmentDirections(array $segments): array
    {
        if ($segments === []) {
            return [];
        }

        $first = reset($segments);

        if (is_array($first) && array_is_list($first) && is_array($first[0] ?? null)) {
            return array_values($segments);
        }

        return [array_values($segments)];
    }

    private function mapLegSegment(array $leg): array
    {
        $fromCode = strtoupper((string) ($leg['departure_code'] ?? ''));
        $toCode = strtoupper((string) ($leg['arrival_code'] ?? ''));
        $fromAirport = $this->airports()->get($fromCode);
        $toAirport = $this->airports()->get($toCode);
        $airlineCode = strtoupper((string) ($leg['img'] ?? ''));
        $airline = $this->airlines()->get($airlineCode);

        $departDT = $this->parseLegDateTime((string) ($leg['departure_date'] ?? ''), (string) ($leg['departure_time'] ?? ''));
        $arriveDT = $this->parseLegDateTime((string) ($leg['arrival_date'] ?? ''), (string) ($leg['arrival_time'] ?? ''));

        return [
            'from' => $fromCode,
            'to' => $toCode,
            'fromCity' => $fromAirport ? ($fromAirport['City'].' ('.$fromCode.')') : ((string) ($leg['departure_city'] ?? $fromCode)),
            'toCity' => $toAirport ? ($toAirport['City'].' ('.$toCode.')') : ((string) ($leg['arrival_city'] ?? $toCode)),
            'fromAirport' => $fromAirport['AirportName'] ?? (string) ($leg['departure_airport'] ?? $fromCode),
            'toAirport' => $toAirport['AirportName'] ?? (string) ($leg['arrival_airport'] ?? $toCode),
            'fromCountry' => $fromAirport['Country'] ?? '',
            'toCountry' => $toAirport['Country'] ?? '',
            'departTime' => (string) ($leg['departure_time'] ?? ''),
            'arriveTime' => (string) ($leg['arrival_time'] ?? ''),
            'departDate' => $departDT?->format('D, d M Y') ?? '',
            'arriveDate' => $arriveDT?->format('D, d M Y') ?? '',
            'departDT' => $departDT?->toIso8601String(),
            'arriveDT' => $arriveDT?->toIso8601String(),
            // seg_duration is this leg's own flight time; duration_time /
            // total_duration hold the whole direction's total (see class doc).
            'duration' => $this->durationToMinutes($leg['seg_duration'] ?? $leg['duration_time'] ?? 0),
            'flightNo' => (string) ($leg['flight_no'] ?? ''),
            'airline' => (string) ($leg['airline'] ?? ''),
            'airlineCode' => $airlineCode,
            // Some airline.json entries carry an empty-string logo rather than
            // omitting the field entirely — ?? alone doesn't fall through that.
            'airlineLogo' => data_get($airline, 'AirLineLogo') ?: '/assets/img/airlines/default.png',
            'cabin' => $this->cabinLabel((string) ($leg['class'] ?? 'Economy')),
            'cabinCode' => strtoupper((string) ($leg['class_letter'] ?? substr((string) ($leg['class'] ?? 'Economy'), 0, 1))),
            'baggage' => (string) ($leg['baggage'] ?? ''),
            'cabinBaggage' => (string) ($leg['cabin_baggage'] ?? ''),
            'seatsLeft' => (int) ($leg['seats_left'] ?? 9),
            'isCodeshare' => false,
            'refundable' => (bool) ($leg['refundable'] ?? false),
            'departTerminal' => $leg['departure_terminal'] ?? null,
            'arriveTerminal' => $leg['arrival_terminal'] ?? null,
        ];
    }

    /**
     * SkyLink's departure_date/arrival_date are "dd-mm-Y" and times are
     * 12-hour "hh:mm am/pm" (e.g. "05-10-2026" / "07:15 pm") — not the ISO
     * date + 24h time the PDF documentation's examples implied.
     */
    private function parseLegDateTime(string $date, string $time): ?Carbon
    {
        if ($date === '' || $time === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y h:i a', $date.' '.strtolower($time));
        } catch (\Throwable) {
            return null;
        }
    }

    private function directionTotalMinutes(array $legs): int
    {
        if ($legs === []) {
            return 0;
        }

        $first = $legs[0];
        $minutes = $this->durationToMinutes($first['total_duration'] ?? $first['duration_time'] ?? 0);

        if ($minutes > 0) {
            return $minutes;
        }

        // Fallback if the whole-itinerary total is missing: sum each leg's
        // own flight time (this undercounts layovers, but never overcounts).
        return collect($legs)->sum(fn (array $leg) => $this->durationToMinutes($leg['seg_duration'] ?? $leg['duration_time'] ?? 0));
    }

    private function layoverDurations(array $mappedSegments): array
    {
        $layovers = [];

        for ($i = 0; $i < count($mappedSegments) - 1; $i++) {
            $arrive = $mappedSegments[$i]['arriveDT'] ?? null;
            $depart = $mappedSegments[$i + 1]['departDT'] ?? null;

            if ($arrive && $depart) {
                $minutes = (int) round((Carbon::parse($depart)->getTimestamp() - Carbon::parse($arrive)->getTimestamp()) / 60);
                $layovers[] = max(0, $minutes);
            }
        }

        return $layovers;
    }

    private function cabinLabel(string $value): string
    {
        $value = trim($value);

        return $value === '' ? 'Economy' : Str::of($value)->replace('_', ' ')->title()->toString();
    }

    private function usdToNgnRate(): float
    {
        return ExchangeRate::rateFor('USD');
    }

    private function ngnToUsd(float $ngn): float
    {
        $rate = $this->usdToNgnRate();

        return $rate > 0 ? round($ngn / $rate, 2) : $ngn;
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================
    private function request(\Closure $callback): Response
    {
        $response = $callback($this->auth->authorizedClient());

        if ($response->status() === 401) {
            $response = $callback($this->auth->client()->withToken($this->auth->refreshToken()));
        }

        return $response;
    }

    private function buildSearchPayload(array $criteria): array
    {
        $tripMap = ['oneway' => 'oneway', 'return' => 'roundtrip', 'multi' => 'multicity'];
        $trip = strtolower((string) ($criteria['trip'] ?? 'oneway'));

        $payload = [
            'search_mode' => 'external',
            'flight_type' => $tripMap[$trip] ?? 'oneway',
            'adults' => (int) ($criteria['adults'] ?? 1),
            'children' => (int) ($criteria['childs'] ?? $criteria['children'] ?? 0),
            'infants' => (int) ($criteria['kids'] ?? $criteria['infants'] ?? 0),
            'class' => $this->mapCabinClass((string) ($criteria['flight_type'] ?? 'Y')),
            // Confirmed via live testing: SkyLink ignores this and always
            // returns NGN. Sent anyway for forward-compatibility;
            // mapSearchResult() converts the NGN response back to USD so
            // FlightMarkup::apply() keeps one consistent USD-supplier
            // assumption regardless of which supplier sourced the flight.
            'currency' => 'USD',
        ];

        if ($trip === 'multi' && ! empty($criteria['multi_legs'])) {
            $payload['routes'] = array_map(fn (array $leg): array => [
                'from' => $this->airportCode((string) ($leg['from'] ?? '')),
                'to' => $this->airportCode((string) ($leg['to'] ?? '')),
                'date' => $this->toIsoDate((string) ($leg['depart'] ?? '')),
            ], $criteria['multi_legs']);

            return $payload;
        }

        $payload['from'] = $this->airportCode((string) ($criteria['from'] ?? ''));
        $payload['to'] = $this->airportCode((string) ($criteria['to'] ?? ''));
        $payload['flights_departure_date'] = $this->toIsoDate((string) ($criteria['depart'] ?? ''));

        if ($trip === 'return') {
            $payload['flights_return_date'] = $this->toIsoDate((string) ($criteria['returning'] ?? ''));
        }

        return $payload;
    }

    private function passengerPayload(array $passengers): array
    {
        if (array_key_exists('adults', $passengers)) {
            // Accept both this service's own {children, infants} naming and the
            // app-wide search-criteria naming {childs, kids} (session('searchParamsStore')
            // et al) — passing the latter straight through used to silently drop
            // child/infant counts since only the former was ever read here.
            return [
                'adults' => (int) ($passengers['adults'] ?? 1),
                'children' => (int) ($passengers['children'] ?? $passengers['childs'] ?? 0),
                'infants' => (int) ($passengers['infants'] ?? $passengers['kids'] ?? 0),
            ];
        }

        // Accept the app's native passenger-list shape (type => ADT/CHD/INF) too.
        $counted = collect($passengers)->countBy(fn ($p) => $p['type'] ?? 'ADT');

        return [
            'adults' => (int) $counted->get('ADT', 0) ?: 1,
            'children' => (int) $counted->get('CHD', 0),
            'infants' => (int) $counted->get('INF', 0),
        ];
    }

    private function mapCabinClass(string $code): string
    {
        return match (strtoupper($code)) {
            'S' => 'premium_economy',
            'C' => 'business',
            'F' => 'first',
            default => 'economy',
        };
    }

    private function airportCode(string $value): string
    {
        $between = trim(Str::between($value, '(', ')'));

        return $between !== '' ? strtoupper($between) : strtoupper(trim($value));
    }

    private function toIsoDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Accepts either a "6h 45m"-style label or a plain number of minutes —
     * the real API's numeric-vs-string convention for duration fields isn't
     * consistently documented, so both are handled defensively.
     */
    private function durationToMinutes(mixed $label): int
    {
        if (is_numeric($label)) {
            return (int) $label;
        }

        if (preg_match('/(\d+)h\s*(\d+)?m?/', (string) $label, $matches)) {
            return ((int) $matches[1] * 60) + (int) ($matches[2] ?? 0);
        }

        return 0;
    }

    private function airports(): Collection
    {
        return $this->airports ??= collect(
            json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true) ?: []
        )->keyBy('AirportCode');
    }

    /**
     * Same airline.json reference data TravelNext's own mapper already uses
     * for logos — SkyLink's `img` field is a standard IATA 2-letter code, so
     * this resolves a real logo instead of always falling back to a generic
     * placeholder.
     */
    private function airlines(): Collection
    {
        return $this->airlines ??= collect(
            json_decode(file_get_contents(public_path('assets/data/airline.json')), true) ?: []
        )->keyBy('AirLineCode');
    }

    private function routeLabelFromCriteria(array $criteria): string
    {
        if (! empty($criteria['multi_legs'])) {
            $first = $this->airportCode((string) ($criteria['multi_legs'][0]['from'] ?? ''));
            $last = $this->airportCode((string) (end($criteria['multi_legs'])['to'] ?? ''));

            return trim($first.'-'.$last, '-');
        }

        return trim($this->airportCode((string) ($criteria['from'] ?? '')).'-'.$this->airportCode((string) ($criteria['to'] ?? '')), '-');
    }

    private function passengerCounts(array $criteria): array
    {
        return [
            'adults' => (int) ($criteria['adults'] ?? 0),
            'children' => (int) ($criteria['childs'] ?? $criteria['children'] ?? 0),
            'infants' => (int) ($criteria['kids'] ?? $criteria['infants'] ?? 0),
        ];
    }

    private function contextLogAttributes(array $context, array $passengers): array
    {
        return [
            'route' => $context['route'] ?? null,
            'cabin' => $context['cabin'] ?? null,
            'trip_type' => $context['trip_type'] ?? null,
            'currency' => $context['currency'] ?? 'USD',
            'search_id' => $context['search_id'] ?? null,
            'passenger_counts' => $this->passengerPayload($passengers),
        ];
    }

    private function blockedError(Response $response, array $decoded): ?string
    {
        if ($response->status() !== 403 || ! (bool) data_get($decoded, 'blocked', false)) {
            return null;
        }

        return (string) (data_get($decoded, 'message') ?: 'This airline is not available right now.');
    }

    private function extractErrorMessage(array $decoded, string $fallback): string
    {
        $message = data_get($decoded, 'message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    private function errorResult(string $message): array
    {
        return ['error' => true, 'message' => $message, 'data' => []];
    }

    private function logCall(array $attributes): void
    {
        FlightSupplierCall::record(array_merge(['supplier' => self::SUPPLIER], $attributes));
    }
}

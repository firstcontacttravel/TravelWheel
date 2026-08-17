<?php

namespace App\Http\Controllers;

use App\Support\FlightMarkup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FlightController extends Controller
{
    /**
     * Correlation id that ties every log line of a single search together,
     * across the search → loading → runPendingSearch redirect chain.
     * Grep this id in storage/logs to see one search end-to-end.
     */
    private string $searchLogId = '';

    /**
     * Start time of the current HTTP request, used to report elapsed_ms
     * on every step so slow phases are immediately visible.
     */
    private float $searchStartedAt = 0.0;

    // ─────────────────────────────────────────────────────────────────────────
    // Step logger — every entry carries search_id + elapsed_ms.
    // Trace one search with:  grep <search_id> storage/logs/laravel.log
    // ─────────────────────────────────────────────────────────────────────────
    private function logStep(string $step, array $context = [], string $level = 'info'): void
    {
        Log::log($level, '[FlightSearch] '.$step, array_merge([
            'search_id' => $this->searchLogId !== '' ? $this->searchLogId : null,
            'elapsed_ms' => $this->searchStartedAt > 0.0
                ? (int) round((microtime(true) - $this->searchStartedAt) * 1000)
                : null,
        ], $context));
    }

    public function search(Request $request)
    {
        $this->searchLogId = (string) Str::uuid();
        $this->searchStartedAt = microtime(true);

        // Step: raw request received
        $this->logStep('search request received', [
            'trip' => strtolower((string) $request->input('trip', '')),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'depart' => $request->input('depart'),
            'returning' => $request->input('returning'),
            'adults' => $request->input('adults'),
            'childs' => $request->input('childs'),
            'kids' => $request->input('kids'),
            'flight_type' => $request->input('flight_type'),
            'multi_legs' => is_string($request->input('multi_legs'))
                ? Str::limit((string) $request->input('multi_legs'), 500)
                : $request->input('multi_legs'),
        ]);

        $this->forgetCheckoutSession();

        // Step: validation (failures are logged inside validateSearchRequest)
        $validated = $this->validateSearchRequest($request);
        $this->logStep('search input validated');

        // Step: queue the search and hand off to the loading page
        session([
            'pendingFlightSearch' => $validated,
            'pendingFlightSearchStartedAt' => now()->toIso8601String(),
            'pendingFlightSearchLogId' => $this->searchLogId,
        ]);

        $this->logStep('pending search stored — redirecting to loading page');

        return redirect()->route('flights.search.loading');
    }

    public function loading()
    {
        $this->searchLogId = (string) session('pendingFlightSearchLogId', '');
        $this->searchStartedAt = microtime(true);

        if (! session()->has('pendingFlightSearch')) {
            $this->logStep('loading page hit without a pending search — redirecting to search form', [], 'warning');

            return redirect()->route('air')->withErrors(['error' => 'Please start a new flight search.']);
        }

        $this->logStep('loading page shown');

        return view('livewire.pages.flight.flight-loading-modern', [
            'search' => session('pendingFlightSearch', []),
        ]);
    }

    public function runPendingSearch()
    {
        $this->searchLogId = (string) session('pendingFlightSearchLogId', '');
        $this->searchStartedAt = microtime(true);

        $pending = session('pendingFlightSearch');

        if (! is_array($pending) || $pending === []) {
            $this->logStep('pending search missing or expired — redirecting to search form', [], 'warning');

            return redirect()->route('air')->withErrors(['error' => 'Flight search expired. Please search again.']);
        }

        $this->logStep('resuming pending search', [
            'queued_at' => session('pendingFlightSearchStartedAt'),
        ]);

        return $this->performSearch(new Request($pending));
    }

    private function performSearch(Request $request)
    {
        set_time_limit(120); // give the API call + processing enough headroom

        if ($this->searchLogId === '') {
            $this->searchLogId = (string) Str::uuid();
        }
        if ($this->searchStartedAt === 0.0) {
            $this->searchStartedAt = microtime(true);
        }

        // Step: search execution begins
        $this->logStep('performSearch started');

        $this->forgetCheckoutSession();
        $request->replace($this->validateSearchRequest($request));

        // ── Normalise trip type ───────────────────────────────────────────────
        $request->merge(['trip' => strtolower($request->trip)]);

        // ── Decode multi-city legs if sent as JSON string ─────────────────────
        if ($request->multi_legs && is_string($request->multi_legs)) {
            $request->merge([
                'multi_legs' => json_decode($request->multi_legs, true),
            ]);
        }

        // ── Strip incomplete legs ─────────────────────────────────────────────
        if (! empty($request->multi_legs)) {
            $request->merge([
                'multi_legs' => array_values(array_filter(
                    $request->multi_legs,
                    fn ($leg) => ! empty($leg['from']) && ! empty($leg['to']) && ! empty($leg['depart'])
                )),
            ]);
        }

        // Step: input normalised
        $this->logStep('input normalised', [
            'trip' => $request->trip,
            'multi_leg_count' => is_array($request->multi_legs) ? count($request->multi_legs) : 0,
        ]);

        // ── Validation ────────────────────────────────────────────────────────
        $rules = [
            'trip' => 'required|in:oneway,return,multi',
            'adults' => 'required|integer|min:1|max:9',
            'childs' => 'nullable|integer|min:0|max:9',
            'kids' => 'nullable|integer|min:0|max:9',
            'flight_type' => 'required|in:Y,S,C,F',
        ];

        if ($request->trip !== 'multi') {
            $rules['from'] = 'required|string|max:255';
            $rules['to'] = 'required|string|max:255';
            $rules['depart'] = 'required|date_format:d/m/Y';
        }
        if ($request->trip === 'return') {
            $rules['returning'] = 'required|date_format:d/m/Y|after_or_equal:depart';
        }
        if ($request->trip === 'multi') {
            $rules['multi_legs'] = 'required|array|min:1';
            $rules['multi_legs.*.from'] = 'required|string|max:255';
            $rules['multi_legs.*.to'] = 'required|string|max:255';
            $rules['multi_legs.*.depart'] = 'required|date_format:d/m/Y';
        }

        $validated = $request->validate($rules);

        $totalPassengers = (int) $validated['adults'] + (int) ($validated['childs'] ?? 0) + (int) ($validated['kids'] ?? 0);
        if ($totalPassengers > 9) {
            $this->logStep('validation failed — passenger total exceeds 9', [
                'passengers' => $totalPassengers,
            ], 'warning');

            throw ValidationException::withMessages([
                'passengers' => 'The total number of passengers must not exceed 9 per booking.',
            ]);
        }

        // Step: validation passed
        $this->logStep('validation passed', [
            'trip' => $validated['trip'],
            'passengers' => $totalPassengers,
            'cabin' => $validated['flight_type'],
        ]);

        // ── Build origin-destination payload ──────────────────────────────────
        $originDestination = [];
        $journeyType = match ($request->trip) {
            'oneway' => 'OneWay',
            'return' => 'Return',
            'multi' => 'Circle',
            default => 'OneWay',
        };

        $fromCode = Str::between($request->from ?? '', '(', ')');
        $toCode = Str::between($request->to ?? '', '(', ')');

        if ($validated['trip'] === 'oneway') {
            $originDestination[] = [
                'departureDate' => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                'airportOriginCode' => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($validated['trip'] === 'return') {
            $originDestination[] = [
                'departureDate' => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                'returnDate' => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['returning'])->format('Y-m-d'),
                'airportOriginCode' => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($validated['trip'] === 'multi') {
            foreach ($validated['multi_legs'] as $leg) {
                $lFrom = Str::between($leg['from'], '(', ')');
                $lTo = Str::between($leg['to'], '(', ')');
                $originDestination[] = [
                    'departureDate' => \Carbon\Carbon::createFromFormat('d/m/Y', $leg['depart'])->format('Y-m-d'),
                    'airportOriginCode' => $lFrom,
                    'airportDestinationCode' => $lTo,
                ];
            }
        }

        // Step: origin-destination payload built
        $this->logStep('origin-destination payload built', [
            'journeyType' => $journeyType,
            'legs' => count($originDestination),
            'routes' => array_map(
                fn (array $od): string => ($od['airportOriginCode'] ?? '?').'-'.($od['airportDestinationCode'] ?? '?'),
                $originDestination
            ),
        ]);

        // ── API call ──────────────────────────────────────────────────────────
        $payload = [
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),

            'requiredCurrency' => 'USD',
            'journeyType' => $journeyType,
            'OriginDestinationInfo' => $originDestination,

            'class' => $this->mapCabin($validated['flight_type']),
            'adults' => (int) $validated['adults'],
            'childs' => (int) ($validated['childs'] ?? 0),
            'infants' => (int) ($validated['kids'] ?? 0),
        ];

        // Step: payload prepared — credentials are stripped before logging.
        // Never write user_id / user_password / access / ip_address to the log.
        $loggablePayload = $payload;
        unset(
            $loggablePayload['user_id'],
            $loggablePayload['user_password'],
            $loggablePayload['access'],
            $loggablePayload['ip_address']
        );
        $this->logStep('availability payload prepared', ['payload' => $loggablePayload]);

        // Step: outbound API call
        $this->logStep('calling availability API');
        $apiStart = microtime(true);

        try {
            $response = Http::connectTimeout(10)->timeout(60)
                ->post(config('services.travelnext.base_url').'availability', $payload);
        } catch (\Throwable $exception) {
            $this->logStep('availability request threw an exception', [
                'error' => $exception->getMessage(),
                'trip' => $request->trip,
                'duration_ms' => (int) round((microtime(true) - $apiStart) * 1000),
            ], 'error');

            return redirect()->route('air')->withErrors([
                'error' => 'Flight search is temporarily unavailable. Please try again shortly.',
            ]);
        }

        // Step: response received
        $this->logStep('availability API responded', [
            'status' => $response->status(),
            'duration_ms' => (int) round((microtime(true) - $apiStart) * 1000),
            'body_bytes' => strlen((string) $response->body()),
        ]);

        if ($response->failed()) {
            $this->logStep('availability returned an error response', [
                'status' => $response->status(),
                'trip' => $request->trip,
                'body_excerpt' => Str::limit((string) $response->body(), 1000),
            ], 'warning');

            return redirect()->route('air')->withErrors(['error' => 'Flight search failed. Please try again.']);
        }

        $jsonData = $response->json();

        // Step: response parsed
        $itCount = count(data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []));
        $firstIt = data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries.0.FareItinerary', null);
        $odoCount = $firstIt ? count($firstIt['OriginDestinationOptions'] ?? []) : null;
        $apiErr = data_get($jsonData, 'AirSearchResponse.AirSearchResult.Errors')
                 ?? data_get($jsonData, 'Errors')
                 ?? null;

        $this->logStep('availability response parsed', [
            'trip' => $request->trip,
            'itinerary_count' => $itCount,
            'first_odo_count' => $odoCount,
            'api_errors' => $apiErr,
            'session_id_present' => filled(data_get($jsonData, 'AirSearchResponse.session_id')),
        ]);

        // ── Reference data ────────────────────────────────────────────────────
        $airlines = collect(
            json_decode(file_get_contents(public_path('assets/data/airline.json')), true)
        )->keyBy('AirLineCode');

        $airports = collect(
            json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true)
        )->keyBy('AirportCode');

        // Step: reference data loaded
        $this->logStep('reference data loaded', [
            'airlines' => $airlines->count(),
            'airports' => $airports->count(),
        ]);

        $tripType = $request->trip;
        $searchLegs = $request->multi_legs ?? [];
        $itineraries = data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []);

        $mapSegments = function (array $odo) use ($airlines, $airports): array {
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
                ->map(function ($seg) use ($airlines, $airports) {
                    $fs = $seg['FlightSegment'];
                    $dep = \Carbon\Carbon::parse($fs['DepartureDateTime']);
                    $arr = \Carbon\Carbon::parse($fs['ArrivalDateTime']);
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
                        'airlineLogo' => $airline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                        'equipment' => $fs['OperatingAirline']['Equipment'] ?? '',
                        'cabin' => $fs['CabinClassText'] ?? '',
                        'cabinCode' => $fs['CabinClassCode'] ?? 'Y',
                        'resBookCode' => $seg['ResBookDesigCode'] ?? '',
                        'mealCode' => $fs['MealCode'] ?? '',
                        'seatsLeft' => (int) ($seg['SeatsRemaining']['Number'] ?? 9),
                        'belowMinimum' => (bool) ($seg['SeatsRemaining']['BelowMinimum'] ?? false),
                        'isCodeshare' => $opCode !== $airlineCode,
                        'operatingCode' => $opCode,
                        'operatingAirline' => $fs['OperatingAirline']['Name'] ?? '',
                        'operatingFlightNo' => $opCode.($fs['OperatingAirline']['FlightNumber'] ?? ''),
                        'operatingLogo' => $opAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                        'eticket' => (bool) ($fs['Eticket'] ?? true),
                    ];
                })->values()->toArray();
        };

        $calcLayovers = function (array $segments): array {
            $durations = [];
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $arrive = \Carbon\Carbon::parse($segments[$i]['arriveDT']);
                $depart = \Carbon\Carbon::parse($segments[$i + 1]['departDT']);
                $mins = $arrive->diffInMinutes($depart);
                $durations[] = floor($mins / 60).'h '.($mins % 60).'m';
            }

            return $durations;
        };

        $calcLayoverMins = function (array $segments): int {
            $total = 0;
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $arrive = \Carbon\Carbon::parse($segments[$i]['arriveDT']);
                $depart = \Carbon\Carbon::parse($segments[$i + 1]['departDT']);
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

        // Step: itinerary mapping begins
        $this->logStep('mapping itineraries started', ['itineraries' => $itCount]);
        $mapStart = microtime(true);

        $flights = collect($itineraries)->values()->map(
            function ($item, $index) use (
                $tripType, $searchLegs,
                $mapSegments, $calcLayovers, $calcLayoverMins, $fmtMins,
                $splitMultiLegs, $airlines
            ) {
                if (! is_array($item)
                    || ! is_array($item['FareItinerary'] ?? null)
                    || ! is_array(data_get($item, 'FareItinerary.AirItineraryFareInfo'))
                    || ! is_array(data_get($item, 'FareItinerary.OriginDestinationOptions'))) {
                    // Only visible when LOG_LEVEL=debug — avoids noise in production
                    $this->logStep('itinerary skipped — malformed structure', ['index' => $index], 'debug');

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
                            $returnDateLabel = \Carbon\Carbon::parse($returnSegments[0]['departDT'])->format('D, d M');
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
                                                    ? \Carbon\Carbon::parse($legSegs[0]['departDT'])->format('D, d M')
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
                    $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
                }

                $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) {
                    return [
                        'passengerType' => $fb['PassengerTypeQuantity']['Code'],
                        'qty' => (int) $fb['PassengerTypeQuantity']['Quantity'],
                        'baseFare' => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                        'totalFare' => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                        'currency' => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
                        'baggage' => $fb['Baggage'] ?? [],
                        'cabinBaggage' => \App\Support\FlightDisplay::cabinBaggageValues($fb['CabinBaggage'] ?? []),
                        'taxes' => $fb['PassengerFare']['Taxes'] ?? [],
                        'serviceTax' => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                        'surcharges' => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
                        'changeAllowed' => $fb['PenaltyDetails']['ChangeAllowed'] ?? false,
                        'changePenalty' => $fb['PenaltyDetails']['ChangePenaltyAmount'] ?? '0.00',
                        'refundAllowed' => $fb['PenaltyDetails']['RefundAllowed'] ?? false,
                        'refundPenalty' => $fb['PenaltyDetails']['RefundPenaltyAmount'] ?? null,
                    ];
                })->values()->toArray();

                $mappedFlight = [
                    'id' => $index,
                    'fareSourceCode' => $fareInfo['FareSourceCode'],
                    'airline' => $firstSeg['airline'] ?? '',
                    'airlineCode' => $firstSeg['airlineCode'] ?? '',
                    'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
                    'validatingCode' => $validatingCode,
                    'validatingAirline' => $validatingAir['AirLineName'] ?? $validatingCode,
                    'validatingLogo' => $validatingAir['AirLineLogo'] ?? '/assets/img/airlines/default.png',
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

                return FlightMarkup::apply($mappedFlight);
            }
        )->filter()->values()->toArray();

        // Step: mapping complete
        $this->logStep('mapping complete', [
            'mapped' => count($flights),
            'skipped' => max(0, $itCount - count($flights)),
            'duration_ms' => (int) round((microtime(true) - $mapStart) * 1000),
        ]);

        // ── Write ONLY to durable session — no flash data needed ─────────────
        // The Livewire FlightPage component reads directly from these session
        // keys in mount(), so data persists across refreshes and back-navigation.
        session()->forget(['pendingFlightSearch', 'pendingFlightSearchStartedAt', 'pendingFlightSearchLogId']);

        session([
            'flightResultsStore' => $flights,
            'searchParamsStore' => $validated,
            'searchSessionId' => data_get($jsonData, 'AirSearchResponse.session_id', ''),
        ]);

        // Step: done — results stored, redirecting to results page
        $this->logStep('results stored in session — redirecting to results page', [
            'flights' => count($flights),
        ]);

        // Plain redirect — no ->with([...]) flash needed
        return redirect()->route('air.flight-s');
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function mapCabin(string $code): string
    {
        return match ($code) {
            'Y' => 'Economy',
            'S' => 'PremiumEconomy',
            'C' => 'Business',
            'F' => 'First',
            default => 'Economy',
        };
    }

    private function validateSearchRequest(Request $request): array
    {
        $input = $request->except('_token');
        $input['trip'] = strtolower((string) ($input['trip'] ?? ''));

        if (isset($input['multi_legs']) && is_string($input['multi_legs'])) {
            $input['multi_legs'] = json_decode($input['multi_legs'], true);
        }

        $rules = [
            'trip' => 'required|in:oneway,return,multi',
            'adults' => 'required|integer|min:1|max:9',
            'childs' => 'nullable|integer|min:0|max:9',
            'kids' => 'nullable|integer|min:0|max:9',
            'flight_type' => 'required|in:Y,S,C,F',
        ];

        if (($input['trip'] ?? null) !== 'multi') {
            $rules += [
                'from' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/'],
                'to' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/'],
                'depart' => 'required|date_format:d/m/Y|after_or_equal:today',
            ];
        }

        if (($input['trip'] ?? null) === 'return') {
            $rules['returning'] = 'required|date_format:d/m/Y|after_or_equal:depart';
        }

        if (($input['trip'] ?? null) === 'multi') {
            $rules += [
                'multi_legs' => 'required|array|min:2|max:6',
                'multi_legs.*.from' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/'],
                'multi_legs.*.to' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/'],
                'multi_legs.*.depart' => 'required|date_format:d/m/Y|after_or_equal:today',
                'multi_legs.*.cabin' => 'nullable|in:Y,S,C,F',
            ];
        }

        $validator = Validator::make($input, $rules);
        $validator->after(function ($validator) use ($input): void {
            $adults = (int) ($input['adults'] ?? 0);
            $children = (int) ($input['childs'] ?? 0);
            $infants = (int) ($input['kids'] ?? 0);

            if ($adults + $children + $infants > 9) {
                $validator->errors()->add('passengers', 'The total number of passengers must not exceed 9.');
            }
            if ($infants > $adults) {
                $validator->errors()->add('kids', 'Each infant must be accompanied by an adult.');
            }

            $knownAirports = $this->knownAirportCodes();
            $legs = ($input['trip'] ?? null) === 'multi'
                ? ($input['multi_legs'] ?? [])
                : [[
                    'from' => $input['from'] ?? '',
                    'to' => $input['to'] ?? '',
                    'depart' => $input['depart'] ?? '',
                ]];
            $previousDate = null;

            foreach ($legs as $index => $leg) {
                $from = $this->airportCode((string) ($leg['from'] ?? ''));
                $to = $this->airportCode((string) ($leg['to'] ?? ''));
                $prefix = ($input['trip'] ?? null) === 'multi' ? "multi_legs.{$index}." : '';

                if ($from !== '' && $from === $to) {
                    $validator->errors()->add($prefix.'to', 'Origin and destination must be different.');
                }
                if ($from !== '' && ! isset($knownAirports[$from])) {
                    $validator->errors()->add($prefix.'from', 'Select a recognised departure airport.');
                }
                if ($to !== '' && ! isset($knownAirports[$to])) {
                    $validator->errors()->add($prefix.'to', 'Select a recognised destination airport.');
                }

                try {
                    $date = Carbon::createFromFormat('d/m/Y', (string) ($leg['depart'] ?? ''))->startOfDay();
                    if ($previousDate && $date->lt($previousDate)) {
                        $validator->errors()->add($prefix.'depart', 'Multi-city dates must be in travel order.');
                    }
                    $previousDate = $date;
                } catch (\Throwable) {
                    // The date-format rule provides the customer-facing error.
                }
            }
        });

        try {
            $validated = $validator->validate();
        } catch (ValidationException $exception) {
            // Step: validation failed — log the field errors, then let Laravel
            // handle the redirect-back-with-errors as normal.
            $this->logStep('search validation failed', [
                'errors' => $exception->errors(),
            ], 'warning');

            throw $exception;
        }

        $validated['childs'] = (int) ($validated['childs'] ?? 0);
        $validated['kids'] = (int) ($validated['kids'] ?? 0);
        $validated['adults'] = (int) $validated['adults'];

        return $validated;
    }

    private function knownAirportCodes(): array
    {
        static $codes;

        return $codes ??= collect(json_decode(
            file_get_contents(public_path('assets/data/airportsCode.json')),
            true,
        ))->mapWithKeys(fn (array $airport): array => [
            strtoupper((string) ($airport['AirportCode'] ?? '')) => true,
        ])->filter(fn (bool $known, string $code): bool => $code !== '')->all();
    }

    private function airportCode(string $value): string
    {
        return strtoupper(trim(Str::between($value, '(', ')')));
    }

    private function forgetCheckoutSession(): void
    {
        session()->forget([
            'bookingFlight',
            'bookingContact',
            'bookingPassengers',
            'bookingSearchParams',
            'bookingSessionId',
            'bookingUniqueId',
            'bookingRef',
            'bookingStatus',
            'bookingConfirmation',
            'bookingTktTimeLimit',
            'flightBookingDbId',
            'selectedExtras',
            'extraBaggage',
            'extraMeal',
            'extraServices',
            'fareRules',
            'paymentMethod',
            'seerbitPaymentReference',
            'seerbitPaymentFlow',
            'ticketOrderResult',
            'ticketSuccess',
            'travelFlexPlan',
            'travelFlexApplicant',
            'travelFlexDocPaths',
        ]);

        $this->logStep('checkout session cleared', [], 'debug');
    }
}
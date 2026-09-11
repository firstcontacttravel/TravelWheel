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

        // ── Route summary for the log ─────────────────────────────────────────
        // TravelNext's OriginDestinationInfo payload was assembled here.
        // SkylinkFlightService::buildSearchPayload() builds its own request
        // shape straight from $criteria, so all that is worth recording at
        // this point is the route, for tracing one search through the logs.
        $routes = $validated['trip'] === 'multi'
            ? array_map(
                fn (array $leg): string => $this->airportCode((string) ($leg['from'] ?? ''))
                    .'-'.$this->airportCode((string) ($leg['to'] ?? '')),
                $validated['multi_legs'] ?? []
            )
            : [$this->airportCode((string) ($validated['from'] ?? ''))
                .'-'.$this->airportCode((string) ($validated['to'] ?? ''))];

        $this->logStep('route resolved', [
            'legs' => count($routes),
            'routes' => $routes,
        ]);

        // ── Supplier search ───────────────────────────────────────────────────
        // DEMO BRANCH — TravelNext removed. This method previously POSTed to
        // TravelNext's `availability` endpoint and mapped its FareItineraries
        // into the canonical flight array. It now calls SkyLink directly and
        // writes the mapped result to the same session keys, so every step
        // downstream — the results page, select(), book(), the payment gateway
        // and reserve() — runs completely unchanged. See BRANCH-NOTES.md.
        $criteria = $validated;
        $criteria['trip'] = $request->trip;
        if (! empty($request->multi_legs)) {
            $criteria['multi_legs'] = $request->multi_legs;
        }

        $this->logStep('calling SkyLink search');
        $apiStart = microtime(true);

        $result = app(SkylinkFlightService::class)->search($criteria);

        $this->logStep('SkyLink search responded', [
            'duration_ms' => (int) round((microtime(true) - $apiStart) * 1000),
            'error' => $result['error'] ?? true,
            'message' => $result['message'] ?? null,
        ]);

        if ($result['error'] ?? true) {
            $this->logStep('SkyLink search failed — redirecting to search form', [
                'message' => $result['message'] ?? null,
            ], 'warning');

            return redirect()->route('air')->withErrors([
                'error' => $result['message'] ?: 'Flight search failed. Please try again.',
            ]);
        }

        // ── Markup ────────────────────────────────────────────────────────────
        $mapStart = microtime(true);

        $flights = array_values(array_map(
            fn (array $flight): array => FlightMarkup::apply($flight),
            (array) data_get($result, 'data.flights', [])
        ));

        // Every card needs a stable id: the results page keys its x-for on
        // flight.id. TravelNext's mapper supplied it from the itinerary index;
        // SkylinkFlightService::mapSearchResult() does not set one at all, so
        // without this Alpine renders every card against an undefined key.
        foreach ($flights as $index => $flight) {
            $flights[$index]['id'] = $index;
        }

        $this->logStep('mapping complete', [
            'mapped' => count($flights),
            'duration_ms' => (int) round((microtime(true) - $mapStart) * 1000),
        ]);

        // ── Write ONLY to durable session — no flash data needed ─────────────
        // The Livewire FlightPage component reads directly from these session
        // keys in mount(), so data persists across refreshes and back-navigation.
        session()->forget(['pendingFlightSearch', 'pendingFlightSearchStartedAt', 'pendingFlightSearchLogId']);

        session([
            'flightResultsStore' => $flights,
            'searchParamsStore' => $validated,
            // SkyLink has no session-id concept — it is TravelNext's own
            // AirSearchResponse.session_id. select() and book() both accept ''.
            'searchSessionId' => '',
            // _selectSkylinkFare() looks the chosen fare up here by
            // fareSourceCode. On the main branch FlightPage::loadSkylinkResults()
            // writes this key; this branch has no async supplement, so the
            // synchronous search above has to write it instead.
            'skylinkResultsStore' => $flights,
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
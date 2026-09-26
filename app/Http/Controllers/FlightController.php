<?php

namespace App\Http\Controllers;

use App\Services\Flights\FlightSupplierControl;
use App\Support\FlightMarkup;
use App\Support\FlightMatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FlightController extends Controller
{
    /** Shown when no switched-on flight API can take a search. */
    private const UNAVAILABLE = 'Flight search is temporarily unavailable. Please try again later.';

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

        // Every flight API switched off in the admin: say so now, rather than
        // after the loading page has spun for nothing.
        if (app(FlightSupplierControl::class)->enabledKeys() === []) {
            $this->logStep('search refused — every flight API is switched off', [], 'warning');

            return redirect()->route('air')->withErrors(['error' => self::UNAVAILABLE]);
        }

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

        // ── Supplier search, in the admin's priority order ────────────────────
        // The first switched-on API that answers with flights fills the page.
        // One that errors or finds nothing hands over to the next, so a broken
        // or empty API never leaves the customer with nothing while another
        // could have answered. Every API not reached here is searched as a
        // supplement once the results page is up (FlightPage).
        $enabled = app(FlightSupplierControl::class)->enabled();
        $attempted = [];
        $primary = null;
        $meta = [];
        $firstError = null;

        foreach ($enabled as $supplier) {
            $attempted[] = $supplier->key();
            $this->logStep('searching supplier', ['supplier' => $supplier->key()]);

            $result = $supplier->search($validated, [
                'search_id' => $this->searchLogId,
                'started_at' => $this->searchStartedAt,
            ]);

            if ($result['error']) {
                $firstError ??= $result['message'];
                $this->logStep('supplier failed — trying the next one', [
                    'supplier' => $supplier->key(),
                    'message' => $result['message'],
                ], 'warning');

                continue;
            }

            $meta[$supplier->key()] = $result['data']['meta'] ?? [];
            $flights = $result['data']['flights'] ?? [];

            // An empty answer is kept in case nobody does better, but the
            // next API still gets its chance.
            if ($primary === null || $flights !== []) {
                $primary = ['key' => $supplier->key(), 'flights' => $flights];
            }

            if ($flights !== []) {
                break;
            }

            $this->logStep('supplier found no flights — trying the next one', ['supplier' => $supplier->key()]);
        }

        if ($primary === null) {
            $this->logStep('no supplier could answer', ['attempted' => $attempted], 'warning');

            return redirect()->route('air')->withErrors(['error' => $firstError ?? self::UNAVAILABLE]);
        }

        $flights = FlightMatch::tag(array_map(
            fn (array $flight): array => FlightMarkup::apply($flight),
            $primary['flights'],
        ));

        // ── Write ONLY to durable session — no flash data needed ─────────────
        // The Livewire FlightPage component reads directly from these session
        // keys in mount(), so data persists across refreshes and back-navigation.
        session()->forget(['pendingFlightSearch', 'pendingFlightSearchStartedAt', 'pendingFlightSearchLogId', 'supplementResultsStore', 'skylinkResultsStore']);

        session([
            'flightResultsStore' => $flights,
            'searchParamsStore' => $validated,
            // The results page posts this back on select. Only TravelNext has
            // a search session; supplierSearchMeta below keeps each supplier's
            // own, so a TravelNext supplement still has its id when TravelNext
            // wasn't the one that filled the page.
            'searchSessionId' => $meta[$primary['key']]['session_id'] ?? '',
            'supplierSearchMeta' => $meta,
            'searchSupplementSuppliers' => array_values(array_diff(
                array_map(fn ($supplier): string => $supplier->key(), $enabled),
                $attempted,
            )),
        ]);

        // Step: done — results stored, redirecting to results page
        $this->logStep('results stored in session — redirecting to results page', [
            'flights' => count($flights),
            'supplier' => $primary['key'],
            'attempted' => $attempted,
        ]);

        // Plain redirect — no ->with([...]) flash needed
        return redirect()->route('air.flight-s');
    }

    // ─────────────────────────────────────────────────────────────────────────
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
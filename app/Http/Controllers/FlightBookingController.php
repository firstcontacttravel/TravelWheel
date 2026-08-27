<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\AdminTicketingService;
use App\Services\DurableMailService;
use App\Services\SeerbitPaymentService;
use App\Services\TravelFlexApplicationPdfService;
use App\Services\TravelFlexApplicationService;
use App\Services\TravelFlexFlowService;
use App\Services\TravelFlexRiskAssessmentService;
use App\Support\FlightDisplay;
use App\Support\FlightMarkup;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FlightBookingController extends Controller
{
    // =========================================================================
    //  select() — revalidate + extra services + fare rules → store in session
    // =========================================================================
    public function select(Request $request)
    {
        $this->_forgetStaleCheckoutSession();

        $validated = $request->validate([
            'fare_source_code' => 'required|string',
            'session_id' => 'required|string',
            'intent' => 'nullable|in:booking,travelflex',
        ]);

        $checkoutIntent = $validated['intent'] ?? 'booking';

        $payload = [
            'session_id' => $validated['session_id'],
            'fare_source_code' => $validated['fare_source_code'],
        ];

        // ── 1. Revalidate ─────────────────────────────────────────────────────────
        try {
            $revalidateResponse = Http::connectTimeout(10)->timeout(60)
                ->post(config('services.travelnext.base_url').'revalidate', $payload);
        } catch (\Throwable $exception) {
            Log::error('Flight revalidation request failed', [
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Fare revalidation is temporarily unavailable. Please try again shortly.',
            ]);
        }

        if ($revalidateResponse->failed()) {

            return back()->with('error', 'Revalidation failed. Please try again.');

        }

        $revalidateData = $revalidateResponse->json();
        $isValid = data_get($revalidateData, 'AirRevalidateResponse.AirRevalidateResult.IsValid');

        // dd($revalidateResponse->json());

        if (! $isValid) {
            return back()->with('error', 'This fare is no longer available. Please select another flight.');
        }

        $fi = data_get(
            $revalidateData,
            'AirRevalidateResponse.AirRevalidateResult.FareItineraries.FareItinerary',
            []
        );

        if (empty($fi)) {
            return back()->with('error', 'No fare data returned from revalidation.');
        }

        // ── 2. Reference data ─────────────────────────────────────────────────────
        $airlines = collect(json_decode(file_get_contents(public_path('assets/data/airline.json')), true))->keyBy('AirLineCode');
        $airports = collect(json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true))->keyBy('AirportCode');
        $searchParams = session('searchParamsStore', []);
        $tripType = strtolower($searchParams['trip'] ?? 'oneway');

        // ── 3. Segment mapper ─────────────────────────────────────────────────────
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
                        'cabin' => trim((string) ($fs['CabinClassText'] ?? '')) !== ''
                            ? $fs['CabinClassText']
                            : \App\Support\FlightDisplay::cabin(['cabinCode' => $fs['CabinClassCode'] ?? 'Y']),
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

        // ── 4. Helpers ────────────────────────────────────────────────────────────
        $calcLayovers = function (array $segs): array {
            $out = [];
            for ($i = 0; $i < count($segs) - 1; $i++) {
                $mins = \Carbon\Carbon::parse($segs[$i]['arriveDT'])
                    ->diffInMinutes(\Carbon\Carbon::parse($segs[$i + 1]['departDT']));
                $out[] = floor($mins / 60).'h '.($mins % 60).'m';
            }

            return $out;
        };

        $calcLayoverMins = function (array $segs): int {
            $total = 0;
            for ($i = 0; $i < count($segs) - 1; $i++) {
                $total += (int) \Carbon\Carbon::parse($segs[$i]['arriveDT'])
                    ->diffInMinutes(\Carbon\Carbon::parse($segs[$i + 1]['departDT']));
            }

            return $total;
        };

        $fmtMins = fn (int $m): string => floor($m / 60).'h '.($m % 60).'m';

        // ── 5. Map segments by trip type ──────────────────────────────────────────
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

        // ── ONE WAY ───────────────────────────────────────────────────────────────
        if ($tripType === 'oneway') {

            $odo0 = $odos[0]['OriginDestinationOption'] ?? [];
            $segments = $mapSegments($odo0);
            $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins = $totalMins + $calcLayoverMins($segments);

            // ── RETURN ────────────────────────────────────────────────────────────────
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

            // ── MULTI-CITY ────────────────────────────────────────────────────────────
            // The revalidate API returns each leg in its OWN OriginDestinationOptions
            // entry — unlike search() which puts all segments in one flat ODO[0].
            // So we iterate the ODOs directly instead of splitting a flat list.
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
                                            ? \Carbon\Carbon::parse($legFirst['departDT'])->format('D, d M')
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

        // ── 6. Shared shortcuts ───────────────────────────────────────────────────
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
            $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
        }

        $validatingCode = $fi['ValidatingAirlineCode'] ?? '';
        $validatingAirline = $airlines->get($validatingCode);

        // ── 7. Fare breakdown ─────────────────────────────────────────────────────
        // TravelNext's `revalidate` response does not repeat `PenaltyDetails` per
        // passenger type — only `availability` (the search step) does. Fall back to
        // the penalty details already captured at search time so refund/TravelFlex
        // eligibility isn't lost just because revalidate omitted the field.
        //
        // The search-stage breakdown was already run through FlightMarkup::apply()
        // (USD → NGN), and $mappedFlight goes through apply() again below, so the
        // fallback amounts are un-converted back to USD here to avoid double conversion.
        $usdToNgnRate = ExchangeRate::rateFor('USD') ?: 1.0;
        $searchedFareBreakdown = collect(session('flightResultsStore', []))
            ->firstWhere('fareSourceCode', $validated['fare_source_code'])['fareBreakdown'] ?? [];
        $searchedFareBreakdown = collect($searchedFareBreakdown)
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
                'cabinBaggage' => \App\Support\FlightDisplay::cabinBaggageValues($fb['CabinBaggage'] ?? []),
                'taxes' => $fb['PassengerFare']['Taxes'] ?? [],
                'serviceTax' => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                'surcharges' => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
                'changeAllowed' => $penaltyDetails['ChangeAllowed'] ?? $fallback['changeAllowed'] ?? false,
                'changePenalty' => $penaltyDetails['ChangePenaltyAmount'] ?? $fallback['changePenalty'] ?? '0.00',
                'refundAllowed' => $penaltyDetails['RefundAllowed'] ?? $fallback['refundAllowed'] ?? false,
                'refundPenalty' => $penaltyDetails['RefundPenaltyAmount'] ?? $fallback['refundPenalty'] ?? null,
            ];
        })->values()->toArray();

        // ── 8. Assemble mapped flight ─────────────────────────────────────────────
        $mappedFlight = [
            'fareSourceCode' => $fareInfo['FareSourceCode'],
            'airline' => $firstSeg['airline'] ?? '',
            'airlineCode' => $firstSeg['airlineCode'] ?? '',
            'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
            'validatingCode' => $validatingCode,
            'validatingAirline' => $validatingAirline['AirLineName'] ?? $validatingCode,
            'validatingLogo' => $validatingAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
            'cabin' => \App\Support\FlightDisplay::cabin($firstSeg),
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

        $mappedFlight = FlightMarkup::apply($mappedFlight);
        // dd($revalidateData);
        $payload1 = [
            'session_id' => $validated['session_id'],
            'fare_source_code' => $revalidateData['AirRevalidateResponse']['AirRevalidateResult']['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['FareSourceCode'],
        ];

        // ── 9. Fetch extra services & fare rules ──────────────────────────────────
        try {
            $responses = Http::pool(fn (Pool $pool): array => [
                $pool->as('extras')->connectTimeout(10)->timeout(60)
                    ->post(config('services.travelnext.base_url').'extra_services', $payload1),
                $pool->as('rules')->connectTimeout(10)->timeout(60)
                    ->post(config('services.travelnext.base_url').'fare_rules', $payload1),
            ]);
            $extraResponse = $responses['extras'];
            $fareRulesResponse = $responses['rules'];
        } catch (\Throwable $exception) {
            Log::error('Flight ancillary request failed', [
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Fare details are temporarily unavailable. Please try again shortly.',
            ]);
        }

        if ($extraResponse->failed()) {
            return back()->withErrors(['error' => 'Extra services fetch failed.']);
        }

        if ($fareRulesResponse->failed()) {
            return back()->withErrors(['error' => 'Fare rules fetch failed.']);
        }

        // dd($extraResponse->json());
        // ── 10. Store in session & redirect ──────────────────────────────────────
        session([
            'bookingFlight' => $mappedFlight,
            'bookingSessionId' => $validated['session_id'],
            'bookingSearchParams' => $searchParams,
            'extraServices' => $extraResponse->json(),
            'fareRules' => $fareRulesResponse->json(),
            'tripType' => $mappedFlight['directionInd'] ?? 'N/A',
            'bookingIntent' => $checkoutIntent,
        ]);

        return redirect()->route('flights.booking');
    }

    // =========================================================================
    //  booking() — show Livewire booking form
    // =========================================================================
    public function booking()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No flight selected.']);
        }

        return view('livewire.pages.flight.flight-booking');
    }

    // =========================================================================
    //  book() — "Confirm & Pay" clicked on review step
    //  WebFare  → save to session → redirect to gateway page
    //  Public/Private → call book API (hold) → redirect to payment options
    // =========================================================================
    public function book(Request $request)
    {
        $validated = $request->validate([
            'fare_source_code' => 'required|string',
            'session_id' => 'required|string',
            'contact.email' => 'required|email',
            'contact.phone' => 'required|string|min:7',
            'contact.area_code' => 'required|string',
            'contact.country_code' => 'required|string',
            'passengers' => 'required|array|min:1',
            'passengers.*.type' => 'required|in:ADT,CHD,INF',
            'passengers.*.title' => 'required|in:Mr,Mrs,Ms,Miss,Dr,Master',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.gender' => 'required|in:M,F',
            'passengers.*.dob' => 'required|date',
            'passengers.*.nationality' => 'required|string|size:2',
            'passengers.*.passport_no' => 'nullable|string|max:20',
            'passengers.*.passport_issue_country' => 'nullable|string|size:2',
            'passengers.*.passport_issue_date' => 'nullable|date',
            'passengers.*.passport_exp' => 'nullable|date|after:today',
            'passengers.*.frequent_flyer_number' => 'nullable|string|max:30',
            'extra_baggage' => 'nullable|array',
            'extra_meal' => 'nullable|array',
            'intent' => 'nullable|in:booking,travelflex',
        ]);

        $this->_validateBookingPassengers($validated);
        $this->_validatePassengerCountsAgainstSearch($validated['passengers']);

        // Persist passenger + contact to session for downstream payment steps
        session([
            'bookingContact' => $validated['contact'],
            'bookingPassengers' => $validated['passengers'],
            'extraBaggage' => $request->input('extra_baggage', []),
            'extraMeal' => $request->input('extra_meal', []),
        ]);

        // ── Collect selected extra services ────────────────────────────────
        $selectedExtras = $this->_collectSelectedExtras($request);
        session(['selectedExtras' => $selectedExtras]);

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $dbBooking = session('flightBookingDbId')
            ? FlightBooking::find(session('flightBookingDbId'))
            : null;
        if (empty($mappedFlight) && $dbBooking) {
            $mappedFlight = $dbBooking->flight_snapshot ?? [];
        }
        if (empty($mappedFlight)) {
            return redirect()->route('air.flight-s')->withErrors([
                'error' => 'Your selected flight has expired. Please search again.',
            ]);
        }
        $fareType = strtolower($mappedFlight['fareType'] ?? 'public');
        $checkoutIntent = $validated['intent'] ?? session('bookingIntent', 'booking');
        $travelFlexIneligibleReason = null;

        if ($checkoutIntent === 'travelflex') {
            session(['bookingIntent' => 'travelflex']);

            $travelFlexEligibility = $this->_travelFlexEligibility($mappedFlight);
            if (! $travelFlexEligibility['eligible']) {
                $travelFlexIneligibleReason = $travelFlexEligibility['reason'];
                session()->forget('bookingIntent');
            } elseif ($fareType === 'webfare') {
                session(['travelFlexRedirectTarget' => 'plan']);

                return redirect()->route('flights.travelflex.fastcredit');
            }
        }

        // ── WebFare: go to payment FIRST, then book ───────────────────────────
        if ($fareType === 'webfare') {
            return redirect()->route('flights.payment.gateway');
        }

        // ── Public / Private: book now (hold), then collect payment ───────────
        $result = $this->_callBookApi($validated, $request);

        if ($result['error']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        $apiResponse = $result['data'];
        $bookResult = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
        $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uniqueId = $bookResult['UniqueID'] ?? '';
        $tktTimeLimit = $bookResult['TktTimeLimit'] ?? '';

        if (! $success || empty($uniqueId)) {
            $errMsg = $this->_extractApiErrorMessage($bookResult, 'Booking failed. Please try again.');

            return back()->withErrors(['error' => $errMsg]);
        }

        $dbBooking = $this->_persistBooking($mappedFlight, $validated, $apiResponse, [
            'unique_id' => $uniqueId,
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => $tktTimeLimit ?: null,
            'extra_services_snapshot' => session('selectedExtras', []),
        ]);

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId' => $uniqueId,           // API hold/ticket ref — NOT shown as booking ref
            'bookingRef' => $dbBooking->booking_ref, // OUR internal booking reference
            'bookingTktTimeLimit' => $tktTimeLimit,
            'bookingStatus' => $bookResult['Status'] ?? '',
            'flightBookingDbId' => $dbBooking->id,
        ]);

        $this->_sendPendingEmail($dbBooking, 'hold');

        if ($travelFlexIneligibleReason) {
            return redirect()->route('flights.payment.options')
                ->withErrors(['flex_error' => $travelFlexIneligibleReason]);
        }

        if (session('bookingIntent') === 'travelflex') {
            $travelFlexEligibility = $this->_travelFlexEligibility($mappedFlight);
            if ($travelFlexEligibility['eligible']) {
                session(['travelFlexRedirectTarget' => 'plan']);

                return redirect()->route('flights.travelflex.fastcredit');
            }

            session()->forget('bookingIntent');

            return redirect()->route('flights.payment.options')
                ->withErrors(['flex_error' => $travelFlexEligibility['reason']]);
        }

        return redirect()->route('flights.payment.options');
    }

    // =========================================================================
    //  paymentGateway() - WebFare payment page
    // =========================================================================
    public function paymentGateway()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $extraServices = session('extraServices', []);
        $selectedExtras = session('selectedExtras', []);

        // Calculate extras total for display
        $extrasTotal = 0.0;
        if (! empty($selectedExtras)) {
            foreach ($selectedExtras as $category) {
                if (is_array($category)) {
                    foreach ($category as $item) {
                        if (isset($item['line_total'])) {
                            $extrasTotal += (float) $item['line_total'];
                        } elseif (isset($item['unit_price'])) {
                            $extrasTotal += (float) $item['unit_price'];
                        }
                    }
                }
            }
        }

        return view('livewire.pages.flight.flight-payment-gateway', [
            'flight' => $mappedFlight,
            'contact' => session('bookingContact', []),
            'selectedExtras' => $selectedExtras,
            'extraServices' => $extraServices,
            'extrasTotal' => $extrasTotal,
        ]);
    }

    // =========================================================================
    //  processGatewayPayment() — WebFare: simulate payment → call book API
    // =========================================================================
    public function processGatewayPayment(Request $request)
    {
        $contact = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);

        if (empty($contact) || empty($passengers)) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired. Please start over.']);
        }

        return $this->_startSeerbitPayment('webfare_full');
    }

    // =========================================================================
    //  paymentOptions() — Non-LCC: 3-option payment page (booking already on hold)
    // =========================================================================
    public function paymentOptions()
    {
        if (! session()->has('bookingUniqueId')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No booking found.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $bookingRef = session('bookingRef', '');
        $dbBooking = session('flightBookingDbId') ? FlightBooking::find(session('flightBookingDbId')) : null;
        if ($dbBooking && $dbBooking->tkt_time_limit && $dbBooking->tkt_time_limit->isPast()) {
            return redirect()->route('air.flight-s')->withErrors([
                'error' => 'This airline hold has expired. Please search again for a current fare.',
            ]);
        }
        $selectedExtras = session('selectedExtras', []);
        $extrasTotal = $this->_selectedExtrasTotal($selectedExtras);

        return view('livewire.pages.flight.flight-payment-options', [
            'flight' => $mappedFlight,
            'uniqueId' => session('bookingUniqueId'),
            'bookingRef' => $bookingRef,
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'contact' => session('bookingContact', []),
            'passengers' => session('bookingPassengers', []),
            'dbId' => session('flightBookingDbId'),
            'selectedExtras' => $selectedExtras,
            'extrasTotal' => $extrasTotal,
        ]);
    }

    // =========================================================================
    //  resumePaymentOptions() - Restore a held booking from email and reopen payment options
    // =========================================================================
    public function resumePaymentOptions(Request $request, string $bookingRef)
    {
        $booking = FlightBooking::where('booking_ref', $bookingRef)->firstOrFail();

        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if (empty($booking->unique_id)) {
            return redirect()->route('air.flight-s')->withErrors([
                'error' => 'This booking cannot be resumed because no hold reference was found.',
            ]);
        }

        if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
            $this->_primeBookingSession($booking);
            session([
                'paymentMethod' => $booking->payment_method,
                'bookingStatus' => strtoupper((string) $booking->booking_status),
            ]);

            return redirect()->route('flights.confirmation');
        }

        if ($booking->tkt_time_limit && now()->greaterThan($booking->tkt_time_limit)) {
            return redirect()->route('air.flight-s')->withErrors([
                'error' => 'This booking hold has already expired. Please search again for a fresh fare.',
            ]);
        }

        $this->_primeBookingSession($booking);

        return redirect()->route('flights.payment.options');
    }

    // =========================================================================
    //  bankTransferNotify() — User clicks "I have made payment"
    // =========================================================================
    public function bankTransferNotify(Request $request)
    {
        abort_if(config('travelwheel.travelflex_bank_accounts', []) === [], 503, 'Bank transfer is unavailable.');

        $request->validate([
            'payment_reference' => 'required|string|min:3|max:100',
        ]);

        $dbId = session('flightBookingDbId');
        $uniqueId = session('bookingUniqueId');

        if (! $uniqueId) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        if ($dbId && $dbBooking = FlightBooking::find($dbId)) {
            $this->_assertHeldBookingPayable($dbBooking, 0);
            $dbBooking->update([
                'payment_method' => 'bank_transfer',
                'payment_status' => 'awaiting_bank_transfer',
                'bank_transfer_reference' => $request->input('payment_reference'),
                'bank_transfer_notified_at' => now(),
            ]);
            $this->_sendPendingEmail($dbBooking, 'bank_transfer');
            $this->_clearCheckoutSession($dbBooking->fresh(), [
                'paymentMethod' => 'bank_transfer',
            ]);
        }

        session(['paymentMethod' => 'bank_transfer']);

        return redirect()->route('flights.pending');
    }

    // =========================================================================
    //  processTicketPayment() - Gateway payment for a held booking
    // =========================================================================
    public function processTicketPayment(Request $request)
    {
        $uniqueId = session('bookingUniqueId');
        $dbId = session('flightBookingDbId');

        if (! $uniqueId) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $booking = $dbId ? FlightBooking::find($dbId) : null;
        if (! $booking) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Booking record not found.']);
        }

        $this->_assertHeldBookingPayable($booking);

        return $this->_startSeerbitPayment('held_ticket_full');
    }

    public function seerbitCallback(Request $request, SeerbitPaymentService $seerbit)
    {
        $reference = $request->input('paymentReference')
            ?? $request->input('reference')
            ?? $request->input('trxref')
            ?? session('seerbitPaymentReference');

        if (! $reference) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Payment reference missing.']);
        }

        $booking = FlightBooking::where('payment_reference', $reference)->first();
        if (! $booking) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Payment record not found.']);
        }

        $lock = Cache::lock('flight-payment-processing:'.$booking->id, 300);
        if (! $lock->get()) {
            $booking = $booking->fresh();
            $this->_prepareSeerbitStatusSession($booking, $reference);

            if ($this->_seerbitPublicStatus($booking)['state'] === 'complete') {
                return $this->_redirectAfterSeerbitFlow($booking);
            }

            return redirect()->route('payments.seerbit.processing');
        }

        try {
            return $this->_processSeerbitCallback($request, $seerbit, $booking->fresh(), $reference);
        } finally {
            $lock->release();
        }
    }

    public function seerbitProcessing()
    {
        $booking = $this->_seerbitSessionBooking();
        if (! $booking) {
            return redirect()->route('air.flight-s')->withErrors([
                'error' => 'We could not find the payment being processed. Please contact support if you were debited.',
            ]);
        }

        $this->_prepareSeerbitStatusSession($booking, (string) $booking->payment_reference);
        $status = $this->_seerbitPublicStatus($booking);

        if ($status['state'] === 'complete') {
            return $this->_redirectAfterSeerbitFlow($booking);
        }

        return view('livewire.pages.flight.flight-payment-processing', [
            'booking' => $booking,
            'paymentStatus' => $status,
        ]);
    }

    public function seerbitStatus()
    {
        $booking = $this->_seerbitSessionBooking();
        if (! $booking) {
            return response()->json([
                'state' => 'missing',
                'headline' => 'Payment status unavailable',
                'message' => 'We could not find this payment in your current session. Please contact support if you were debited.',
            ], 404);
        }

        $this->_prepareSeerbitStatusSession($booking, (string) $booking->payment_reference);
        $status = $this->_seerbitPublicStatus($booking);

        if ($status['state'] === 'complete') {
            $status['redirect_url'] = $this->_seerbitRedirectUrl($booking);
        }

        return response()
            ->json($status)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function _processSeerbitCallback(
        Request $request,
        SeerbitPaymentService $seerbit,
        FlightBooking $booking,
        string $reference,
    ) {

        if ($booking->payment_verified_at && in_array($booking->booking_status, ['confirmed', 'ticketed', 'ticketing_failed', 'failed'], true)) {
            $this->_sendPaymentReceipt($booking);
            $this->_primeBookingSession($booking);
            session(['paymentMethod' => $booking->payment_method]);

            return $this->_redirectAfterSeerbitFlow($booking);
        }

        try {
            $verification = $seerbit->verifyPayment($reference);
        } catch (\Throwable $exception) {
            Log::error('SeerBit payment verification could not start', [
                'booking_ref' => $booking->booking_ref,
                'payment_reference' => $reference,
                'error' => $exception->getMessage(),
            ]);

            return $this->_redirectAfterSeerbitFailure(
                $booking,
                'We could not confirm the payment yet. Please retry shortly. Your booking remains pending.'
            );
        }
        $expectedAmount = round((float) $booking->payment_amount, 2);
        $verifiedAmount = $verification['amount'] === null ? null : round((float) $verification['amount'], 2);
        $amountMatches = $verifiedAmount !== null && $verifiedAmount >= $expectedAmount;
        $currencyMatches = empty($verification['currency'])
            || strtoupper((string) $verification['currency']) === strtoupper((string) $booking->payment_currency);

        if (! ($verification['query_succeeded'] ?? true)) {
            Log::warning('SeerBit payment verification temporarily unavailable', [
                'booking_ref' => $booking->booking_ref,
                'payment_reference' => $reference,
            ]);

            return $this->_redirectAfterSeerbitFailure(
                $booking,
                'We could not confirm the payment yet. Please retry shortly. Your booking remains pending.'
            );
        }

        if (! $verification['ok'] || ! $amountMatches || ! $currencyMatches) {
            $booking->update([
                'payment_status' => 'failed',
                'payment_gateway_response' => $verification['raw'] ?? [],
            ]);

            Log::warning('SeerBit payment verification rejected', [
                'booking_ref' => $booking->booking_ref,
                'payment_reference' => $reference,
                'expected_amount' => $expectedAmount,
                'verified_amount' => $verifiedAmount,
                'expected_currency' => $booking->payment_currency,
                'verified_currency' => $verification['currency'] ?? null,
            ]);

            return $this->_redirectAfterSeerbitFailure($booking, 'Payment could not be verified. Please contact support if you were debited.');
        }

        $booking->update([
            'payment_verified_at' => now(),
            'payment_charged_amount' => $verifiedAmount,
            'payment_gateway_response' => $verification['raw'] ?? [],
        ]);

        $booking = $booking->fresh();

        $this->_primeBookingSession($booking);
        session([
            'seerbitPaymentReference' => $reference,
            'paymentMethod' => $booking->payment_method,
        ]);

        $response = match ($booking->payment_flow) {
            'webfare_full' => $this->_completeWebfarePayment($booking, $request),
            'held_ticket_full' => $this->_completeHeldTicketPayment($booking),
            'travelflex_down_payment' => $this->_completeTravelFlexPayment($booking, $request),
            'travelflex_fees_payment' => $this->_completeTravelFlexFeesPayment($booking, $request),
            default => redirect()->route('air.flight-s')->withErrors(['error' => 'Unknown payment flow.']),
        };

        $this->_sendPaymentReceipt($booking->fresh());

        return $response;
    }

    public function seerbitWebhook(Request $request, SeerbitPaymentService $seerbit)
    {
        $reference = $request->input('paymentReference')
            ?? $request->input('data.paymentReference')
            ?? $request->input('data.payments.paymentReference')
            ?? $request->input('reference');

        $status = 'received';

        if ($reference && $booking = FlightBooking::where('payment_reference', $reference)->first()) {
            $lock = Cache::lock('flight-payment-processing:'.$booking->id, 300);

            if ($lock->get()) {
                try {
                    $this->_processSeerbitCallback($request, $seerbit, $booking->fresh(), $reference);
                    $status = 'processed';
                } catch (\Throwable $exception) {
                    $status = 'deferred';
                    Log::warning('SeerBit webhook completion deferred', [
                        'booking_id' => $booking->id,
                        'error_type' => $exception::class,
                    ]);
                } finally {
                    $lock->release();
                }
            } else {
                $status = 'processing';
            }
        }

        return response()->json([
            'ackReference' => $request->header('ackReference', $reference ?: Str::uuid()->toString()),
            'status' => $status,
        ]);
    }

    // =========================================================================
    //  travelFlexBankTransfer() — User clicks "I have made payment" on TravelFlex
    // =========================================================================
    public function travelFlexBankTransfer(Request $request)
    {
        abort_if(config('travelwheel.travelflex_bank_accounts', []) === [], 503, 'Bank transfer is unavailable.');

        $request->validate([
            'payment_reference' => 'required|string|min:3|max:100',
        ]);

        $currentPlan = session('travelFlexPlan', []);
        if (empty($currentPlan)) {
            return redirect()->route('flights.travelflex')
                ->withErrors(['error' => 'TravelFlex plan missing. Please choose your repayment plan again.']);
        }

        $application = TravelFlexApplication::with('booking')->findOrFail((int) session('travelFlexApplicationId'));
        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);

        $tfPlan = $this->_normalizeTravelFlexPlan(
            (int) data_get($currentPlan, 'down_percent', 30),
            (string) data_get($currentPlan, 'repayment_plan', ''),
            'bank_transfer',
        );
        session(['travelFlexPlan' => $tfPlan]);

        // Two separate transfers: the deposit (equity) reference comes first, then
        // the admin + insurance fees reference, submitted back-to-back in one visit.
        $feesOwed = round((float) ($tfPlan['administration_fee'] ?? 0) + (float) ($tfPlan['insurance_fee'] ?? 0), 2);
        $isDepositStage = ! in_array($application->deposit_status, ['pending', 'paid'], true);
        $reference = (string) $request->input('payment_reference');

        $application->update($isDepositStage
            ? ['deposit_status' => 'pending', 'deposit_reference' => $reference]
            : ['fees_status' => 'pending', 'fees_reference' => $reference]);

        // ── Update DB record if it exists (from the hold booking) ─────────────
        $dbId = session('flightBookingDbId');
        if ($dbId && $dbBooking = \App\Models\FlightBooking::find($dbId)) {
            $dbBooking->update([
                'payment_method' => 'flex_bank_transfer',
                'payment_status' => 'awaiting_bank_transfer',
                'bank_transfer_reference' => $reference,
                'bank_transfer_notified_at' => now(),
                'extra_services_snapshot' => session('selectedExtras', []),
            ]);
            $this->_syncTravelFlexApplicationBooking($dbBooking);
            if ($isDepositStage) {
                $this->_sendPendingEmail($dbBooking, 'bank_transfer');
            }
        } else {
            // No existing DB record yet (booking hasn't been called yet for non-LCC)
            // Persist a new one
            $bookingFlight = session('bookingFlight', []);
            $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
            $contact = session('bookingContact', []);
            $passengers = session('bookingPassengers', []);

            $dbBooking = $this->_persistBooking($mappedFlight, [
                'contact' => $contact,
                'passengers' => $passengers,
            ], [], [
                'unique_id' => session('bookingUniqueId', ''),
                'booking_status' => 'on_hold',
                'payment_method' => 'flex_bank_transfer',
                'payment_status' => 'awaiting_bank_transfer',
                'bank_transfer_reference' => $reference,
                'bank_transfer_notified_at' => now(),
                'tkt_time_limit' => session('bookingTktTimeLimit'),
                'extra_services_snapshot' => session('selectedExtras', []),
            ]);

            session(['flightBookingDbId' => $dbBooking->id, 'bookingRef' => $dbBooking->booking_ref]);
            $this->_syncTravelFlexApplicationBooking($dbBooking);
            $this->_sendPendingEmail($dbBooking, 'bank_transfer');
        }

        session(['paymentMethod' => 'flex_bank_transfer']);

        if ($isDepositStage && $feesOwed > 0) {
            return redirect()->route('flights.travelflex.bank-transfer-form');
        }

        return redirect()->route('flights.travelflex.pending');
    }

    // =========================================================================
    //  travelFlexGateway() — Simulate payment → book API → TravelFlex confirmation
    // =========================================================================
    public function travelFlexGateway(Request $request)
    {
        $request->validate([
            'down_percent' => 'required|integer|between:30,90',
            'repayment_plan' => 'required|string',
        ]);

        $tfPlan = $this->_normalizeTravelFlexPlan(
            (int) $request->input('down_percent'),
            (string) $request->input('repayment_plan'),
            'gateway',
        );

        session(['travelFlexPlan' => $tfPlan]);

        $contact = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);

        if (empty($contact) || empty($passengers)) {
            return redirect()->route('flights.travelflex')->withErrors(['error' => 'Session expired. Please start over.']);
        }

        $validatedData = [
            'fare_source_code' => session('bookingFlight.flight.fareSourceCode', session('bookingFlight.fareSourceCode', '')),
            'session_id' => session('bookingSessionId', ''),
            'contact' => $contact,
            'passengers' => $passengers,
        ];

        // ── Check if already booked on hold (Public/Private) ─────────────────
        $existingUniqueId = session('bookingUniqueId', '');
        $dbId = session('flightBookingDbId');

        if ($existingUniqueId) {
            // Already on hold — call ticket_order instead of book
            $dbBooking = $dbId ? FlightBooking::find($dbId) : null;
            $ticketResult = $dbBooking
                ? app(AdminTicketingService::class)->ticketOrder($dbBooking)
                : ['ok' => false, 'message' => 'Booking record not found.', 'response' => []];
            $ticketResponse = $ticketResult['response'] ?? [];
            $ticketSuccess = (bool) ($ticketResult['ok'] ?? false);

            if ($dbBooking) {
                $dbBooking->update([
                    'payment_method' => 'flex_gateway',
                    'payment_status' => 'paid',
                ]);
                if ($ticketSuccess) {
                    $this->_sendConfirmedEmail($dbBooking);
                } else {
                    $this->_sendTicketingFailureAlert($dbBooking->fresh(), $ticketResult['message'] ?: $this->_extractTicketOrderErrorMessage($ticketResponse), $ticketResponse);
                }
            }

            if (! $ticketSuccess) {
                return redirect()->route('flights.travelflex.confirmation')->withErrors([
                    'error' => 'Payment received, but ticket issuance needs manual processing. Our team has been notified.',
                ]);
            }

        } else {
            // WebFare — call book API now
            $result = $this->_callBookApi($validatedData, $request);

            if ($result['error']) {
                return redirect()->route('flights.travelflex')->withErrors(['error' => $result['message']]);
            }

            $apiResponse = $result['data'];
            $bookResult = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
            $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $uniqueId = $bookResult['UniqueID'] ?? '';

            if (! $success || empty($uniqueId)) {
                $errMsg = $this->_extractApiErrorMessage($bookResult, 'Booking failed. Please contact support.');

                return redirect()->route('flights.travelflex')->withErrors(['error' => $errMsg]);
            }

            $bookingFlight = session('bookingFlight', []);
            $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;

            $dbBooking = $this->_persistBooking($mappedFlight, $validatedData, $apiResponse, [
                'unique_id' => $uniqueId,
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'flex_gateway',
                'extra_services_snapshot' => session('selectedExtras', []),
            ]);

            $this->_sendConfirmedEmail($dbBooking);

            session([
                'bookingConfirmation' => $apiResponse,
                'bookingUniqueId' => $uniqueId,           // API ref
                'bookingRef' => $dbBooking->booking_ref, // OUR ref
                'bookingStatus' => $bookResult['Status'] ?? 'CONFIRMED',
                'flightBookingDbId' => $dbBooking->id,
            ]);
        }

        session(['paymentMethod' => 'flex_gateway']);

        return redirect()->route('flights.travelflex.confirmation');
    }

    // =========================================================================
    //  travelFlexPending() — Bank transfer pending page (TravelFlex version)
    // =========================================================================
    public function travelFlexPending()
    {
        if (! session()->has('travelFlexPlan')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No TravelFlex plan found.']);
        }

        $dbId = session('flightBookingDbId');
        $dbBooking = $dbId ? \App\Models\FlightBooking::find($dbId) : null;
        $application = session('travelFlexApplicationId')
            ? TravelFlexApplication::find(session('travelFlexApplicationId'))
            : null;

        return view('livewire.pages.flight.flight-travelflex-pending', [
            'bookingRef' => session('bookingRef', $dbBooking?->booking_ref ?? ''),
            'application' => $application,
            'dbBooking' => $dbBooking,
        ]);
    }

    // =========================================================================
    //  travelFlexConfirmation() — Gateway paid + booked confirmation (TravelFlex)
    // =========================================================================
    public function travelFlexConfirmation()
    {
        if (! session()->has('travelFlexPlan')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No TravelFlex plan found.']);
        }

        $uniqueId = session('bookingUniqueId', '');
        $dbId = session('flightBookingDbId');
        $dbBooking = $dbId ? \App\Models\FlightBooking::find($dbId) : null;

        // ── Fetch live trip details after successful gateway payment ──────────
        $tripDetails = [];
        if ($uniqueId) {
            $tripDetails = $this->_callTripDetailsApi($uniqueId);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;

        return view('livewire.pages.flight.flight-travelflex-confirmation', [
            'flight' => $mappedFlight,
            'dbBooking' => $dbBooking,
            'tripDetails' => $tripDetails,   // ← live API data
            'uniqueId' => $uniqueId ?: $dbBooking?->unique_id,      // API e-ticket ref
            'bookingRef' => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'contact' => session('bookingContact', ['email' => $dbBooking?->contact_email, 'phone' => $dbBooking?->contact_phone]),
            'passengers' => session('bookingPassengers', $dbBooking?->passengers_snapshot ?? []),
        ]);
    }

    // =========================================================================
    //  pending() — Bank transfer: awaiting manual confirmation
    // =========================================================================
    public function pending()
    {
        $dbId = session('flightBookingDbId');
        $dbBooking = $dbId ? FlightBooking::find($dbId) : null;

        if (! $dbBooking && ! session()->has('bookingUniqueId')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No booking found.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;

        return view('livewire.pages.flight.flight-pending', [
            'flight' => $mappedFlight,
            'dbBooking' => $dbBooking,
            'uniqueId' => session('bookingUniqueId'),     // API hold ref
            'bookingRef' => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'contact' => session('bookingContact', []),
            'passengers' => session('bookingPassengers', []),
        ]);
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    private function _startSeerbitPayment(string $flow)
    {
        $paymentScope = session('flightBookingDbId')
            ? 'booking:'.session('flightBookingDbId')
            : 'session:'.session()->getId();
        $lock = Cache::lock('flight-payment-initialize:'.sha1($paymentScope.':'.$flow), 45);

        if (! $lock->get()) {
            return back()->withErrors([
                'error' => 'Payment setup is already in progress. Please wait a moment and try again.',
            ]);
        }

        try {
            $booking = $this->_prepareSeerbitBooking($flow);
            $amount = $this->_paymentAmountForFlow($flow, $booking);
            $currency = $booking->currency ?: 'NGN';
            $existingRedirect = $this->_seerbitRedirectLink($booking->payment_gateway_response ?? []);
            $samePendingPayment = $booking->payment_status === 'pending'
                && $booking->payment_flow === $flow
                && filled($booking->payment_reference)
                && round((float) $booking->payment_amount, 2) === round($amount, 2)
                && strtoupper((string) $booking->payment_currency) === strtoupper($currency);

            if ($samePendingPayment && $existingRedirect) {
                session([
                    'flightBookingDbId' => $booking->id,
                    'bookingRef' => $booking->booking_ref,
                    'seerbitPaymentReference' => $booking->payment_reference,
                    'seerbitPaymentFlow' => $flow,
                ]);

                return redirect()->away($existingRedirect);
            }

            $reference = $samePendingPayment
                ? (string) $booking->payment_reference
                : $this->_generatePaymentReference();

            $booking->update([
                'payment_reference' => $reference,
                'payment_gateway' => 'seerbit',
                'payment_flow' => $flow,
                'payment_amount' => $amount,
                'payment_currency' => $currency,
                'payment_method' => in_array($flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true) ? 'flex_gateway' : 'gateway',
                'payment_status' => 'pending',
                'payment_initializing_at' => now(),
            ]);

            session([
                'flightBookingDbId' => $booking->id,
                'bookingRef' => $booking->booking_ref,
                'seerbitPaymentReference' => $reference,
                'seerbitPaymentFlow' => $flow,
            ]);

            $contact = session('bookingContact', []);
            $passengers = session('bookingPassengers', []);
            $lead = $passengers[0] ?? [];
            $fullName = trim(($lead['first_name'] ?? '').' '.($lead['last_name'] ?? '')) ?: ($contact['email'] ?? 'Travelwheel Customer');

            $seerbit = app(SeerbitPaymentService::class);
            $checkout = $seerbit->initializePayment([
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'paymentReference' => $reference,
                'email' => $contact['email'] ?? $booking->contact_email,
                'fullName' => $fullName,
                'mobileNumber' => $contact['phone'] ?? $booking->contact_phone,
                'callbackUrl' => route('payments.seerbit.callback', ['paymentReference' => $reference]),
                'productDescription' => $this->_paymentDescription($flow, $booking),
            ]);

            $booking->update([
                'payment_gateway_response' => $checkout['raw'],
                'payment_initializing_at' => null,
            ]);

            return redirect()->away($checkout['redirect_link']);
        } catch (\Throwable $e) {
            Log::error('Unable to start SeerBit payment', [
                'flow' => $flow,
                'error' => $e->getMessage(),
            ]);

            return $this->_redirectAfterSeerbitFailure(
                isset($booking) ? $booking : null,
                $e->getMessage() ?: 'Unable to start payment. Please try again.'
            );
        } finally {
            $lock->release();
        }
    }

    private function _seerbitRedirectLink(array $response): ?string
    {
        return data_get($response, 'data.payments.redirectLink')
            ?? data_get($response, 'data.redirectLink')
            ?? data_get($response, 'payments.redirectLink')
            ?? data_get($response, 'redirect_link');
    }

    private function _prepareSeerbitBooking(string $flow): FlightBooking
    {
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $contact = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);
        $validatedData = [
            'fare_source_code' => session('bookingFlight.flight.fareSourceCode', session('bookingFlight.fareSourceCode', '')),
            'session_id' => session('bookingSessionId', ''),
            'contact' => $contact,
            'passengers' => $passengers,
        ];

        $dbId = session('flightBookingDbId');
        $booking = $dbId ? FlightBooking::find($dbId) : null;

        if (! $booking) {
            $booking = $this->_persistBooking($mappedFlight, $validatedData, [], [
                'unique_id' => session('bookingUniqueId', ''),
                'booking_status' => 'pending_payment',
                'payment_status' => 'pending',
                'payment_method' => in_array($flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true) ? 'flex_gateway' : 'gateway',
                'extra_services_snapshot' => session('selectedExtras', []),
            ]);
        } else {
            if ($flow === 'held_ticket_full') {
                $this->_assertHeldBookingPayable($booking);
            }
            $booking->update([
                'payment_method' => in_array($flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true) ? 'flex_gateway' : 'gateway',
                'extra_services_snapshot' => session('selectedExtras', $booking->extra_services_snapshot ?? []),
            ]);
        }

        if (in_array($flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true)) {
            $this->_syncTravelFlexApplicationBooking($booking);
        }

        return $booking->fresh();
    }

    private function _assertHeldBookingPayable(FlightBooking $booking, int $minimumMinutes = 10): void
    {
        if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
            throw ValidationException::withMessages([
                'payment' => 'This booking has already been ticketed.',
            ]);
        }

        if (! $booking->unique_id) {
            throw ValidationException::withMessages([
                'payment' => 'The airline hold reference is missing. Please restart checkout.',
            ]);
        }

        if ($booking->tkt_time_limit && $booking->tkt_time_limit->lte(now()->addMinutes($minimumMinutes))) {
            throw ValidationException::withMessages([
                'payment' => 'The airline hold no longer leaves enough time to verify payment and issue the ticket. Please search again.',
            ]);
        }
    }

    private function _completeWebfarePayment(FlightBooking $booking, Request $request)
    {
        $validatedData = [
            'fare_source_code' => $booking->fare_source_code,
            'session_id' => $booking->session_id,
            'contact' => session('bookingContact', []),
            'passengers' => session('bookingPassengers', []),
        ];

        $result = $this->_callBookApi($validatedData, $request);
        if ($result['error']) {
            $booking->update(['payment_status' => 'paid', 'booking_status' => 'failed']);

            return redirect()->route('flights.payment.gateway')->withErrors(['error' => $result['message']]);
        }

        $apiResponse = $result['data'];
        $bookResult = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
        $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uniqueId = $bookResult['UniqueID'] ?? '';

        if (! $success || empty($uniqueId)) {
            $errMsg = $this->_extractApiErrorMessage($bookResult, 'Booking failed after payment. Please contact support.');
            $booking->update(['payment_status' => 'paid', 'booking_status' => 'failed', 'booking_api_response' => $apiResponse]);

            return redirect()->route('flights.payment.gateway')->withErrors(['error' => $errMsg]);
        }

        $booking->update([
            'unique_id' => $uniqueId,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'gateway',
            'booking_api_response' => $apiResponse,
        ]);

        $this->_sendConfirmedEmail($booking->fresh());

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId' => $uniqueId,
            'bookingRef' => $booking->booking_ref,
            'bookingStatus' => $bookResult['Status'] ?? 'CONFIRMED',
            'flightBookingDbId' => $booking->id,
            'paymentMethod' => 'gateway',
        ]);

        $this->_clearCheckoutSession($booking->fresh(), [
            'bookingStatus' => $bookResult['Status'] ?? 'CONFIRMED',
        ]);

        return redirect()->route('flights.confirmation');
    }

    private function _completeHeldTicketPayment(FlightBooking $booking)
    {
        if (! $booking->unique_id) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Booking hold reference missing.']);
        }

        $ticketResult = app(AdminTicketingService::class)->ticketOrder($booking);
        $ticketResponse = $ticketResult['response'] ?? [];
        $ticketSuccess = (bool) ($ticketResult['ok'] ?? false);

        $booking->update([
            'payment_method' => 'gateway',
            'payment_status' => 'paid',
        ]);

        if ($ticketSuccess) {
            $this->_sendConfirmedEmail($booking->fresh());
        } else {
            $this->_sendTicketingFailureAlert($booking->fresh(), $ticketResult['message'] ?: $this->_extractTicketOrderErrorMessage($ticketResponse), $ticketResponse);
        }

        session([
            'ticketOrderResult' => $ticketResponse,
            'ticketSuccess' => $ticketSuccess,
            'paymentMethod' => 'gateway',
            'bookingUniqueId' => $ticketSuccess
                ? (data_get($ticketResponse, 'AirOrderTicketRS.TicketOrderResult.UniqueID', $booking->unique_id) ?: $booking->unique_id)
                : $booking->unique_id,
        ]);

        if (! $ticketSuccess) {
            $this->_clearCheckoutSession($booking->fresh(), [
                'ticketOrderResult' => $ticketResponse,
                'ticketSuccess' => false,
                'bookingStatus' => 'TICKETING_FAILED',
            ]);

            return redirect()->route('flights.confirmation')->withErrors([
                'error' => 'Payment received, but ticket issuance needs manual processing. Our team has been notified.',
            ]);
        }

        $this->_clearCheckoutSession($booking->fresh(), [
            'ticketOrderResult' => $ticketResponse,
            'ticketSuccess' => true,
        ]);

        return redirect()->route('flights.confirmation');
    }

    // Leg 1 of 2: the equity/down payment. Clearing this does NOT ticket the booking —
    // it immediately starts the second SeerBit charge for the admin + insurance fees.
    // Ticketing only happens once _completeTravelFlexFeesPayment() sees both legs paid.
    private function _completeTravelFlexPayment(FlightBooking $booking, Request $request)
    {
        $tfPlan = session('travelFlexPlan', []);

        if (empty($tfPlan)) {
            return redirect()->route('flights.travelflex')->withErrors(['error' => 'TravelFlex plan missing.']);
        }

        $application = app(TravelFlexFlowService::class)->applicationForBooking($booking);
        if (! $application) {
            return redirect()->route('flights.travelflex.pending')->withErrors(['error' => 'Payment was received, but the TravelFlex application could not be matched. Ticketing has been stopped for manual review.']);
        }

        if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
            return redirect()->route('flights.travelflex.confirmation');
        }

        if ($application->deposit_status === 'paid') {
            // Duplicate/retried callback for a deposit already recorded — resume at the fees leg.
            return $application->fees_status === 'paid'
                ? redirect()->route('flights.travelflex.confirmation')
                : $this->_startSeerbitPayment('travelflex_fees_payment');
        }

        try {
            app(TravelFlexFlowService::class)->assertApprovedForDeposit($application->load('booking'));
        } catch (ValidationException $exception) {
            $application->update([
                'deposit_status' => 'refund_pending',
                'payment_status' => 'paid',
                'deposit_reference' => $booking->payment_reference,
                'deposit_paid_at' => now(),
            ]);
            $booking->update(['payment_status' => 'partially_paid']);

            return redirect()->route('flights.travelflex.pending')->withErrors([
                'error' => 'The down payment was received, but ticketing was stopped because the approval or airline hold is no longer valid. TravelWheel will review the payment immediately.',
            ]);
        }

        $application->update([
            'deposit_status' => 'paid',
            'payment_status' => 'partially_paid',
            'deposit_reference' => $booking->payment_reference,
            'deposit_paid_at' => now(),
        ]);

        $booking->update([
            'payment_method' => 'flex_gateway',
            'payment_status' => 'partially_paid',
        ]);

        $this->_syncTravelFlexApplicationBooking($booking->fresh(), [
            'deposit_status' => 'paid',
            'payment_status' => 'partially_paid',
            'deposit_reference' => $booking->payment_reference,
            'deposit_paid_at' => now(),
        ]);

        session(['paymentMethod' => 'flex_gateway', 'travelFlexPlan' => $tfPlan]);

        // Same-visit, back-to-back: immediately send the customer to pay the
        // administration + insurance fees before anything gets ticketed.
        return $this->_startSeerbitPayment('travelflex_fees_payment');
    }

    // Leg 2 of 2: administration + insurance fees. Only once this clears (with the
    // deposit already paid) does the booking actually get ticketed.
    private function _completeTravelFlexFeesPayment(FlightBooking $booking, Request $request)
    {
        $tfPlan = session('travelFlexPlan', []);

        if (empty($tfPlan)) {
            return redirect()->route('flights.travelflex')->withErrors(['error' => 'TravelFlex plan missing.']);
        }

        $application = app(TravelFlexFlowService::class)->applicationForBooking($booking);
        if (! $application) {
            return redirect()->route('flights.travelflex.pending')->withErrors(['error' => 'Payment was received, but the TravelFlex application could not be matched. Ticketing has been stopped for manual review.']);
        }

        if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
            return redirect()->route('flights.travelflex.confirmation');
        }

        if ($application->fees_status !== 'paid') {
            $application->update([
                'fees_status' => 'paid',
                'fees_reference' => $booking->payment_reference,
                'fees_paid_at' => now(),
            ]);
        }

        if ($application->fresh()->deposit_status !== 'paid') {
            // Fees cleared before the deposit was recorded — hold ticketing until both legs are confirmed.
            return redirect()->route('flights.travelflex.pending')->withErrors([
                'error' => 'The administration and insurance fees were received, but the down payment is still outstanding. Please complete the down payment to continue.',
            ]);
        }

        try {
            app(TravelFlexFlowService::class)->assertApprovedForDeposit($application->load('booking'));
        } catch (ValidationException $exception) {
            return redirect()->route('flights.travelflex.pending')->withErrors([
                'error' => 'Both payments were received, but ticketing was stopped because the approval or airline hold is no longer valid. TravelWheel will review the payment immediately.',
            ]);
        }

        if ($booking->unique_id) {
            // Both legs are paid. Ticketing is no longer automatic here — an admin
            // must trigger "Order ticket" in Filament (same as the bank transfer
            // path) once they've reviewed the booking.
            $booking->update([
                'payment_method' => 'flex_gateway',
                'payment_status' => 'paid',
                'booking_status' => 'awaiting_ticketing',
            ]);
        } else {
            if ($message = $this->_completeTravelFlexWebfareBooking($booking, $request)) {
                return redirect()->route('flights.travelflex')->withErrors(['error' => $message]);
            }
        }

        $application = $application->fresh();
        $this->_syncTravelFlexApplicationBooking($booking->fresh(), [
            'deposit_status' => 'paid',
            'fees_status' => 'paid',
            'payment_status' => 'paid',
            'deposit_reference' => $application->deposit_reference,
            'deposit_paid_at' => $application->deposit_paid_at,
        ]);

        session(['paymentMethod' => 'flex_gateway']);

        $this->_clearCheckoutSession($booking->fresh(), [
            'ticketSuccess' => false,
            'travelFlexPlan' => $tfPlan,
        ]);

        return redirect()->route('flights.travelflex.confirmation');
    }

    private function _completeTravelFlexWebfareBooking(FlightBooking $booking, Request $request): ?string
    {
        $validatedData = [
            'fare_source_code' => $booking->fare_source_code,
            'session_id' => $booking->session_id,
            'contact' => session('bookingContact', []),
            'passengers' => session('bookingPassengers', []),
        ];

        $result = $this->_callBookApi($validatedData, $request);
        if ($result['error']) {
            $booking->update(['payment_status' => 'paid', 'booking_status' => 'failed']);

            return $result['message'];
        }

        $apiResponse = $result['data'];
        $bookResult = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
        $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uniqueId = $bookResult['UniqueID'] ?? '';

        if (! $success || empty($uniqueId)) {
            $booking->update(['payment_status' => 'paid', 'booking_status' => 'failed', 'booking_api_response' => $apiResponse]);

            return $this->_extractApiErrorMessage($bookResult, 'Booking failed after payment. Please contact support.');
        }

        $booking->update([
            'unique_id' => $uniqueId,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'flex_gateway',
            'booking_api_response' => $apiResponse,
        ]);

        $this->_sendConfirmedEmail($booking->fresh());

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId' => $uniqueId,
            'bookingRef' => $booking->booking_ref,
            'bookingStatus' => $bookResult['Status'] ?? 'CONFIRMED',
            'flightBookingDbId' => $booking->id,
        ]);

        return null;
    }

    private function _paymentAmountForFlow(string $flow, FlightBooking $booking): float
    {
        if (in_array($flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true)) {
            $tfPlan = session('travelFlexPlan', []);
            $tfPlan = $this->_normalizeTravelFlexPlan(
                (int) data_get($tfPlan, 'down_percent', 30),
                (string) data_get($tfPlan, 'repayment_plan', '1 month'),
                (string) data_get($tfPlan, 'payment_method', 'gateway'),
            );
            session(['travelFlexPlan' => $tfPlan]);

            if ($flow === 'travelflex_fees_payment') {
                return round(
                    (float) data_get($tfPlan, 'administration_fee', 0) + (float) data_get($tfPlan, 'insurance_fee', 0),
                    2,
                );
            }

            return round((float) data_get($tfPlan, 'down_payment', 0), 2);
        }

        return $this->_fullPayableAmount($booking);
    }

    private function _fullPayableAmount(FlightBooking $booking): float
    {
        $flight = $booking->flight_snapshot ?? session('bookingFlight.flight', session('bookingFlight', []));
        $extras = $booking->extra_services_snapshot ?? session('selectedExtras', []);

        return round(((float) ($flight['price'] ?? $booking->total_price ?? 0)) + $this->_selectedExtrasTotal($extras), 2);
    }

    private function _selectedExtrasTotal(array $selectedExtras = []): float
    {
        $total = 0.0;

        foreach ($selectedExtras as $category) {
            if (! is_array($category)) {
                continue;
            }

            foreach ($category as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (isset($item['line_total'])) {
                    $total += (float) $item['line_total'];

                    continue;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $total += ((float) ($item['unit_price'] ?? 0)) * $quantity;
            }
        }

        return round($total, 2);
    }

    private function _normalizeTravelFlexPlan(int $downPercent, string $repaymentPlan, string $paymentMethod): array
    {
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $eligibility = $this->_travelFlexEligibility($mappedFlight);

        if (! $eligibility['eligible']) {
            throw ValidationException::withMessages([
                'travelflex' => $eligibility['reason'],
            ]);
        }

        $riskAssessment = app(TravelFlexRiskAssessmentService::class)->assess(
            $mappedFlight,
            session('selectedExtras', []),
        );
        $minimumDownPercent = (int) $riskAssessment['minimum_down_percent'];
        $maximumDownPercent = (int) $riskAssessment['maximum_down_percent'];

        if ($downPercent < $minimumDownPercent) {
            throw ValidationException::withMessages([
                'down_percent' => "This fare requires a minimum {$minimumDownPercent}% down payment to cover its estimated cancellation cost.",
            ]);
        }

        $downPercent = min($maximumDownPercent, $downPercent);
        $repaymentPlan = trim($repaymentPlan);
        $paymentMethod = in_array($paymentMethod, ['bank_transfer', 'gateway'], true) ? $paymentMethod : 'gateway';
        $ticketCost = round(((float) ($mappedFlight['price'] ?? 0)) + $this->_selectedExtrasTotal(session('selectedExtras', [])), 2);

        if ($ticketCost <= 0) {
            throw ValidationException::withMessages([
                'travelflex' => 'Unable to calculate the TravelFlex ticket cost. Please restart checkout.',
            ]);
        }

        $parsed = $this->_parseTravelFlexRepaymentPlan($repaymentPlan);
        $departureDate = $this->_travelFlexDepartureDate($mappedFlight);
        $daysToDepart = $departureDate ? Carbon::today()->diffInDays($departureDate->copy()->startOfDay(), false) : null;
        $safeDays = $daysToDepart === null ? null : max(0, $daysToDepart - 14);

        $repaymentDurationDays = $parsed['unit_days'] * $parsed['count'];

        if ($safeDays !== null && $repaymentDurationDays > $safeDays) {
            throw ValidationException::withMessages([
                'repayment_plan' => 'Selected repayment plan does not fit within the TravelFlex eligibility window.',
            ]);
        }

        $downPayment = round($ticketCost * ($downPercent / 100), 2);
        $remainingBalance = round($ticketCost - $downPayment, 2);
        $rate = (float) config('travelwheel.travelflex_interest_rate', 0.04);
        $administrationFeeRate = (float) config('travelwheel.travelflex_administration_fee_rate', 0.01);
        $insuranceFeeRate = (float) config('travelwheel.travelflex_insurance_fee_rate', 0.015);
        $administrationFee = round($remainingBalance * $administrationFeeRate, 2);
        $insuranceFee = round($remainingBalance * $insuranceFeeRate, 2);
        $upfrontFeeTotal = round($administrationFee + $insuranceFee, 2);
        $upfrontPaymentTotal = round($downPayment + $upfrontFeeTotal, 2);
        $instalmentPlanCount = max(1, min(12, $parsed['count']));
        $proportions = [
            1 => [1.0],
            2 => [0.5, 0.5],
            3 => [0.4, 0.3, 0.3],
            4 => [0.25, 0.25, 0.25, 0.25],
            5 => [0.2, 0.2, 0.2, 0.2, 0.2],
        ][$instalmentPlanCount] ?? array_fill(0, $instalmentPlanCount, 1 / $instalmentPlanCount);

        $dueDate = Carbon::today()->addDays($parsed['unit_days']);
        $ordinals = ['1st', '2nd', '3rd', '4th', '5th'];
        $totalInterest = 0.0;
        $principalAllocated = 0.0;
        $schedule = [];
        $instalmentCount = count($proportions);

        foreach ($proportions as $index => $portion) {
            if ($index > 0) {
                $dueDate->addDays($parsed['unit_days']);
            }

            $interest = round($remainingBalance * $rate, 2);
            $isLastInstalment = $index === $instalmentCount - 1;
            $principal = $isLastInstalment
                ? round($remainingBalance - $principalAllocated, 2)
                : round($remainingBalance * $portion, 2);
            $principalAllocated = round($principalAllocated + $principal, 2);
            $total = round($principal + $interest, 2);
            $totalInterest = round($totalInterest + $interest, 2);

            $schedule[] = [
                'label' => ($ordinals[$index] ?? (($index + 1).'th')).' Payment',
                'dueDate' => $dueDate->toFormattedDateString(),
                'due_date' => $dueDate->toDateString(),
                'principal' => $principal,
                'interest' => $interest,
                'total' => $total,
            ];
        }

        return [
            'ticket_cost' => $ticketCost,
            'down_payment' => $downPayment,
            'administration_fee' => $administrationFee,
            'administration_fee_rate' => $administrationFeeRate,
            'administration_fee_percent' => round($administrationFeeRate * 100, 2),
            'insurance_fee' => $insuranceFee,
            'insurance_fee_rate' => $insuranceFeeRate,
            'insurance_fee_percent' => round($insuranceFeeRate * 100, 2),
            'upfront_fee_total' => $upfrontFeeTotal,
            'upfront_payment_total' => $upfrontPaymentTotal,
            'down_percent' => $downPercent,
            'minimum_down_percent' => $minimumDownPercent,
            'minimum_down_payment' => $riskAssessment['minimum_down_payment'],
            'loan_amount' => $remainingBalance,
            'remaining_balance' => $remainingBalance,
            'repayment_plan' => $repaymentPlan,
            'repayment_interval_days' => $parsed['unit_days'],
            'repayment_duration_days' => $repaymentDurationDays,
            'repayment_count' => count($schedule),
            'grand_total' => round($ticketCost + $totalInterest + $upfrontFeeTotal, 2),
            'total_interest' => $totalInterest,
            'interest_rate' => $rate,
            'interest_rate_percent' => round($rate * 100, 2),
            'schedule' => $schedule,
            'payment_method' => $paymentMethod,
            'risk_assessment' => $riskAssessment,
            'normalized_at' => now()->toIso8601String(),
        ];
    }

    private function _parseTravelFlexRepaymentPlan(string $label): array
    {
        $normalized = strtolower(trim($label));

        if (preg_match('/(\d+)\s*month/', $normalized, $matches)) {
            $count = max(1, (int) $matches[1]);

            if ($count > 12) {
                throw ValidationException::withMessages([
                    'repayment_plan' => 'TravelFlex repayment plans are limited to a maximum of 12 months.',
                ]);
            }

            return ['count' => $count, 'unit_days' => 30];
        }

        if (preg_match('/(\d+)\s*week/', $normalized, $matches)) {
            return ['count' => max(1, (int) $matches[1]), 'unit_days' => 7];
        }

        if (preg_match('/(\d+)\s*hour/', $normalized, $matches)) {
            return ['count' => 1, 'unit_days' => max(1, (int) ceil(((int) $matches[1]) / 24))];
        }

        if (str_contains($normalized, 'month')) {
            return ['count' => 1, 'unit_days' => 30];
        }

        if (str_contains($normalized, 'week')) {
            return ['count' => 1, 'unit_days' => 7];
        }

        if (str_contains($normalized, 'hour')) {
            return ['count' => 1, 'unit_days' => 1];
        }

        throw ValidationException::withMessages([
            'repayment_plan' => 'Please select a valid repayment plan.',
        ]);
    }

    private function _generatePaymentReference(): string
    {
        do {
            $reference = 'TW-SEER-'.Str::upper(Str::random(14));
        } while (FlightBooking::where('payment_reference', $reference)->exists());

        return $reference;
    }

    private function _paymentDescription(string $flow, FlightBooking $booking): string
    {
        return match ($flow) {
            'travelflex_down_payment' => 'TravelFlex down payment for booking '.$booking->booking_ref,
            'travelflex_fees_payment' => 'TravelFlex administration & insurance fees for booking '.$booking->booking_ref,
            'held_ticket_full' => 'Flight ticket payment for booking '.$booking->booking_ref,
            default => 'Flight payment for booking '.$booking->booking_ref,
        };
    }

    private function _redirectAfterSeerbitFailure(?FlightBooking $booking, string $message)
    {
        return match ($booking?->payment_flow) {
            'held_ticket_full' => redirect()->route('flights.payment.options')->withErrors(['error' => $message]),
            'travelflex_down_payment' => redirect()->route('flights.travelflex')->withErrors(['error' => $message]),
            'travelflex_fees_payment' => redirect()->route('flights.travelflex.pending')->withErrors(['error' => $message]),
            default => redirect()->route('flights.payment.gateway')->withErrors(['error' => $message]),
        };
    }

    private function _redirectAfterSeerbitFlow(FlightBooking $booking)
    {
        return redirect()->to($this->_seerbitRedirectUrl($booking));
    }

    private function _seerbitRedirectUrl(FlightBooking $booking): string
    {
        return in_array($booking->payment_flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true)
            ? route('flights.travelflex.confirmation')
            : route('flights.confirmation');
    }

    private function _seerbitSessionBooking(): ?FlightBooking
    {
        $bookingId = session('flightBookingDbId');
        $reference = session('seerbitPaymentReference');

        if (! $bookingId || ! $reference) {
            return null;
        }

        return FlightBooking::query()
            ->whereKey($bookingId)
            ->where('payment_reference', $reference)
            ->first();
    }

    private function _prepareSeerbitStatusSession(FlightBooking $booking, string $reference): void
    {
        $this->_primeBookingSession($booking);

        session([
            'seerbitPaymentReference' => $reference,
            'seerbitPaymentFlow' => $booking->payment_flow,
            'paymentMethod' => $booking->payment_method ?: (
                in_array($booking->payment_flow, ['travelflex_down_payment', 'travelflex_fees_payment'], true) ? 'flex_gateway' : 'gateway'
            ),
            'bookingConfirmation' => $booking->booking_api_response ?? session('bookingConfirmation', []),
            'ticketOrderResult' => $booking->ticket_api_response ?? session('ticketOrderResult', []),
            'ticketSuccess' => $booking->ticket_ordered || $booking->booking_status === 'ticketed',
        ]);
    }

    private function _seerbitPublicStatus(FlightBooking $booking): array
    {
        $booking = $booking->fresh();
        $bookingStatus = strtolower((string) $booking->booking_status);
        $paymentStatus = strtolower((string) $booking->payment_status);
        $paymentConfirmed = $booking->payment_verified_at !== null
            || in_array($paymentStatus, ['paid', 'partially_paid'], true);

        if (
            $booking->ticket_ordered
            || $bookingStatus === 'ticketed'
            || ($paymentConfirmed && $bookingStatus === 'confirmed')
        ) {
            return [
                'state' => 'complete',
                'headline' => $bookingStatus === 'ticketed' || $booking->ticket_ordered
                    ? 'Payment confirmed and ticket issued'
                    : 'Payment and booking confirmed',
                'message' => $bookingStatus === 'ticketed' || $booking->ticket_ordered
                    ? 'Your booking is ticketed. We are taking you to your confirmation now.'
                    : 'Your booking is confirmed. We are taking you to your confirmation now.',
                'booking_reference' => $booking->booking_ref,
            ];
        }

        if ($paymentConfirmed && in_array($bookingStatus, [
            'failed',
            'ticketing_failed',
            'hold_expired_review',
        ], true)) {
            return [
                'state' => 'attention',
                'headline' => 'Payment received — ticketing needs attention',
                'message' => 'Your payment is confirmed, but the ticket could not be issued automatically. Our team has been notified and your payment record is safe.',
                'booking_reference' => $booking->booking_ref,
            ];
        }

        if ($paymentStatus === 'failed' && ! $paymentConfirmed) {
            return [
                'state' => 'failed',
                'headline' => 'Payment could not be verified',
                'message' => 'We could not verify this payment. If your account was debited, please contact support with your booking reference before trying again.',
                'booking_reference' => $booking->booking_ref,
            ];
        }

        if ($paymentConfirmed || $bookingStatus === 'ticketing_in_progress') {
            return [
                'state' => 'ticketing',
                'headline' => 'Payment confirmed — issuing your ticket',
                'message' => 'Your payment is successful. We are waiting for the airline to finish issuing your ticket.',
                'booking_reference' => $booking->booking_ref,
            ];
        }

        return [
            'state' => 'verifying',
            'headline' => 'Confirming your payment',
            'message' => 'SeerBit has returned your payment to TravelWheel. We are verifying it securely before issuing your ticket.',
            'booking_reference' => $booking->booking_ref,
        ];
    }

    private function _extractTicketOrderErrorMessage(array $response, string $fallback = 'Ticket order failed.'): string
    {
        $result = data_get($response, 'AirOrderTicketRS.TicketOrderResult', $response);

        return is_array($result)
            ? $this->_extractApiErrorMessage($result, $fallback)
            : $fallback;
    }

    private function _extractApiErrorMessage(array $payload, string $fallback): string
    {
        $paths = [
            'Errors.0.Errors.ErrorMessage',
            'Errors.Errors.ErrorMessage',
            'Errors.Error.ErrorMessage',
            'Errors.ErrorMessage',
            'Error.ErrorMessage',
            'ErrorMessage',
            'BookFlightResponse.BookFlightResult.Errors.0.Errors.ErrorMessage',
            'BookFlightResponse.BookFlightResult.Errors.Errors.ErrorMessage',
            'BookFlightResponse.BookFlightResult.Errors.ErrorMessage',
            'AirOrderTicketRS.TicketOrderResult.Errors.Error.ErrorMessage',
            'AirOrderTicketRS.TicketOrderResult.Errors.ErrorMessage',
        ];

        foreach ($paths as $path) {
            $message = data_get($payload, $path);
            if (is_scalar($message) && trim((string) $message) !== '') {
                return trim((string) $message);
            }
        }

        return $this->_findFirstApiErrorMessage($payload) ?? $fallback;
    }

    private function _findFirstApiErrorMessage(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        foreach ($value as $key => $child) {
            if ($key === 'ErrorMessage' && is_scalar($child) && trim((string) $child) !== '') {
                return trim((string) $child);
            }

            $message = $this->_findFirstApiErrorMessage($child);
            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    private function _validateBookingPassengers(array $validated): void
    {
        $passengers = collect($validated['passengers'] ?? []);
        $messages = [];

        if ($passengers->count() > 9) {
            $messages['passengers'] = 'The total number of passengers must not exceed 9 per booking.';
        }

        $travelDate = $this->_bookingTravelDate();
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $passportMandatory = filter_var(
            $mappedFlight['isPassportMandatory'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        foreach ($passengers as $i => $pax) {
            $title = (string) ($pax['title'] ?? '');
            $type = (string) ($pax['type'] ?? '');

            if ($passportMandatory) {
                foreach ([
                    'passport_no' => 'Passport number',
                    'passport_issue_country' => 'Passport issuing country',
                    'passport_issue_date' => 'Passport issue date',
                    'passport_exp' => 'Passport expiry date',
                ] as $field => $label) {
                    if (blank($pax[$field] ?? null)) {
                        $messages["passengers.{$i}.{$field}"] = "{$label} is required for this fare.";
                    }
                }
            }

            if (in_array($type, ['CHD', 'INF'], true) && ! in_array($title, ['Master', 'Miss'], true)) {
                $messages["passengers.{$i}.title"] = 'Child and infant passenger titles must be Master or Miss.';
            }

            if (empty($pax['dob']) || empty($pax['type'])) {
                continue;
            }

            try {
                $age = Carbon::parse($pax['dob'])->diffInYears($travelDate);
            } catch (\Throwable) {
                continue;
            }

            $key = "passengers.{$i}.dob";
            if ($pax['type'] === 'ADT' && $age < 18) {
                $messages[$key] = 'Adult passengers must be at least 18 years old on the travel date.';
            }
            if ($pax['type'] === 'CHD' && ($age < 2 || $age > 12)) {
                $messages[$key] = 'Child passengers must be between 2 and 12 years old on the travel date.';
            }
            if ($pax['type'] === 'INF' && $age >= 2) {
                $messages[$key] = 'Infant passengers must be under 2 years old on the travel date.';
            }

            if (! empty($pax['passport_issue_date'])) {
                try {
                    if (Carbon::parse($pax['passport_issue_date'])->gt($travelDate)) {
                        $messages["passengers.{$i}.passport_issue_date"] = 'Passport issue date must be before the travel date.';
                    }
                } catch (\Throwable) {
                    // The request date rule provides the customer-facing error.
                }
            }
            if (! empty($pax['passport_exp'])) {
                try {
                    if (Carbon::parse($pax['passport_exp'])->lte($travelDate)) {
                        $messages["passengers.{$i}.passport_exp"] = 'Passport must remain valid through the travel date.';
                    }
                } catch (\Throwable) {
                    // The request date rule provides the customer-facing error.
                }
            }
        }

        if (! empty($messages)) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function _validatePassengerCountsAgainstSearch(array $passengers): void
    {
        $searchParams = session('bookingSearchParams', []);
        $expected = [
            'ADT' => (int) ($searchParams['adults'] ?? 1),
            'CHD' => (int) ($searchParams['childs'] ?? 0),
            'INF' => (int) ($searchParams['kids'] ?? 0),
        ];

        $actual = collect($passengers)->countBy('type');
        $messages = [];

        foreach ($expected as $type => $count) {
            if ((int) ($actual[$type] ?? 0) !== $count) {
                $messages['passengers'] = "Passenger count mismatch. Please refresh the booking page and enter details for {$expected['ADT']} adult(s), {$expected['CHD']} child(ren), and {$expected['INF']} infant(s).";
                break;
            }
        }

        if (! empty($messages)) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function _bookingTravelDate(): Carbon
    {
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $date = $mappedFlight['departDT'] ?? data_get($mappedFlight, 'segments.0.departDT');
        $searchParams = session('bookingSearchParams', []);

        if (! $date && ! empty($searchParams['depart'])) {
            return Carbon::createFromFormat('d/m/Y', $searchParams['depart'])->startOfDay();
        }

        return $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
    }

    private function _callBookApi(array $validated, Request $request): array
    {
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        $fareSourceCode = $mappedFlight['fareSourceCode'] ?? $validated['fare_source_code'];
        $isPassportMand = $mappedFlight['isPassportMandatory'] ?? false;
        $fareType = $mappedFlight['fareType'] ?? 'Public';
        $contact = $validated['contact'];

        $passengers = collect($validated['passengers']);
        $adults = $passengers->where('type', 'ADT')->values();
        $children = $passengers->where('type', 'CHD')->values();
        $infants = $passengers->where('type', 'INF')->values();

        $buildPaxGroup = function ($list): array {
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
            // ── NEW: Frequent Flyer ───────────────────────────────────────────
            if (array_filter($list->pluck('frequent_flyer_number')->toArray())) {
                $g['frequentFlyrNum'] = $list->pluck('frequent_flyer_number')->toArray();
            }
            // Extra services
            $baggageOut = session('extraBaggage.outbound', []);
            $baggageIn = session('extraBaggage.inbound', []);
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
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),

            'flightBookingInfo' => [
                'flight_session_id' => $validated['session_id'] ?? session('bookingSessionId'),
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

        // Credentials are stripped before logging, same convention as the search payload.
        $loggablePayload = $payload;
        unset(
            $loggablePayload['user_id'],
            $loggablePayload['user_password'],
            $loggablePayload['access'],
            $loggablePayload['ip_address']
        );

        try {
            $response = Http::connectTimeout(10)->timeout(90)->post(config('services.travelnext.base_url').'booking', $payload);
            if ($response->failed()) {
                Log::error('FlightBooking API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $loggablePayload,
                ]);

                return ['error' => true, 'message' => 'Booking request failed. Please try again.', 'data' => []];
            }

            $data = $response->json();
            $bookResult = $data['BookFlightResponse']['BookFlightResult'] ?? [];
            $success = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $success) {
                Log::warning('FlightBooking API returned unsuccessful result', [
                    'response' => $data,
                    'payload' => $loggablePayload,
                ]);
            }

            return ['error' => false, 'message' => '', 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('FlightBooking API error', ['message' => $e->getMessage()]);

            return ['error' => true, 'message' => 'A network error occurred. Please try again.', 'data' => []];
        }
    }

    /**
     * Generate a unique, human-readable booking reference.
     * Format: TW-XXXXXXXX  (TW prefix + 8 uppercase alphanumeric chars)
     * This is OUR internal reference — completely separate from the airline's
     * UniqueID / e-ticket number returned by the ticketing API.
     */
    private function _generateBookingRef(): string
    {
        do {
            $ref = 'TW-'.strtoupper(substr(base_convert(bin2hex(random_bytes(5)), 16, 36), 0, 8));
        } while (FlightBooking::where('booking_ref', $ref)->exists());

        return $ref;
    }

    private function _persistBooking(array $mappedFlight, array $validated, array $apiResponse, array $overrides = []): FlightBooking
    {
        $contact = $validated['contact'] ?? session('bookingContact', []);
        $passengers = $validated['passengers'] ?? session('bookingPassengers', []);

        $tktRaw = $overrides['tkt_time_limit'] ?? null;
        unset($overrides['tkt_time_limit']);

        // unique_id from the API (hold/ticket reference) — NOT our booking ref
        // We always generate our own booking_ref for customer-facing reference.
        $bookingRef = $this->_generateBookingRef();

        return FlightBooking::create(array_merge([
            'booking_ref' => $bookingRef,
            'fare_source_code' => $mappedFlight['fareSourceCode'] ?? '',
            'session_id' => session('bookingSessionId', ''),
            'fare_type' => $mappedFlight['fareType'] ?? 'Public',
            'trip_type' => session('tripType', ''),
            'route' => FlightDisplay::route($mappedFlight),
            'airline' => $mappedFlight['airline'] ?? '',
            'cabin' => \App\Support\FlightDisplay::cabin($mappedFlight),
            'currency' => $mappedFlight['currency'] ?? 'NGN',
            'supplier_price' => $mappedFlight['supplierPrice'] ?? ($mappedFlight['price'] ?? 0),
            'markup_amount' => $mappedFlight['markupAmount'] ?? 0,
            'markup_category' => $mappedFlight['markupCategory'] ?? null,
            'markup_details' => [
                'category' => $mappedFlight['markupCategory'] ?? null,
                'cabin' => $mappedFlight['markupCabin'] ?? null,
                'supplier_price' => $mappedFlight['supplierPrice'] ?? ($mappedFlight['price'] ?? 0),
                'markup_amount' => $mappedFlight['markupAmount'] ?? 0,
                'rate_per_passenger' => $mappedFlight['markupRatePerPassenger'] ?? 0,
                'passenger_count' => $mappedFlight['markupPassengerCount'] ?? 1,
                'customer_price' => $mappedFlight['price'] ?? 0,
                'currency' => $mappedFlight['currency'] ?? 'NGN',
            ],
            'total_price' => ((float) ($mappedFlight['price'] ?? 0)) + $this->_selectedExtrasTotal($overrides['extra_services_snapshot'] ?? session('selectedExtras', [])),
            'contact_email' => $contact['email'] ?? '',
            'contact_phone' => $contact['phone'] ?? '',
            'adult_count' => collect($passengers)->where('type', 'ADT')->count(),
            'child_count' => collect($passengers)->where('type', 'CHD')->count(),
            'infant_count' => collect($passengers)->where('type', 'INF')->count(),
            'tkt_time_limit' => $tktRaw ? \Carbon\Carbon::parse($tktRaw) : null,
            'booking_api_response' => $apiResponse,
            'passengers_snapshot' => $passengers,
            'flight_snapshot' => $mappedFlight,
        ], $overrides));
    }

    private function _sendConfirmedEmail(FlightBooking $booking, array $tripDetails = []): void
    {
        if ($booking->confirmation_email_sent) {
            Log::info('_sendConfirmedEmail: skipped (already sent)', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);

            return;
        }

        if (empty($booking->contact_email)) {
            Log::warning('_sendConfirmedEmail: skipped (missing contact_email)', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);

            return;
        }

        Log::info('_sendConfirmedEmail: start', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'has_trip_details' => ! empty($tripDetails),
            'has_unique_id' => ! empty($booking->unique_id),
        ]);

        // Fetch trip details from the API if the caller didn't supply them.
        // _callTripDetailsApi() already handles errors gracefully (returns []).
        if (empty($tripDetails) && ! empty($booking->unique_id)) {
            Log::info('_sendConfirmedEmail: fetching trip details', [
                'booking_ref' => $booking->booking_ref,
                'unique_id' => $booking->unique_id,
            ]);
            $tripDetails = $this->_callTripDetailsApi($booking->unique_id);

            Log::info('_sendConfirmedEmail: trip details fetched', [
                'booking_ref' => $booking->booking_ref,
                'trip_details_keys' => array_keys($tripDetails),
            ]);
        }

        if (! empty($tripDetails)) {
            $booking->update(['itinerary_snapshot' => $tripDetails]);
        }

        try {
            Log::info('_sendConfirmedEmail: sending ETicketMail', [
                'booking_ref' => $booking->booking_ref,
            ]);

            // dd($tripDetails['ItineraryInfo']['ReservationItems'][0]['ReservationItem']['AirlinePNR']);

            $sent = app(DurableMailService::class)->sendNowOrStore(
                DurableMailService::FLIGHT_ETICKET,
                (string) $booking->contact_email,
                $booking,
                ['trip_details' => $tripDetails],
                'flight-eticket:'.$booking->id,
            );

            if ($sent) {
                $booking->update(['confirmation_email_sent' => true]);
            }

            Log::info($sent ? '_sendConfirmedEmail: delivered' : '_sendConfirmedEmail: saved for retry', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);
        } catch (\Throwable $e) {
            Log::error('_sendConfirmedEmail: failed to send ETicketMail', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
                'error_type' => $e::class,
            ]);
        }
    }

    private function _sendPaymentReceipt(FlightBooking $booking): void
    {
        if ($booking->payment_receipt_sent) {
            Log::info('_sendPaymentReceipt: skipped (already sent)', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);

            return;
        }

        if (empty($booking->contact_email)) {
            Log::warning('_sendPaymentReceipt: skipped (missing contact_email)', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);

            return;
        }

        try {
            $sent = app(DurableMailService::class)->sendNowOrStore(
                DurableMailService::FLIGHT_RECEIPT,
                (string) $booking->contact_email,
                $booking,
                [],
                'flight-payment-receipt:'.$booking->id,
            );
            if ($sent) {
                $booking->update(['payment_receipt_sent' => true]);
            }

            Log::info($sent ? '_sendPaymentReceipt: delivered' : '_sendPaymentReceipt: saved for retry', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);
        } catch (\Throwable $e) {
            Log::error('_sendPaymentReceipt: failed', [
                'booking_id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
                'error_type' => $e::class,
            ]);
        }
    }

    private function _sendPendingEmail(FlightBooking $booking, string $method = 'bank_transfer'): void
    {
        if ($booking->pending_email_sent && $method === 'hold') {
            return;
        }

        try {
            \Log::info('Attempting to send pending email', [
                'booking_id' => $booking->id,
                'method' => $method,
            ]);

            $sent = app(DurableMailService::class)->sendNowOrStore(
                DurableMailService::FLIGHT_PENDING,
                (string) $booking->contact_email,
                $booking,
                compact('method'),
                "flight-pending:{$booking->id}:{$method}",
            );
            if ($sent) {
                $booking->update(['pending_email_sent' => true]);
            }

            \Log::info($sent ? 'Pending email delivered' : 'Pending email saved for retry', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send pending email', [
                'booking_id' => $booking->id,
                'error_type' => $e::class,
            ]);
        }
    }

    private function _sendTicketingFailureAlert(FlightBooking $booking, string $message, array $ticketResponse = []): void
    {
        try {
            app(AdminTicketingService::class)->sendFailureAlert($booking, $message, $ticketResponse);
        } catch (\Throwable $e) {
            Log::error('Ticketing failure alert failed', [
                'booking_ref' => $booking->booking_ref,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function _primeBookingSession(FlightBooking $booking): void
    {
        $flight = $booking->flight_snapshot ?? [];
        $contact = array_merge(session('bookingContact', []), [
            'email' => $booking->contact_email,
            'phone' => $booking->contact_phone,
        ]);
        $passengers = session('bookingPassengers', $booking->passengers_snapshot ?? []);

        session([
            'bookingFlight' => ['flight' => $flight],
            'bookingContact' => $contact,
            'bookingPassengers' => $passengers,
            'bookingUniqueId' => $booking->unique_id,
            'bookingRef' => $booking->booking_ref,
            'bookingTktTimeLimit' => optional($booking->tkt_time_limit)->toIso8601String(),
            'bookingStatus' => strtoupper((string) $booking->booking_status),
            'flightBookingDbId' => $booking->id,
            'tripType' => $booking->trip_type,
            'selectedExtras' => $booking->extra_services_snapshot ?? [],
        ]);
    }

    private function _forgetStaleCheckoutSession(): void
    {
        session()->forget([
            'bookingFlight',
            'bookingSearchParams',
            'bookingSessionId',
            'bookingContact',
            'bookingPassengers',
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
            'bookingIntent',
            'seerbitPaymentReference',
            'seerbitPaymentFlow',
            'ticketOrderResult',
            'ticketSuccess',
            'travelFlexPlan',
            'travelFlexApplicationId',
            'travelFlexApplicant',
            'travelFlexDocPaths',
        ]);
    }

    private function _clearCheckoutSession(FlightBooking $booking, array $keep = []): void
    {
        $preserved = array_merge([
            'flightBookingDbId' => $booking->id,
            'bookingRef' => $booking->booking_ref,
            'bookingUniqueId' => $booking->unique_id,
            'bookingStatus' => strtoupper((string) $booking->booking_status),
            'paymentMethod' => $booking->payment_method,
            'ticketSuccess' => $booking->ticket_ordered || $booking->booking_status === 'ticketed',
        ], $keep);

        session()->forget([
            'bookingFlight',
            'bookingSearchParams',
            'bookingSessionId',
            'bookingContact',
            'bookingPassengers',
            'selectedExtras',
            'extraBaggage',
            'extraMeal',
            'extraServices',
            'fareRules',
            'bookingConfirmation',
            'bookingTktTimeLimit',
            'bookingIntent',
            'seerbitPaymentReference',
            'seerbitPaymentFlow',
            'travelFlexPlan',
            'travelFlexApplicationId',
            'travelFlexApplicant',
            'travelFlexDocPaths',
        ]);

        session($preserved);
    }

    private function _callTripDetailsApi(string $uniqueId): array
    {
        $payload = [
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),
            'UniqueID' => $uniqueId,
        ];

        try {
            $response = Http::connectTimeout(10)->timeout(30)->post(config('services.travelnext.base_url').'trip_details', $payload);
            if ($response->failed()) {
                return [];
            }
            $data = $response->json();
            $result = $data['TripDetailsResponse']['TripDetailsResult'] ?? [];
            $success = filter_var($result['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $success) {
                return [];
            }

            $tripData = $result['TravelItinerary'] ?? [];
            // dd($tripData['ItineraryInfo']['ReservationItems'][0]['ReservationItem']['AirlinePNR']);
            $bookingStatus = $tripData['BookingStatus'] ?? '';
            $ticketStatus = $tripData['TicketStatus'] ?? '';

            // ── Handle CONFIRMED (not ticketed) case ─────────────────────────────
            if (strtoupper($bookingStatus) === 'CONFIRMED' && strtoupper($ticketStatus) !== 'TICKETED') {
                // Send alert email to support about untickleted confirmed booking
                $this->_sendUnTicketedConfirmationAlert($tripData, $uniqueId);
            }

            return $tripData;
        } catch (\Throwable $e) {
            Log::error('TripDetails API error', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Send alert to support when booking is CONFIRMED but not yet TICKETED
     * This indicates a potential issue that needs manual follow-up
     */
    private function _sendUnTicketedConfirmationAlert(array $tripData, string $uniqueId): void
    {
        try {
            $booking = FlightBooking::query()->where('unique_id', $uniqueId)->latest()->first();
            if (! $booking) {
                Log::warning('Unticketed supplier result has no local booking', ['unique_id' => $uniqueId]);

                return;
            }

            app(AdminTicketingService::class)->sendFailureAlert(
                $booking,
                'Supplier status is '.($tripData['TicketStatus'] ?? 'UNKNOWN').' while booking status is '.($tripData['BookingStatus'] ?? 'UNKNOWN').'.',
            );
        } catch (\Throwable $e) {
            Log::error('UnTicketedConfirmationAlert failed', [
                'unique_id' => $uniqueId,
                'error_type' => $e::class,
            ]);
        }
    }

    // =========================================================================
    //  Updated confirmation() — now also fetches trip details for ticketed bookings
    // =========================================================================
    public function confirmation()
    {
        $dbId = session('flightBookingDbId');
        $dbBooking = $dbId ? \App\Models\FlightBooking::find($dbId) : null;

        if (! session()->has('bookingConfirmation') && ! session()->has('bookingUniqueId') && ! $dbBooking) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No booking found.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
        if (empty($mappedFlight) && $dbBooking) {
            $mappedFlight = $dbBooking->flight_snapshot ?? [];
        }

        $uniqueId = session('bookingUniqueId', $dbBooking?->unique_id ?? '');
        $paymentMethod = session('paymentMethod', $dbBooking?->payment_method ?? 'gateway');

        // ── Fetch live trip details if booking is ticketed ────────────────────
        $tripDetails = [];
        $isTicketed = $dbBooking?->isTicketed()
            || in_array($paymentMethod, ['gateway', 'flex_gateway'])
            || (session('ticketSuccess') === true);

        if ($isTicketed && $uniqueId) {
            $tripDetails = $this->_callTripDetailsApi($uniqueId);
        }

        return view('livewire.pages.flight.flight-confirmation', [
            'flight' => $mappedFlight,
            'bookingResult' => session('bookingConfirmation', []),
            'ticketOrderResult' => session('ticketOrderResult', []),
            'ticketSuccess' => session('ticketSuccess', false),
            'uniqueId' => $uniqueId,                         // API e-ticket / hold ref
            'bookingRef' => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'bookingStatus' => session('bookingStatus', 'CONFIRMED'),
            'paymentMethod' => $paymentMethod,
            'dbBooking' => $dbBooking,
            'contact' => session('bookingContact', ['email' => $dbBooking?->contact_email, 'phone' => $dbBooking?->contact_phone]),
            'passengers' => session('bookingPassengers', $dbBooking?->passengers_snapshot ?? []),
            'tripDetails' => $tripDetails,   // ← NEW: live trip details from API
        ]);
    }

    // =========================================================================
    //  TravelFlex eligibility helpers
    // =========================================================================
    private function _travelFlexEligibility(array $flight): array
    {
        if (strtolower((string) ($flight['fareType'] ?? $flight['fare_type'] ?? '')) === 'webfare') {
            return [
                'eligible' => false,
                'reason' => 'TravelFlex is only available on fares that can be held while Fast Credit reviews the application.',
            ];
        }

        $refundableValue = $flight['isRefundable'] ?? false;
        $isRefundable = is_bool($refundableValue)
            ? $refundableValue
            : in_array(strtolower((string) $refundableValue), ['1', 'true', 'yes', 'y', 'refundable'], true);

        if (! $isRefundable) {
            return [
                'eligible' => false,
                'reason' => 'TravelFlex is only available for refundable fares.',
            ];
        }

        $departureDate = $this->_travelFlexDepartureDate($flight);
        if ($departureDate && Carbon::today()->diffInDays($departureDate->copy()->startOfDay(), false) < 14) {
            return [
                'eligible' => false,
                'reason' => 'TravelFlex is available when departure is at least 14 days away.',
            ];
        }

        $riskAssessment = app(TravelFlexRiskAssessmentService::class)->assess(
            $flight,
            session('selectedExtras', []),
        );

        if (! $riskAssessment['eligible']) {
            return [
                'eligible' => false,
                'reason' => $riskAssessment['reason'],
            ];
        }

        return [
            'eligible' => true,
            'reason' => '',
        ];
    }

    private function _travelFlexDepartureDate(array $flight): ?Carbon
    {
        $candidates = [
            data_get($flight, 'departDT'),
            data_get($flight, 'segments.0.departDT'),
            data_get($flight, 'multiLegs.0.departDT'),
            data_get($flight, 'multiLegs.0.segments.0.departDT'),
            data_get($flight, 'departDate'),
            data_get($flight, 'segments.0.departDate'),
            data_get($flight, 'multiLegs.0.segments.0.departDate'),
        ];

        foreach ($candidates as $candidate) {
            if (! $candidate) {
                continue;
            }

            try {
                return Carbon::parse($candidate);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    // =========================================================================
    //  travelFlex() - block unavailable TravelFlex fares before application
    // =========================================================================
    public function travelFlex()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;

        // ── Refundable check ──────────────────────────────────────────────────
        $travelFlexEligibility = $this->_travelFlexEligibility($mappedFlight);
        if (! $travelFlexEligibility['eligible']) {
            session()->forget('bookingIntent');

            return redirect()->route('flights.payment.options')
                ->withErrors(['flex_error' => $travelFlexEligibility['reason']]);
        }

        return view('livewire.pages.flight.flight-travelflex');
    }

    // =========================================================================
    //  travelFlexApplication() — Show the loan application form
    //  Called from the TravelFlex calculator page after plan is selected and
    //  before payment is made.
    // =========================================================================
    public function travelFlexApplication(Request $request)
    {
        // Store plan data from the calculator POST before showing the form
        if ($request->isMethod('POST')) {
            $request->validate([
                'down_percent' => 'required|integer|between:30,90',
                'repayment_plan' => 'required|string',
            ]);

            $bookingFlight = session('bookingFlight', []);
            $mappedFlight = $bookingFlight['flight'] ?? $bookingFlight;
            $travelFlexEligibility = $this->_travelFlexEligibility($mappedFlight);

            if (! $travelFlexEligibility['eligible']) {
                session()->forget(['travelFlexPlan', 'bookingIntent']);

                return redirect()->route('flights.payment.options')
                    ->withErrors(['flex_error' => $travelFlexEligibility['reason']]);
            }

            session(['travelFlexPlan' => $this->_normalizeTravelFlexPlan(
                (int) $request->input('down_percent'),
                (string) $request->input('repayment_plan'),
                (string) data_get(session('travelFlexPlan', []), 'payment_method', 'gateway'),
            )]);
            session(['travelFlexRedirectTarget' => 'application']);

            return redirect()->route('flights.travelflex.fastcredit');
        }

        if (! session()->has('travelFlexPlan')) {
            return redirect()->route('flights.travelflex');
        }

        return view('livewire.pages.flight.flight-travelflex-application');
    }

    public function travelFlexFastCreditRedirect()
    {
        $target = session('travelFlexRedirectTarget', session()->has('travelFlexPlan') ? 'application' : 'plan');

        if ($target === 'application' && ! session()->has('travelFlexPlan')) {
            return redirect()->route('flights.travelflex');
        }

        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        return view('livewire.pages.flight.flight-travelflex-fastcredit', [
            'target' => $target,
        ]);
    }

    // =========================================================================
    //  travelFlexSubmitApplication() — Validate + upload docs + send emails + pay
    // =========================================================================
    public function travelFlexSubmitApplication(Request $request)
    {
        $validated = $request->validate([
            'applicant_type' => 'required|in:individual,company',
            'home_address' => 'required|string|max:500',
            'email' => 'required|email',
            'phone_primary' => 'required|string|max:30',
            'phone_secondary' => 'nullable|string|max:30',
            'home_address_place_id' => 'nullable|string|max:255',
            'employer_address_place_id' => 'nullable|string|max:255',
            'next_of_kin_address_place_id' => 'nullable|string|max:255',
            'bvn' => 'required|string|size:11|regex:/^\d{11}$/',
            'nin' => 'required|string|size:11|regex:/^\d{11}$/',
            'title' => 'required|string|max:30',
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'other_name' => 'nullable|string|max:120',
            'marital_status' => 'required|in:single,married,divorced,separated',
            'gender' => 'required|in:female,male',
            'date_of_birth' => 'required|date|before:today',
            'passport_number' => 'required|string|max:50',
            'passport_expiry_date' => 'required|date|after:today',
            'government_id_type' => 'required|string|max:80',
            'employer_name' => 'required|string|max:200',
            'employer_address' => 'required|string|max:500',
            'occupation' => 'required|string|max:150',
            'job_description' => 'required|string|max:1000',
            'office_id' => 'required|string|max:80',
            'sector' => 'required|in:private,public',
            'ippis_number' => 'required_if:sector,public|nullable|string|max:80',
            'monthly_salary' => 'required|numeric|min:0',
            'salary_account_number' => 'required|string|max:30',
            'bank_name' => 'required|string|max:150',
            'social_media_platform' => 'nullable|in:facebook,instagram,x',
            'social_media_handle' => 'nullable|string|max:150',
            'next_of_kin_surname' => 'required|string|max:100',
            'next_of_kin_first_name' => 'required|string|max:100',
            'next_of_kin_other_names' => 'nullable|string|max:150',
            'next_of_kin_relationship' => 'required|string|max:80',
            'next_of_kin_date_of_birth' => 'required|date|before:today',
            'next_of_kin_gender' => 'required|in:female,male',
            'next_of_kin_title' => 'required|string|max:30',
            'next_of_kin_address' => 'required|string|max:500',
            'next_of_kin_phone_primary' => 'required|string|max:30',
            'next_of_kin_phone_secondary' => 'nullable|string|max:30',
            'next_of_kin_email' => 'required|email',
            'fast_credit_agreement' => 'accepted',
            'digital_signature' => 'required|string|max:200',
            'digital_signature_image' => ['required', 'string', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
            'witness_full_name' => 'required|string|max:200',
            'witness_signature_image' => ['required', 'string', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
            'witness_declaration' => 'accepted',
            'valid_id' => 'required_if:applicant_type,individual|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'passport_photo' => 'required_if:applicant_type,individual|file|mimes:jpg,jpeg,png|max:5120',
            'work_id_card' => 'required_if:applicant_type,individual|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'employment_letter' => 'required_if:applicant_type,individual|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_statements' => 'required_if:applicant_type,individual|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'representative_valid_id' => 'required_if:applicant_type,company|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'cac_status_report' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'share_certificate' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'memart' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'register_of_members' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'shareholders_agreement' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'return_of_allotment' => 'required_if:applicant_type,company|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'certificate_of_incorporation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'board_resolution' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'company_bank_statement' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'tin_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'bvn.size' => 'BVN must be exactly 11 digits.',
            'bvn.regex' => 'BVN must contain only numbers.',
            'nin.size' => 'NIN must be exactly 11 digits.',
            'nin.regex' => 'NIN must contain only numbers.',
            'fast_credit_agreement.accepted' => 'You must accept the Fast Credit loan agreement before submitting.',
            'witness_declaration.accepted' => 'The witness must confirm their signature before submission.',
        ]);

        if ($validated['applicant_type'] === 'company') {
            $validated['sector'] = 'private';
        }

        $currentPlan = session('travelFlexPlan', []);
        if (empty($currentPlan)) {
            return redirect()->route('flights.travelflex')
                ->withErrors(['error' => 'TravelFlex plan missing. Please choose your repayment plan again.']);
        }

        // ── Store uploaded documents ──────────────────────────────────────────
        $fullName = $this->_travelFlexFullName($validated);

        $docKeys = $validated['applicant_type'] === 'company'
            ? ['representative_valid_id', 'cac_status_report', 'share_certificate', 'memart', 'register_of_members', 'shareholders_agreement', 'return_of_allotment', 'certificate_of_incorporation', 'board_resolution', 'company_bank_statement', 'tin_certificate']
            : ['valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements'];
        $uploadPaths = [];
        $storagePaths = [];

        foreach ($docKeys as $key) {
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('travelflex_docs', 'local');
                $storagePaths[$key] = $path;
                $uploadPaths[$key] = storage_path('app/'.$path);
            }
        }

        // ── Update plan in session ────────────────────────────────────────────
        $tfPlan = $this->_normalizeTravelFlexPlan(
            (int) data_get($currentPlan, 'down_percent', 30),
            (string) data_get($currentPlan, 'repayment_plan', ''),
            'gateway',
        );
        $tfPlan['payment_method'] = null;
        session(['travelFlexPlan' => $tfPlan]);

        $applicant = [
            'applicant_type' => $validated['applicant_type'],
            'full_name' => $fullName,
            'email' => $validated['email'],
            'home_address' => $validated['home_address'],
            'phone_primary' => $validated['phone_primary'],
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'home_address_place_id' => $validated['home_address_place_id'] ?? null,
            'bvn' => $validated['bvn'] ?? null,
            'nin' => $validated['nin'] ?? null,
            'title' => $validated['title'] ?? null,
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'other_name' => $validated['other_name'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'passport_number' => $validated['passport_number'] ?? null,
            'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
            'employer_name' => $validated['employer_name'] ?? null,
            'employer_address' => $validated['employer_address'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'job_description' => $validated['job_description'] ?? null,
            'office_id' => $validated['office_id'],
        ];

        $fastCredit = $this->_fastCreditApplicationPayload($validated, $request);

        session(['travelFlexApplicant' => $applicant, 'travelFlexDocPaths' => $storagePaths]);
        $travelFlexApplication = $this->_persistTravelFlexApplication($applicant, $tfPlan, $storagePaths, $fastCredit);

        try {
            $travelFlexApplication = app(TravelFlexApplicationPdfService::class)->generateAndStore(
                $travelFlexApplication->fresh(['booking']),
                ['bvn' => $validated['bvn']],
            );
        } catch (\Throwable $exception) {
            $travelFlexApplication->update([
                'provider_status' => 'failed',
                'provider_email_error' => 'Fast Credit PDF generation failed: '.$exception->getMessage(),
            ]);
            Log::error('TravelFlex Fast Credit PDF generation failed', [
                'application_id' => $travelFlexApplication->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('flights.travelflex.pending')->withErrors([
                'error' => 'Your application was saved, but its Fast Credit form could not be prepared. TravelWheel will review it before provider handoff.',
            ]);
        }

        // ── Now branch on payment method ──────────────────────────────────────
        if ($booking = $travelFlexApplication->booking) {
            $booking->update([
                'booking_status' => 'awaiting_approval',
                'payment_status' => 'pending',
                'payment_method' => null,
            ]);
        }

        // Provider handoff and the customer confirmation email both attach a
        // dompdf-rendered itinerary/Fast Credit PDF, which can be slow (remote
        // airline-logo fetches, font rendering) and has previously exceeded
        // PHP's max_execution_time — a fatal that a try/catch cannot stop, since
        // it kills the request before any catch block runs. Deferring both to
        // run after the response has already reached the browser means a slow
        // or failing render can no longer block or crash the form submission.
        dispatch(function () use ($travelFlexApplication): void {
            try {
                app(TravelFlexApplicationService::class)->sendProviderEmail($travelFlexApplication->fresh(['booking']));
            } catch (\Throwable $exception) {
                $travelFlexApplication->update([
                    'provider_status' => 'failed',
                    'provider_email_error' => $exception->getMessage(),
                ]);
                Log::error('TravelFlex provider handoff failed', ['application_id' => $travelFlexApplication->id, 'error' => $exception->getMessage()]);
            }

            try {
                app(TravelFlexApplicationService::class)->notifyCustomerStatus(
                    $travelFlexApplication->fresh(['booking']),
                    'submitted',
                    'Fast Credit will contact you and provide a decision within 24 hours.',
                );
            } catch (\Throwable $exception) {
                Log::error('TravelFlex submission email failed', ['application_id' => $travelFlexApplication->id, 'error' => $exception->getMessage()]);
            }
        })->afterResponse();

        return redirect()->route('flights.travelflex.pending');
    }

    // =========================================================================
    public function travelFlexApproved(TravelFlexApplication $application, TravelFlexFlowService $flow)
    {
        try {
            $booking = $flow->revalidateHold($application->load('booking'));
        } catch (ValidationException $exception) {
            return redirect()->route('air.flight-s')->withErrors($exception->errors());
        }

        $flow->primeSession($application);

        return view('livewire.pages.flight.flight-travelflex-approved', [
            'application' => $application,
            'booking' => $booking,
            'paymentDeadline' => $flow->approvalDeadline($application),
        ]);
    }

    public function travelFlexApprovedPayment(Request $request, TravelFlexFlowService $flow)
    {
        $validated = $request->validate(['pay_method' => 'required|in:gateway,bank_transfer']);
        $application = TravelFlexApplication::with('booking')->findOrFail((int) session('travelFlexApplicationId'));
        $booking = ! $application->pricing_revalidated_at || $application->pricing_revalidated_at->lt(now()->subMinutes(10))
            ? $flow->revalidateHold($application)
            : $flow->assertApprovedForDeposit($application);
        $plan = $this->_normalizeTravelFlexPlan(
            (int) data_get($application->repayment_plan, 'down_percent', 30),
            (string) data_get($application->repayment_plan, 'repayment_plan', ''),
            $validated['pay_method'],
        );
        session(['travelFlexPlan' => $plan]);
        $application->update([
            'repayment_plan' => $plan,
            'payment_method' => $validated['pay_method'],
            // Gateway payment is about to start immediately; bank transfer deposit_status
            // stays 'not_due' until the customer actually submits a transfer reference,
            // so the bank-transfer step can tell "not started" apart from "reference submitted".
            'deposit_status' => $validated['pay_method'] === 'gateway' ? 'pending' : $application->deposit_status,
            'pricing_revalidated_at' => now(),
        ]);
        $booking->update(['booking_status' => 'awaiting_deposit']);

        return $validated['pay_method'] === 'gateway'
            ? $this->travelFlexGatewayProcess($request)
            : redirect()->route('flights.travelflex.bank-transfer-form');
    }

    //  travelFlexBankTransferForm() — Show bank details after application approval
    // =========================================================================
    public function travelFlexBankTransferForm()
    {
        $tfPlan = session('travelFlexPlan', []);
        $applicant = session('travelFlexApplicant', []);

        if (empty($tfPlan) || empty($applicant)) {
            return redirect()->route('flights.travelflex')
                ->withErrors(['error' => 'TravelFlex application session expired. Please continue again.']);
        }

        $application = TravelFlexApplication::with('booking')->findOrFail((int) session('travelFlexApplicationId'));
        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);

        $tfPlan = $this->_normalizeTravelFlexPlan(
            (int) data_get($tfPlan, 'down_percent', 30),
            (string) data_get($tfPlan, 'repayment_plan', ''),
            'bank_transfer',
        );
        session(['travelFlexPlan' => $tfPlan]);

        $bankAccounts = config('travelwheel.travelflex_bank_accounts', []);
        if ($bankAccounts === []) {
            Log::critical('TravelFlex bank transfer requested without configured bank accounts');

            return back()->withErrors([
                'error' => 'Bank transfer is temporarily unavailable. Please choose online payment or contact support.',
            ]);
        }

        $feesOwed = round((float) ($tfPlan['administration_fee'] ?? 0) + (float) ($tfPlan['insurance_fee'] ?? 0), 2);
        $depositSubmitted = in_array($application->deposit_status, ['pending', 'paid'], true);
        $feesSubmitted = in_array($application->fees_status, ['pending', 'paid'], true);

        if ($depositSubmitted && ($feesSubmitted || $feesOwed <= 0)) {
            return redirect()->route('flights.travelflex.pending');
        }

        $stage = $depositSubmitted ? 'fees' : 'deposit';

        return view('livewire.pages.flight.flight-travelflex-bank-transfer', compact('bankAccounts', 'stage'));
    }

    // =========================================================================
    //  travelFlexGatewayProcess() — Process gateway payment + book + email + confirm
    // =========================================================================
    public function travelFlexGatewayProcess(Request $request)
    {
        $contact = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);
        $tfPlan = session('travelFlexPlan', []);

        if (empty($contact) || empty($passengers) || empty($tfPlan)) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $application = TravelFlexApplication::with('booking')->findOrFail((int) session('travelFlexApplicationId'));
        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);

        $tfPlan = $this->_normalizeTravelFlexPlan(
            (int) data_get($tfPlan, 'down_percent', 30),
            (string) data_get($tfPlan, 'repayment_plan', ''),
            'gateway',
        );
        session(['travelFlexPlan' => $tfPlan]);

        return $this->_startSeerbitPayment('travelflex_down_payment');
    }

    private function _travelFlexFullName(array $validated): string
    {
        return trim(collect([
            $validated['title'] ?? null,
            $validated['surname'] ?? null,
            $validated['first_name'] ?? null,
            $validated['other_name'] ?? null,
        ])->filter(fn ($value) => filled($value))->implode(' '));
    }

    private function _fastCreditApplicationPayload(array $validated, Request $request): array
    {
        $applicantType = $validated['applicant_type'];
        $fullName = $this->_travelFlexFullName($validated);

        return [
            'applicant_type' => $applicantType,
            'identity_details' => [
                'nin' => $validated['nin'] ?? null,
                'title' => $validated['title'] ?? null,
                'surname' => $validated['surname'] ?? null,
                'first_name' => $validated['first_name'] ?? null,
                'other_name' => $validated['other_name'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'phone_primary' => $validated['phone_primary'],
                'phone_secondary' => $validated['phone_secondary'] ?? null,
                'passport_number' => $validated['passport_number'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'government_id_type' => $validated['government_id_type'] ?? null,
                'social_media_platform' => $validated['social_media_platform'] ?? null,
                'social_media_handle' => $validated['social_media_handle'] ?? null,
            ],
            'employment_details' => [
                'employer_name' => $validated['employer_name'] ?? null,
                'employer_address' => $validated['employer_address'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'job_description' => $validated['job_description'] ?? null,
                'office_id' => $validated['office_id'] ?? null,
                'sector' => $validated['sector'] ?? null,
                'ippis_number' => $validated['ippis_number'] ?? null,
                'employer_address_place_id' => $validated['employer_address_place_id'] ?? null,
            ],
            'bank_details' => [
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'salary_account_number' => $validated['salary_account_number'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
            ],
            'next_of_kin_details' => [
                'surname' => $validated['next_of_kin_surname'] ?? null,
                'first_name' => $validated['next_of_kin_first_name'] ?? null,
                'other_names' => $validated['next_of_kin_other_names'] ?? null,
                'relationship' => $validated['next_of_kin_relationship'] ?? null,
                'date_of_birth' => $validated['next_of_kin_date_of_birth'] ?? null,
                'gender' => $validated['next_of_kin_gender'] ?? null,
                'title' => $validated['next_of_kin_title'] ?? null,
                'residential_address' => $validated['next_of_kin_address'] ?? null,
                'residential_address_place_id' => $validated['next_of_kin_address_place_id'] ?? null,
                'phone_primary' => $validated['next_of_kin_phone_primary'] ?? null,
                'phone_secondary' => $validated['next_of_kin_phone_secondary'] ?? null,
                'email' => $validated['next_of_kin_email'] ?? null,
            ],
            'company_details' => $applicantType === 'company' ? [
                'company_name' => $validated['employer_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone_primary'],
                'registered_address' => $validated['employer_address'],
                'registered_address_place_id' => $validated['employer_address_place_id'] ?? null,
                'sector' => 'private',
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['salary_account_number'],
            ] : null,
            'representative_details' => [
                'full_name' => $fullName,
                'email' => $validated['email'],
                'phone_primary' => $validated['phone_primary'],
                'phone_secondary' => $validated['phone_secondary'] ?? null,
                'role' => $validated['occupation'],
                'residential_address' => $validated['home_address'],
                'residential_address_place_id' => $validated['home_address_place_id'] ?? null,
            ],
            'agreement_acceptance' => [
                'agreement' => 'fast_credit_loan_agreement',
                'version' => '2026-07-22',
                'accepted' => $request->boolean('fast_credit_agreement'),
                'digital_signature' => $validated['digital_signature'],
                'signature_image' => $validated['digital_signature_image'],
                'signature_format' => 'image/png',
                'accepted_at' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'witness' => [
                    'full_name' => $validated['witness_full_name'],
                    'signature_image' => $validated['witness_signature_image'],
                    'signature_format' => 'image/png',
                    'confirmed' => $request->boolean('witness_declaration'),
                    'signed_at' => now()->toIso8601String(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                ],
            ],
        ];
    }

    private function _persistTravelFlexApplication(array $applicant, array $tfPlan, array $documentPaths, array $fastCredit = []): TravelFlexApplication
    {
        $booking = ($dbId = session('flightBookingDbId')) ? FlightBooking::find($dbId) : null;
        $bvn = (string) ($applicant['bvn'] ?? '');

        $application = TravelFlexApplication::updateOrCreate(
            ['id' => session('travelFlexApplicationId')],
            [
                'flight_booking_id' => $booking?->id,
                'booking_ref' => $booking?->booking_ref ?? session('bookingRef'),
                'unique_id' => $booking?->unique_id ?? session('bookingUniqueId'),
                'applicant_type' => $fastCredit['applicant_type'] ?? $applicant['applicant_type'] ?? 'individual',
                'applicant_details' => [
                    'full_name' => $applicant['full_name'] ?? null,
                    'email' => $applicant['email'] ?? null,
                    'home_address' => $applicant['home_address'] ?? null,
                    'phone_primary' => $applicant['phone_primary'] ?? null,
                    'phone_secondary' => $applicant['phone_secondary'] ?? null,
                    'applicant_type' => $applicant['applicant_type'] ?? 'individual',
                ],
                'bvn_metadata' => [
                    'last_four' => $bvn !== '' ? substr($bvn, -4) : null,
                    'hash' => $bvn !== '' ? hash('sha256', $bvn.config('app.key')) : null,
                    'captured_at' => now()->toIso8601String(),
                ],
                'identity_details' => $fastCredit['identity_details'] ?? null,
                'employment_details' => $fastCredit['employment_details'] ?? [
                    'employer_name' => $applicant['employer_name'] ?? null,
                    'employer_address' => $applicant['employer_address'] ?? null,
                    'occupation' => $applicant['occupation'] ?? null,
                    'job_description' => $applicant['job_description'] ?? null,
                    'office_id' => $applicant['office_id'] ?? null,
                ],
                'bank_details' => $fastCredit['bank_details'] ?? null,
                'next_of_kin_details' => $fastCredit['next_of_kin_details'] ?? null,
                'company_details' => $fastCredit['company_details'] ?? null,
                'representative_details' => $fastCredit['representative_details'] ?? null,
                'document_paths' => $documentPaths,
                'agreement_acceptance' => $fastCredit['agreement_acceptance'] ?? null,
                'repayment_plan' => $tfPlan,
                'down_payment' => $tfPlan['down_payment'] ?? null,
                'down_percent' => $tfPlan['down_percent'] ?? null,
                'grand_total' => $tfPlan['grand_total'] ?? null,
                'total_interest' => $tfPlan['total_interest'] ?? null,
                'payment_method' => $tfPlan['payment_method'] ?? null,
                'payment_status' => 'not_due',
                'application_status' => 'submitted',
                'financing_status' => 'pending',
                'deposit_status' => 'not_due',
                'fees_status' => 'not_due',
            ],
        );

        session(['travelFlexApplicationId' => $application->id]);

        return $application;
    }

    private function _syncTravelFlexApplicationBooking(FlightBooking $booking, array $overrides = []): void
    {
        $applicationId = session('travelFlexApplicationId');

        if (! $applicationId) {
            return;
        }

        TravelFlexApplication::whereKey($applicationId)->update(array_merge([
            'flight_booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'unique_id' => $booking->unique_id,
            'payment_status' => $booking->payment_status,
            'payment_method' => $booking->payment_method,
        ], $overrides));
    }

    // ── Private: send TravelFlex application emails ───────────────────────────
    /**
     * Collect and structure the selected extra services (baggage, meals) from the request
     * Returns a formatted array containing all selected services with their details
     */
    private function _collectSelectedExtras(Request $request): array
    {
        $extraServices = session('extraServices', []);
        $extraBaggage = $request->input('extra_baggage', []);
        $extraMeal = $request->input('extra_meal', []);

        // Parse extra services API response
        $esResult = $extraServices['ExtraServicesResponse']['ExtraServicesResult']['ExtraServicesData'] ?? [];
        $dynBaggage = $esResult['DynamicBaggage'] ?? [];
        $dynMeal = $esResult['DynamicMeal'] ?? [];
        $flight = session('bookingFlight.flight', session('bookingFlight', []));
        $bookingCurrency = strtoupper((string) ($flight['currency'] ?? 'NGN'));

        $collected = [
            'baggage' => [],
            'meal' => [],
            'total_amount' => 0,
            'currency' => $bookingCurrency,
        ];

        // ── Process selected baggage ──────────────────────────────────────
        foreach ($extraBaggage as $direction => $items) {
            foreach ($items as $serviceId => $quantity) {
                $quantity = min(9, max(0, (int) $quantity));
                if ($quantity <= 0) {
                    continue;
                }

                // Find matching baggage service from API data
                foreach ($dynBaggage as $bag) {
                    $bagDir = str_contains(strtoupper($bag['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                    if ($bagDir === $direction) {
                        foreach (($bag['Services'][0] ?? []) as $svc) {
                            if ((string) $svc['ServiceId'] === (string) $serviceId) {
                                $price = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                $currency = strtoupper((string) ($svc['ServiceCost']['CurrencyCode'] ?? $bookingCurrency));
                                if ($currency !== $bookingCurrency) {
                                    throw ValidationException::withMessages([
                                        'extra_baggage' => 'The selected baggage price uses a different currency. Please refresh the booking before continuing.',
                                    ]);
                                }
                                $collected['currency'] = $currency;
                                $collected['total_amount'] += $price * $quantity;
                                $collected['baggage'][] = [
                                    'service_id' => $serviceId,
                                    'description' => $svc['Description'] ?? 'Baggage',
                                    'direction' => $direction,
                                    'quantity' => $quantity,
                                    'unit_price' => $price,
                                    'currency' => $currency,
                                    'line_total' => $price * $quantity,
                                ];
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // ── Process selected meals ───────────────────────────────────────
        foreach ($extraMeal as $direction => $segments) {
            foreach ($segments as $segmentIndex => $items) {
                foreach ($items as $serviceId => $checked) {
                    if (! $checked) {
                        continue;
                    }

                    // Find matching meal service from API data
                    foreach ($dynMeal as $meal) {
                        $mealDir = str_contains(strtoupper($meal['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                        if ($mealDir === $direction) {
                            foreach (($meal['Services'][$segmentIndex] ?? []) as $svc) {
                                if ((string) $svc['ServiceId'] === (string) $serviceId) {
                                    $price = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                    $currency = strtoupper((string) ($svc['ServiceCost']['CurrencyCode'] ?? $bookingCurrency));
                                    if ($currency !== $bookingCurrency) {
                                        throw ValidationException::withMessages([
                                            'extra_meal' => 'The selected meal price uses a different currency. Please refresh the booking before continuing.',
                                        ]);
                                    }
                                    $collected['currency'] = $currency;
                                    $collected['total_amount'] += $price;
                                    $collected['meal'][] = [
                                        'service_id' => $serviceId,
                                        'description' => $svc['Description'] ?? 'Meal',
                                        'direction' => $direction,
                                        'segment' => $segmentIndex,
                                        'unit_price' => $price,
                                        'currency' => $currency,
                                    ];
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $collected;
    }
}

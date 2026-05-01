<?php

namespace App\Http\Controllers;

use App\Mail\BookingPendingMail;
use App\Mail\ETicketMail;
use App\Models\FlightBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FlightBookingController extends Controller
{
    // =========================================================================
    //  select() — revalidate + extra services + fare rules → store in session
    // =========================================================================
    public function select(Request $request)
    {
        $validated = $request->validate([
            'fare_source_code' => 'required|string',
            'session_id'       => 'required|string',
        ]);
    
        $payload = [
            'session_id'       => $validated['session_id'],
            'fare_source_code' => $validated['fare_source_code'],
        ];
    
        // ── 1. Revalidate ─────────────────────────────────────────────────────────
        $revalidateResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/revalidate', $payload);
    
        if ($revalidateResponse->failed()) {
            return back()->withErrors(['error' => 'Revalidation failed. Please try again.']);
        }
    
        $revalidateData = $revalidateResponse->json();
        $isValid = data_get($revalidateData, 'AirRevalidateResponse.AirRevalidateResult.IsValid');
    
        if (!$isValid) {
            return back()->withErrors([
                'error' => 'This fare is no longer available. Please select another flight.'
            ])->withInput();
        }
    
        $fi = data_get(
            $revalidateData,
            'AirRevalidateResponse.AirRevalidateResult.FareItineraries.FareItinerary',
            []
        );
    
        if (empty($fi)) {
            return back()->withErrors(['error' => 'No fare data returned from revalidation.']);
        }
    
        // ── 2. Reference data ─────────────────────────────────────────────────────
        $airlines     = collect(json_decode(file_get_contents(public_path('assets/data/airline.json')), true))->keyBy('AirLineCode');
        $airports     = collect(json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true))->keyBy('AirportCode');
        $searchParams = session('searchParamsStore', []);
        $tripType     = strtolower($searchParams['trip'] ?? 'oneway');
    
        // ── 3. Segment mapper ─────────────────────────────────────────────────────
        $mapSegments = function (array $odo) use ($airlines, $airports): array {
            return collect($odo)->map(function ($seg) use ($airlines, $airports) {
                $fs          = $seg['FlightSegment'];
                $dep         = \Carbon\Carbon::parse($fs['DepartureDateTime']);
                $arr         = \Carbon\Carbon::parse($fs['ArrivalDateTime']);
                $airlineCode = $fs['MarketingAirlineCode'];
                $airline     = $airlines->get($airlineCode);
                $fromCode    = $fs['DepartureAirportLocationCode'];
                $toCode      = $fs['ArrivalAirportLocationCode'];
                $fromAirport = $airports->get($fromCode);
                $toAirport   = $airports->get($toCode);
                $opCode      = $fs['OperatingAirline']['Code'] ?? $airlineCode;
                $opAirline   = $airlines->get($opCode);
    
                return [
                    'from'              => $fromCode,
                    'to'                => $toCode,
                    'fromCity'          => $fromAirport ? ($fromAirport['City'] . ' (' . $fromCode . ')') : $fromCode,
                    'toCity'            => $toAirport   ? ($toAirport['City']   . ' (' . $toCode   . ')') : $toCode,
                    'fromAirport'       => $fromAirport['AirportName'] ?? $fromCode,
                    'toAirport'         => $toAirport['AirportName']   ?? $toCode,
                    'fromCountry'       => $fromAirport['Country']     ?? '',
                    'toCountry'         => $toAirport['Country']       ?? '',
                    'fromLat'           => $fromAirport['Latitude']    ?? null,
                    'fromLon'           => $fromAirport['Longitude']   ?? null,
                    'toLat'             => $toAirport['Latitude']      ?? null,
                    'toLon'             => $toAirport['Longitude']     ?? null,
                    'departTime'        => $dep->format('H:i'),
                    'arriveTime'        => $arr->format('H:i'),
                    'departDate'        => $dep->format('D, d M Y'),
                    'arriveDate'        => $arr->format('D, d M Y'),
                    'departDT'          => $fs['DepartureDateTime'],
                    'arriveDT'          => $fs['ArrivalDateTime'],
                    'duration'          => (int) $fs['JourneyDuration'],
                    'flightNo'          => $airlineCode . $fs['FlightNumber'],
                    'airline'           => $fs['MarketingAirlineName'],
                    'airlineCode'       => $airlineCode,
                    'airlineLogo'       => $airline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'equipment'         => $fs['OperatingAirline']['Equipment'] ?? '',
                    'cabin'             => $fs['CabinClassText'] ?? '',
                    'cabinCode'         => $fs['CabinClassCode'] ?? 'Y',
                    'resBookCode'       => $seg['ResBookDesigCode'] ?? '',
                    'mealCode'          => $fs['MealCode']          ?? '',
                    'seatsLeft'         => (int)  ($seg['SeatsRemaining']['Number']       ?? 9),
                    'belowMinimum'      => (bool) ($seg['SeatsRemaining']['BelowMinimum'] ?? false),
                    'isCodeshare'       => $opCode !== $airlineCode,
                    'operatingCode'     => $opCode,
                    'operatingAirline'  => $fs['OperatingAirline']['Name']         ?? '',
                    'operatingFlightNo' => $opCode . ($fs['OperatingAirline']['FlightNumber'] ?? ''),
                    'operatingLogo'     => $opAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'eticket'           => (bool) ($fs['Eticket'] ?? true),
                ];
            })->values()->toArray();
        };
    
        // ── 4. Helpers ────────────────────────────────────────────────────────────
        $calcLayovers = function (array $segs): array {
            $out = [];
            for ($i = 0; $i < count($segs) - 1; $i++) {
                $mins  = \Carbon\Carbon::parse($segs[$i]['arriveDT'])
                            ->diffInMinutes(\Carbon\Carbon::parse($segs[$i + 1]['departDT']));
                $out[] = floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
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
    
        $fmtMins = fn(int $m): string => floor($m / 60) . 'h ' . ($m % 60) . 'm';
    
        // ── 5. Map segments by trip type ──────────────────────────────────────────
        $fareInfo = $fi['AirItineraryFareInfo'];
        $odos     = $fi['OriginDestinationOptions'] ?? [];
    
        $segments               = [];
        $layoverDurations       = [];
        $returnSegments         = [];
        $returnLayoverDurations = [];
        $multiLegs              = [];
        $totalStops             = 0;
        $totalMins              = 0;
        $totalTimeMins          = 0;
        $returnStops            = 0;
        $returnTotalTimeMins    = 0;
        $returnDurationLabel    = '';
        $returnTotalTimeLabel   = '';
        $returnDateLabel        = '';
        $departDateLabel        = '';
    
        // ── ONE WAY ───────────────────────────────────────────────────────────────
        if ($tripType === 'oneway') {
    
            $odo0             = $odos[0]['OriginDestinationOption'] ?? [];
            $segments         = $mapSegments($odo0);
            $totalStops       = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins        = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins    = $totalMins + $calcLayoverMins($segments);
    
        // ── RETURN ────────────────────────────────────────────────────────────────
        } elseif ($tripType === 'return') {
    
            $odo0             = $odos[0]['OriginDestinationOption'] ?? [];
            $segments         = $mapSegments($odo0);
            $totalStops       = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins        = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins    = $totalMins + $calcLayoverMins($segments);
    
            if (!empty($odos[1])) {
                $odo1                   = $odos[1]['OriginDestinationOption'] ?? [];
                $returnSegments         = $mapSegments($odo1);
                $returnStops            = (int) ($odos[1]['TotalStops'] ?? max(0, count($odo1) - 1));
                $returnMins             = array_sum(array_column($returnSegments, 'duration'));
                $returnDurationLabel    = $fmtMins($returnMins);
                $returnLayoverDurations = $calcLayovers($returnSegments);
                $returnTotalTimeMins    = $returnMins + $calcLayoverMins($returnSegments);
                $returnTotalTimeLabel   = $fmtMins($returnTotalTimeMins);
    
                if (!empty($returnSegments[0]['departDT'])) {
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
                if (empty($odo)) continue;
    
                $legSegs      = $mapSegments($odo);
                $legFirst     = $legSegs[0]                         ?? [];
                $legLast      = !empty($legSegs) ? end($legSegs)    : [];
                $legMins      = array_sum(array_column($legSegs, 'duration'));
                $legLayovers  = $calcLayovers($legSegs);
                $legLayoverMs = $calcLayoverMins($legSegs);
                $legTotalTime = $legMins + $legLayoverMs;
                $legStops     = (int) ($odoEntry['TotalStops'] ?? max(0, count($odo) - 1));
    
                $totalStops += $legStops;
    
                $multiLegs[] = [
                    'segments'         => $legSegs,
                    'stops'            => $legStops,
                    'durationLabel'    => $fmtMins($legMins),
                    'layoverDurations' => $legLayovers,
                    'totalTimeMins'    => $legTotalTime,
                    'totalTimeLabel'   => $fmtMins($legTotalTime),
                    'departDateLabel'  => !empty($legFirst['departDT'])
                                            ? \Carbon\Carbon::parse($legFirst['departDT'])->format('D, d M')
                                            : '',
                    // ── Shortcut fields so the blade never digs into segments[] ──
                    'from'       => $legFirst['from']       ?? '',
                    'to'         => $legLast['to']          ?? '',
                    'fromCity'   => $legFirst['fromCity']   ?? ($legFirst['from'] ?? ''),
                    'toCity'     => $legLast['toCity']      ?? ($legLast['to']   ?? ''),
                    'departTime' => $legFirst['departTime'] ?? '',
                    'arriveTime' => $legLast['arriveTime']  ?? '',
                    'departDT'   => $legFirst['departDT']   ?? '',
                    'arriveDT'   => $legLast['arriveDT']    ?? '',
                ];
            }
    
            // First leg drives the top-level outbound fields for the card header
            //$segments         = $multiLegs[0]['segments'] ?? [];
            $totalMins        = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins    = $totalMins + $calcLayoverMins($segments);
        }
    
        // ── 6. Shared shortcuts ───────────────────────────────────────────────────
        $firstSeg = $segments[0] ?? [];
        $lastSeg  = !empty($segments) ? end($segments) : [];
    
        // For multi-city: first seg of trip = multiLegs[0].segments[0]
        //                 last  seg of trip = last segment of final leg
        if ($tripType === 'multi' && !empty($multiLegs)) {
            $firstSeg      = $multiLegs[0]['segments'][0] ?? [];
            $lastMultiSegs = end($multiLegs)['segments']  ?? [];
            $lastSeg       = !empty($lastMultiSegs) ? end($lastMultiSegs) : [];
        }
    
        $deptHour = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
        $arrHour  = (int) substr($lastSeg['arriveTime']  ?? '00:00', 0, 2);
    
        if (!empty($firstSeg['departDT'])) {
            $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
        }
    
        $validatingCode    = $fi['ValidatingAirlineCode'] ?? '';
        $validatingAirline = $airlines->get($validatingCode);
    
        // ── 7. Fare breakdown ─────────────────────────────────────────────────────
        $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) {
            return [
                'passengerType' => $fb['PassengerTypeQuantity']['Code'],
                'qty'           => (int)   $fb['PassengerTypeQuantity']['Quantity'],
                'baseFare'      => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                'totalFare'     => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                'currency'      => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
                'baggage'       => $fb['Baggage']      ?? [],
                'cabinBaggage'  => $fb['CabinBaggage'] ?? [],
                'taxes'         => $fb['PassengerFare']['Taxes'] ?? [],
                'serviceTax'    => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                'surcharges'    => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
                'changeAllowed' => $fb['PenaltyDetails']['ChangeAllowed']       ?? false,
                'changePenalty' => $fb['PenaltyDetails']['ChangePenaltyAmount'] ?? '0.00',
                'refundAllowed' => $fb['PenaltyDetails']['RefundAllowed']       ?? false,
                'refundPenalty' => $fb['PenaltyDetails']['RefundPenaltyAmount'] ?? '0.00',
            ];
        })->values()->toArray();
    
        // ── 8. Assemble mapped flight ─────────────────────────────────────────────
        $mappedFlight = [
            'fareSourceCode'         => $fareInfo['FareSourceCode'],
            'airline'                => $firstSeg['airline']     ?? '',
            'airlineCode'            => $firstSeg['airlineCode'] ?? '',
            'airlineLogo'            => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
            'validatingCode'         => $validatingCode,
            'validatingAirline'      => $validatingAirline['AirLineName'] ?? $validatingCode,
            'validatingLogo'         => $validatingAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
            'cabin'                  => $firstSeg['cabin']     ?? '',
            'cabinCode'              => $firstSeg['cabinCode'] ?? 'Y',
            'stops'                  => $totalStops,
            'price'                  => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
            'baseFare'               => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
            'totalTax'               => (float) ($fareInfo['ItinTotalFares']['TotalTax']['Amount'] ?? 0),
            'currency'               => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
            'isRefundable'           => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
            'fareType'               => $fareInfo['FareType']             ?? 'Public',
            'ticketType'             => $fi['TicketType']                 ?? 'eTicket',
            'isPassportMandatory'    => (bool) ($fi['IsPassportMandatory'] ?? false),
            'directionInd'           => $tripType               ?? '',
            'ticketAdvisory'         => trim($fi['TicketAdvisory']        ?? ''),
            'segments'               => $segments,
            'departTime'             => $firstSeg['departTime'] ?? '',
            'arriveTime'             => $lastSeg['arriveTime']  ?? '',
            'departDT'               => $firstSeg['departDT']   ?? '',
            'arriveDT'               => $lastSeg['arriveDT']    ?? '',
            'totalDuration'          => $totalMins,
            'durationLabel'          => $fmtMins($totalMins),
            'layoverDurations'       => $layoverDurations,
            'departDateLabel'        => $departDateLabel,
            'totalTimeMins'          => $totalTimeMins,
            'totalTimeLabel'         => $fmtMins($totalTimeMins),
            'returnSegments'         => $returnSegments,
            'returnStops'            => $returnStops,
            'returnDurationLabel'    => $returnDurationLabel,
            'returnDateLabel'        => $returnDateLabel,
            'returnLayoverDurations' => $returnLayoverDurations,
            'returnTotalTimeMins'    => $returnTotalTimeMins,
            'returnTotalTimeLabel'   => $returnTotalTimeLabel,
            'multiLegs'              => $multiLegs,
            'departSlot'             => $deptHour < 12 ? 'morning' : ($deptHour < 18 ? 'afternoon' : 'evening'),
            'arrivalSlot'            => $arrHour  < 12 ? 'morning' : ($arrHour  < 18 ? 'afternoon' : 'evening'),
            'fareBreakdown'          => $breakdown,
        ];
        //dd($revalidateData); 
        $payload1 = [
            'session_id'       => $validated['session_id'],
            'fare_source_code' => $revalidateData['AirRevalidateResponse']['AirRevalidateResult']['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['FareSourceCode'],
        ];

        // ── 9. Fetch extra services & fare rules ──────────────────────────────────
        $extraResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/extra_services', $payload1);
    
        if ($extraResponse->failed()) {
            return back()->withErrors(['error' => 'Extra services fetch failed.']);
        }
    
        $fareRulesResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/fare_rules', $payload1);
    
        if ($fareRulesResponse->failed()) {
            return back()->withErrors(['error' => 'Fare rules fetch failed.']);
        }
    
        //dd($extraResponse->json());
        // ── 10. Store in session & redirect ──────────────────────────────────────
        session([
            'bookingFlight'       => $mappedFlight,
            'bookingSessionId'    => $validated['session_id'],
            'bookingSearchParams' => $searchParams,
            'extraServices'       => $extraResponse->json(),
            'fareRules'           => $fareRulesResponse->json(),
            'tripType'            => $mappedFlight['directionInd'] ?? 'N/A',
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
            'fare_source_code'                    => 'required|string',
            'session_id'                          => 'required|string',
            'contact.email'                       => 'required|email',
            'contact.phone'                       => 'required|string|min:7',
            'contact.area_code'                   => 'required|string',
            'contact.country_code'                => 'required|string',
            'passengers'                          => 'required|array|min:1',
            'passengers.*.type'                   => 'required|in:ADT,CHD,INF',
            'passengers.*.title'                  => 'required|in:Mr,Mrs,Ms,Miss,Dr,Master',
            'passengers.*.first_name'             => 'required|string|max:100',
            'passengers.*.last_name'              => 'required|string|max:100',
            'passengers.*.gender'                 => 'required|in:M,F',
            'passengers.*.dob'                    => 'required|date',
            'passengers.*.nationality'            => 'required|string|size:2',
            'passengers.*.passport_no'            => 'nullable|string|max:20',
            'passengers.*.passport_issue_country' => 'nullable|string|size:2',
            'passengers.*.passport_issue_date'    => 'nullable|date',
            'passengers.*.passport_exp'           => 'nullable|date|after:today',
            'passengers.*.frequent_flyer_number' => 'nullable|string|max:30',
            'extra_baggage'                       => 'nullable|array',
            'extra_meal'                          => 'nullable|array',
        ]);

        // Persist passenger + contact to session for downstream payment steps
        session([
            'bookingContact'    => $validated['contact'],
            'bookingPassengers' => $validated['passengers'],
            'extraBaggage'      => $request->input('extra_baggage', []),
            'extraMeal'         => $request->input('extra_meal', []),
        ]);

        // ── Collect selected extra services ────────────────────────────────
        $selectedExtras = $this->_collectSelectedExtras($request);
        session(['selectedExtras' => $selectedExtras]);

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
        $fareType      = strtolower($mappedFlight['fareType'] ?? 'public');

        // ── WebFare: go to payment FIRST, then book ───────────────────────────
        if ($fareType === 'webfare') {
            return redirect()->route('flights.payment.gateway');
        }

        // ── Public / Private: book now (hold), then collect payment ───────────
        $result = $this->_callBookApi($validated, $request);
       
        if ($result['error']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        $apiResponse  = $result['data'];
        $bookResult   = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
        $success      = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uniqueId     = $bookResult['UniqueID']     ?? '';
        $tktTimeLimit = $bookResult['TktTimeLimit'] ?? '';

        if (! $success || empty($uniqueId)) {
            $errMsg = data_get($bookResult, 'Errors.0.Errors.ErrorMessage')
                   ?? data_get($bookResult, 'Errors.ErrorMessage')
                   ?? 'Booking failed. Please try again.';
            return back()->withErrors(['error' => $errMsg]);
        }

        $dbBooking = $this->_persistBooking($mappedFlight, $validated, $apiResponse, [
            'unique_id'      => $uniqueId,
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => $tktTimeLimit ?: null,
            'extra_services_snapshot' => session('selectedExtras', []),
        ]);

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId'     => $uniqueId,           // API hold/ticket ref — NOT shown as booking ref
            'bookingRef'          => $dbBooking->booking_ref, // OUR internal booking reference
            'bookingTktTimeLimit' => $tktTimeLimit,
            'bookingStatus'       => $bookResult['Status'] ?? '',
            'flightBookingDbId'   => $dbBooking->id,
        ]);

        $this->_sendPendingEmail($dbBooking, 'hold');

        return redirect()->route('flights.payment.options');
    }

    // =========================================================================
    //  paymentGateway() — WebFare simulated payment page
    // =========================================================================
    public function paymentGateway()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
        $extraServices = session('extraServices', []);
        $selectedExtras = session('selectedExtras', []);

        // Calculate extras total for display
        $extrasTotal = 0.0;
        if (!empty($selectedExtras)) {
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
            'flight'           => $mappedFlight,
            'contact'          => session('bookingContact', []),
            'selectedExtras'   => $selectedExtras,
            'extraServices'    => $extraServices,
            'extrasTotal'      => $extrasTotal,
        ]);
    }

    // =========================================================================
    //  processGatewayPayment() — WebFare: simulate payment → call book API
    // =========================================================================
    public function processGatewayPayment(Request $request)
    {
        $contact    = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);

        if (empty($contact) || empty($passengers)) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired. Please start over.']);
        }

        $validatedData = [
            'fare_source_code' => session('bookingFlight.flight.fareSourceCode', session('bookingFlight.fareSourceCode', '')),
            'session_id'       => session('bookingSessionId', ''),
            'contact'          => $contact,
            'passengers'       => $passengers,
        ];

        $result = $this->_callBookApi($validatedData, $request);

        if ($result['error']) {
            return redirect()->route('flights.payment.gateway')->withErrors(['error' => $result['message']]);
        }

        $apiResponse = $result['data'];
        $bookResult  = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
        $success     = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $uniqueId    = $bookResult['UniqueID'] ?? '';

        if (! $success || empty($uniqueId)) {
            $errMsg = data_get($bookResult, 'Errors.0.Errors.ErrorMessage')
                   ?? data_get($bookResult, 'Errors.ErrorMessage')
                   ?? 'Booking failed after payment. Please contact support.';
            return redirect()->route('flights.payment.gateway')->withErrors(['error' => $errMsg]);
        }

        $mappedFlight = session('bookingFlight.flight') ?? session('bookingFlight', []);
        $dbBooking    = $this->_persistBooking($mappedFlight, $validatedData, $apiResponse, [
            'unique_id'      => $uniqueId,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'gateway',
            'extra_services_snapshot' => session('selectedExtras', []),
        ]);

        $this->_sendConfirmedEmail($dbBooking);

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId'     => $uniqueId,           // API e-ticket ref
            'bookingRef'          => $dbBooking->booking_ref, // OUR internal booking reference
            'bookingStatus'       => $bookResult['Status'] ?? 'CONFIRMED',
            'flightBookingDbId'   => $dbBooking->id,
            'paymentMethod'       => 'gateway',
        ]);

        return redirect()->route('flights.confirmation');
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
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
        $bookingRef    = session('bookingRef', '');

        return view('livewire.pages.flight.flight-payment-options', [
            'flight'       => $mappedFlight,
            'uniqueId'     => session('bookingUniqueId'),
            'bookingRef'   => $bookingRef,
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'contact'      => session('bookingContact', []),
            'passengers'   => session('bookingPassengers', []),
            'dbId'         => session('flightBookingDbId'),
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
        $request->validate([
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $dbId     = session('flightBookingDbId');
        $uniqueId = session('bookingUniqueId');

        if (! $uniqueId) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        if ($dbId && $dbBooking = FlightBooking::find($dbId)) {
            $dbBooking->update([
                'payment_method'            => 'bank_transfer',
                'payment_status'            => 'awaiting_bank_transfer',
                'bank_transfer_reference'   => $request->input('payment_reference'),
                'bank_transfer_notified_at' => now(),
            ]);
            $this->_sendPendingEmail($dbBooking, 'bank_transfer');
        }

        session(['paymentMethod' => 'bank_transfer']);

        return redirect()->route('flights.pending');
    }

    // =========================================================================
    //  processTicketPayment() — Gateway on payment options → simulate + ticket_order
    // =========================================================================
    public function processTicketPayment(Request $request)
    {
        $uniqueId = session('bookingUniqueId');
        $dbId     = session('flightBookingDbId');

        if (! $uniqueId) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }

        $ticketResult = $this->_callTicketOrderApi($uniqueId);

        // Even if ticketing fails we keep the response for the confirmation page
        $ticketResponse = $ticketResult['data'];
        $ticketResult2  = $ticketResponse['AirOrderTicketRS']['TicketOrderResult'] ?? [];
        $ticketSuccess  = filter_var($ticketResult2['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($dbId && $dbBooking = FlightBooking::find($dbId)) {
            $dbBooking->update([
                'payment_method'      => 'gateway',
                'payment_status'      => $ticketSuccess ? 'paid'     : 'failed',
                'booking_status'      => $ticketSuccess ? 'ticketed'  : 'on_hold',
                'ticket_ordered'      => $ticketSuccess,
                'ticket_ordered_at'   => $ticketSuccess ? now() : null,
                'ticket_api_response' => $ticketResponse,
            ]);

            if ($ticketSuccess) $this->_sendConfirmedEmail($dbBooking);
        }

        session([
            'ticketOrderResult' => $ticketResponse,
            'ticketSuccess'     => $ticketSuccess,
            'paymentMethod'     => 'gateway',
            // bookingRef already in session from book() — just ensure uniqueId is the e-ticket ref
            'bookingUniqueId'   => $ticketSuccess
                ? (data_get($ticketResponse, 'AirOrderTicketRS.TicketOrderResult.UniqueID', session('bookingUniqueId', '')) ?: session('bookingUniqueId', ''))
                : session('bookingUniqueId', ''),
        ]);

        return redirect()->route('flights.confirmation');
    }

   
 
    // =========================================================================
    //  travelFlexBankTransfer() — User clicks "I have made payment" on TravelFlex
    // =========================================================================
    public function travelFlexBankTransfer(Request $request)
    {
        $request->validate([
            'down_payment'      => 'required|numeric|min:1',
            'down_percent'      => 'required|integer|between:30,90',
            'repayment_plan'    => 'required|string',
            'grand_total'       => 'required|numeric',
            'total_interest'    => 'required|numeric',
            'schedule_json'     => 'required|string',
            'payment_reference' => 'nullable|string|max:100',
        ]);
 
        $schedule = json_decode($request->input('schedule_json', '[]'), true) ?: [];
 
        $tfPlan = [
            'down_payment'      => (float) $request->input('down_payment'),
            'down_percent'      => (int)   $request->input('down_percent'),
            'repayment_plan'    => $request->input('repayment_plan'),
            'grand_total'       => (float) $request->input('grand_total'),
            'total_interest'    => (float) $request->input('total_interest'),
            'schedule'          => $schedule,
            'payment_method'    => 'bank_transfer',
        ];
 
        session(['travelFlexPlan' => $tfPlan]);
 
        // ── Update DB record if it exists (from the hold booking) ─────────────
        $dbId = session('flightBookingDbId');
        if ($dbId && $dbBooking = \App\Models\FlightBooking::find($dbId)) {
            $dbBooking->update([
                'payment_method'            => 'flex_bank_transfer',
                'payment_status'            => 'awaiting_bank_transfer',
                'bank_transfer_reference'   => $request->input('payment_reference'),
                'bank_transfer_notified_at' => now(),
                'extra_services_snapshot'   => session('selectedExtras', []),
            ]);
            // Send pending email
            $this->_sendPendingEmail($dbBooking, 'bank_transfer');
        } else {
            // No existing DB record yet (booking hasn't been called yet for non-LCC)
            // Persist a new one
            $bookingFlight = session('bookingFlight', []);
            $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
            $contact       = session('bookingContact', []);
            $passengers    = session('bookingPassengers', []);
 
            $dbBooking = $this->_persistBooking($mappedFlight, [
                'contact'    => $contact,
                'passengers' => $passengers,
            ], [], [
                'unique_id'                 => session('bookingUniqueId', ''),
                'booking_status'            => 'on_hold',
                'payment_method'            => 'flex_bank_transfer',
                'payment_status'            => 'awaiting_bank_transfer',
                'bank_transfer_reference'   => $request->input('payment_reference'),
                'bank_transfer_notified_at' => now(),
                'tkt_time_limit'            => session('bookingTktTimeLimit'),
                'extra_services_snapshot'   => session('selectedExtras', []),
            ]);
 
            session(['flightBookingDbId' => $dbBooking->id, 'bookingRef' => $dbBooking->booking_ref]);
            $this->_sendPendingEmail($dbBooking, 'bank_transfer');
        }
 
        session(['paymentMethod' => 'flex_bank_transfer']);
 
        return redirect()->route('flights.travelflex.pending');
    }
 
    // =========================================================================
    //  travelFlexGateway() — Simulate payment → book API → TravelFlex confirmation
    // =========================================================================
    public function travelFlexGateway(Request $request)
    {
        $request->validate([
            'down_payment'   => 'required|numeric|min:1',
            'down_percent'   => 'required|integer|between:30,90',
            'repayment_plan' => 'required|string',
            'grand_total'    => 'required|numeric',
            'total_interest' => 'required|numeric',
            'schedule_json'  => 'required|string',
        ]);
 
        $schedule = json_decode($request->input('schedule_json', '[]'), true) ?: [];
 
        $tfPlan = [
            'down_payment'   => (float) $request->input('down_payment'),
            'down_percent'   => (int)   $request->input('down_percent'),
            'repayment_plan' => $request->input('repayment_plan'),
            'grand_total'    => (float) $request->input('grand_total'),
            'total_interest' => (float) $request->input('total_interest'),
            'schedule'       => $schedule,
            'payment_method' => 'gateway',
        ];
 
        session(['travelFlexPlan' => $tfPlan]);
 
        $contact    = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);
 
        if (empty($contact) || empty($passengers)) {
            return redirect()->route('flights.travelflex')->withErrors(['error' => 'Session expired. Please start over.']);
        }
 
        $validatedData = [
            'fare_source_code' => session('bookingFlight.flight.fareSourceCode', session('bookingFlight.fareSourceCode', '')),
            'session_id'       => session('bookingSessionId', ''),
            'contact'          => $contact,
            'passengers'       => $passengers,
        ];
 
        // ── Check if already booked on hold (Public/Private) ─────────────────
        $existingUniqueId = session('bookingUniqueId', '');
        $dbId             = session('flightBookingDbId');
 
        if ($existingUniqueId) {
            // Already on hold — call ticket_order instead of book
            $ticketResult = $this->_callTicketOrderApi($existingUniqueId);
            $ticketResponse = $ticketResult['data'];
            $ticketSuccess  = filter_var(
                ($ticketResponse['AirOrderTicketRS']['TicketOrderResult']['Success'] ?? false),
                FILTER_VALIDATE_BOOLEAN
            );
 
            if ($dbId && $dbBooking = \App\Models\FlightBooking::find($dbId)) {
                $dbBooking->update([
                    'payment_method'      => 'flex_gateway',
                    'payment_status'      => $ticketSuccess ? 'paid'     : 'failed',
                    'booking_status'      => $ticketSuccess ? 'ticketed' : 'on_hold',
                    'ticket_ordered'      => $ticketSuccess,
                    'ticket_ordered_at'   => $ticketSuccess ? now() : null,
                    'ticket_api_response' => $ticketResponse,
                ]);
                if ($ticketSuccess) $this->_sendConfirmedEmail($dbBooking);
            }
 
        } else {
            // WebFare — call book API now
            $result = $this->_callBookApi($validatedData, $request);
 
            if ($result['error']) {
                return redirect()->route('flights.travelflex')->withErrors(['error' => $result['message']]);
            }
 
            $apiResponse = $result['data'];
            $bookResult  = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
            $success     = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $uniqueId    = $bookResult['UniqueID'] ?? '';
 
            if (! $success || empty($uniqueId)) {
                $errMsg = data_get($bookResult, 'Errors.0.Errors.ErrorMessage')
                       ?? data_get($bookResult, 'Errors.ErrorMessage')
                       ?? 'Booking failed. Please contact support.';
                return redirect()->route('flights.travelflex')->withErrors(['error' => $errMsg]);
            }
 
            $bookingFlight = session('bookingFlight', []);
            $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
 
            $dbBooking = $this->_persistBooking($mappedFlight, $validatedData, $apiResponse, [
                'unique_id'      => $uniqueId,
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'flex_gateway',
                'extra_services_snapshot' => session('selectedExtras', []),
            ]);
 
            $this->_sendConfirmedEmail($dbBooking);
 
            session([
                'bookingConfirmation' => $apiResponse,
                'bookingUniqueId'     => $uniqueId,           // API ref
                'bookingRef'          => $dbBooking->booking_ref, // OUR ref
                'bookingStatus'       => $bookResult['Status'] ?? 'CONFIRMED',
                'flightBookingDbId'   => $dbBooking->id,
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
 
        $dbId      = session('flightBookingDbId');
        $dbBooking = $dbId ? \App\Models\FlightBooking::find($dbId) : null;

        return view('livewire.pages.flight.flight-travelflex-pending', [
            'bookingRef' => session('bookingRef', $dbBooking?->booking_ref ?? ''),
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
 
        $uniqueId    = session('bookingUniqueId', '');
        $dbId        = session('flightBookingDbId');
        $dbBooking   = $dbId ? \App\Models\FlightBooking::find($dbId) : null;
 
        // ── Fetch live trip details after successful gateway payment ──────────
        $tripDetails = [];
        if ($uniqueId) {
            $tripDetails = $this->_callTripDetailsApi($uniqueId);
        }
 
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
 
        return view('livewire.pages.flight.flight-travelflex-confirmation', [
            'flight'      => $mappedFlight,
            'dbBooking'   => $dbBooking,
            'tripDetails' => $tripDetails,   // ← live API data
            'uniqueId'    => $uniqueId,      // API e-ticket ref
            'bookingRef'  => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'contact'     => session('bookingContact', []),
            'passengers'  => session('bookingPassengers', []),
        ]);
    }
 

    // =========================================================================
    //  pending() — Bank transfer: awaiting manual confirmation
    // =========================================================================
    public function pending()
    {
        $dbId      = session('flightBookingDbId');
        $dbBooking = $dbId ? FlightBooking::find($dbId) : null;

        if (! $dbBooking && ! session()->has('bookingUniqueId')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No booking found.']);
        }

        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;

        return view('livewire.pages.flight.flight-pending', [
            'flight'       => $mappedFlight,
            'dbBooking'    => $dbBooking,
            'uniqueId'     => session('bookingUniqueId'),     // API hold ref
            'bookingRef'   => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'contact'      => session('bookingContact', []),
            'passengers'   => session('bookingPassengers', []),
        ]);
    }

     // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    private function _callBookApi(array $validated, Request $request): array
    {
        $bookingFlight  = session('bookingFlight', []);
        $mappedFlight   = $bookingFlight['flight'] ?? $bookingFlight;
        $fareSourceCode = $mappedFlight['fareSourceCode'] ?? $validated['fare_source_code'];
        $isPassportMand = $mappedFlight['isPassportMandatory'] ?? false;
        $fareType       = $mappedFlight['fareType'] ?? 'Public';
        $contact        = $validated['contact'];

        $passengers = collect($validated['passengers']);
        $adults   = $passengers->where('type', 'ADT')->values();
        $children = $passengers->where('type', 'CHD')->values();
        $infants  = $passengers->where('type', 'INF')->values();

        $buildPaxGroup = function ($list): array {
            $g = [
                'title'       => $list->pluck('title')->toArray(),
                'firstName'   => $list->pluck('first_name')->toArray(),
                'lastName'    => $list->pluck('last_name')->toArray(),
                'dob'         => $list->pluck('dob')->toArray(),
                'nationality' => $list->pluck('nationality')->toArray(),
            ];
            if (array_filter($list->pluck('passport_no')->toArray()))
                $g['passportNo'] = $list->pluck('passport_no')->toArray();
            if (array_filter($list->pluck('passport_issue_country')->toArray()))
                $g['passportIssueCountry'] = $list->pluck('passport_issue_country')->toArray();
            if (array_filter($list->pluck('passport_issue_date')->toArray()))
                $g['passportIssueDate'] = $list->pluck('passport_issue_date')->toArray();
            if (array_filter($list->pluck('passport_exp')->toArray()))
                $g['passportExpiryDate'] = $list->pluck('passport_exp')->toArray();
            // ── NEW: Frequent Flyer ───────────────────────────────────────────
            if (array_filter($list->pluck('frequent_flyer_number')->toArray()))
                $g['frequentFlyrNum'] = $list->pluck('frequent_flyer_number')->toArray();
            // Extra services
            $baggageOut = session('extraBaggage.outbound', []);
            $baggageIn  = session('extraBaggage.inbound', []);
            if (!empty($baggageOut)) $g['ExtraServiceOutbound'] = array_fill(0, $list->count(), $baggageOut);
            if (!empty($baggageIn))  $g['ExtraServiceInbound']  = array_fill(0, $list->count(), $baggageIn);
            return $g;
        };

        $paxDetails = [[]];
        if ($adults->isNotEmpty())   $paxDetails[0]['adult']  = $buildPaxGroup($adults);
        if ($children->isNotEmpty()) $paxDetails[0]['child']  = $buildPaxGroup($children);
        if ($infants->isNotEmpty())  $paxDetails[0]['infant'] = $buildPaxGroup($infants);

        $payload = [
            'flightBookingInfo' => [
                'flight_session_id'   => $validated['session_id'] ?? session('bookingSessionId'),
                'fare_source_code'    => $fareSourceCode,
                'IsPassportMandatory' => $isPassportMand ? 'true' : 'false',
                'fareType'            => $fareType,
                'areaCode'            => $contact['area_code'],
                'countryCode'         => $contact['country_code'],
            ],
            'paxInfo' => [
                'customerEmail' => $contact['email'],
                'customerPhone' => $contact['phone'],
                'paxDetails'    => $paxDetails,
            ],
        ];
        try {
            $response = Http::timeout(90)->post('https://travelnext.works/api/aeroVE5/booking', $payload);
            if ($response->failed()) return ['error' => true, 'message' => 'Booking request failed. Please try again.', 'data' => []];
            return ['error' => false, 'message' => '', 'data' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('FlightBooking API error', ['message' => $e->getMessage()]);
            return ['error' => true, 'message' => 'A network error occurred. Please try again.', 'data' => []];
        }
    }

    private function _callTicketOrderApi(string $uniqueId): array
    {
        $payload = [
            'user_id'       => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access'        => config('services.travelnext.access'),
            'ip_address'    => config('services.travelnext.ip'),
            'UniqueID'      => $uniqueId,
        ];

        try {
            $response = Http::timeout(60)->post('https://travelnext.works/api/aeroVE5/ticket_order', $payload);
            if ($response->failed()) return ['error' => true, 'message' => 'Ticket order request failed.', 'data' => []];
            $data   = $response->json();
            $result = $data['AirOrderTicketRS']['TicketOrderResult'] ?? [];
            $ok     = filter_var($result['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $ok) {
                $errMsg = data_get($result, 'Errors.Error.ErrorMessage') ?? data_get($result, 'Errors.ErrorMessage') ?? 'Ticket order failed.';
                return ['error' => true, 'message' => $errMsg, 'data' => $data];
            }
            return ['error' => false, 'message' => '', 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('TicketOrder API error', ['message' => $e->getMessage()]);
            return ['error' => true, 'message' => 'A network error occurred during ticketing.', 'data' => []];
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
            $ref = 'TW-' . strtoupper(substr(base_convert(bin2hex(random_bytes(5)), 16, 36), 0, 8));
        } while (FlightBooking::where('booking_ref', $ref)->exists());

        return $ref;
    }

    private function _persistBooking(array $mappedFlight, array $validated, array $apiResponse, array $overrides = []): FlightBooking
    {
        $segments   = $mappedFlight['segments'] ?? [];
        $firstSeg   = $segments[0] ?? [];
        $lastSeg    = !empty($segments) ? end($segments) : [];
        $contact    = $validated['contact']    ?? session('bookingContact', []);
        $passengers = $validated['passengers'] ?? session('bookingPassengers', []);

        $tktRaw = $overrides['tkt_time_limit'] ?? null;
        unset($overrides['tkt_time_limit']);

        // unique_id from the API (hold/ticket reference) — NOT our booking ref
        // We always generate our own booking_ref for customer-facing reference.
        $bookingRef = $this->_generateBookingRef();

        return FlightBooking::create(array_merge([
            'booking_ref'          => $bookingRef,
            'fare_source_code'     => $mappedFlight['fareSourceCode'] ?? '',
            'session_id'           => session('bookingSessionId', ''),
            'fare_type'            => $mappedFlight['fareType']   ?? 'Public',
            'trip_type'            => session('tripType', ''),
            'route'                => ($firstSeg['from'] ?? '') . ' → ' . ($lastSeg['to'] ?? ''),
            'airline'              => $mappedFlight['airline']    ?? '',
            'cabin'                => $mappedFlight['cabin']      ?? '',
            'currency'             => $mappedFlight['currency']   ?? 'NGN',
            'total_price'          => $mappedFlight['price']      ?? 0,
            'contact_email'        => $contact['email']  ?? '',
            'contact_phone'        => $contact['phone']  ?? '',
            'adult_count'          => collect($passengers)->where('type', 'ADT')->count(),
            'child_count'          => collect($passengers)->where('type', 'CHD')->count(),
            'infant_count'         => collect($passengers)->where('type', 'INF')->count(),
            'tkt_time_limit'       => $tktRaw ? \Carbon\Carbon::parse($tktRaw) : null,
            'booking_api_response' => $apiResponse,
            'passengers_snapshot'  => $passengers,
            'flight_snapshot'      => $mappedFlight,
        ], $overrides));
    }

    private function _sendConfirmedEmail(FlightBooking $booking, array $tripDetails = []): void
    {
        if ($booking->confirmation_email_sent) {
            Log::info('_sendConfirmedEmail: skipped (already sent)', [
                'booking_id'  => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);
            return;
        }

        if (empty($booking->contact_email)) {
            Log::warning('_sendConfirmedEmail: skipped (missing contact_email)', [
                'booking_id'  => $booking->id,
                'booking_ref' => $booking->booking_ref,
            ]);
            return;
        }

        Log::info('_sendConfirmedEmail: start', [
            'booking_id'       => $booking->id,
            'booking_ref'      => $booking->booking_ref,
            'recipient'        => $booking->contact_email,
            'mail_default'     => config('mail.default'),
            'mail_from'        => config('mail.from.address'),
            'has_trip_details' => !empty($tripDetails),
            'has_unique_id'    => !empty($booking->unique_id),
        ]);
 
        // Fetch trip details from the API if the caller didn't supply them.
        // _callTripDetailsApi() already handles errors gracefully (returns []).
        if (empty($tripDetails) && !empty($booking->unique_id)) {
            Log::info('_sendConfirmedEmail: fetching trip details', [
                'booking_ref' => $booking->booking_ref,
                'unique_id'   => $booking->unique_id,
            ]);
            $tripDetails = $this->_callTripDetailsApi($booking->unique_id);

            Log::info('_sendConfirmedEmail: trip details fetched', [
                'booking_ref'       => $booking->booking_ref,
                'trip_details_keys' => array_keys($tripDetails),
            ]);
        }
 
        try {
            Log::info('_sendConfirmedEmail: sending ETicketMail', [
                'booking_ref' => $booking->booking_ref,
                'recipient'   => $booking->contact_email,
            ]);

            //dd($tripDetails['ItineraryInfo']['ReservationItems'][0]['ReservationItem']['AirlinePNR']);
           
            Mail::to($booking->contact_email)->send(
                new ETicketMail($booking, $tripDetails)
            );

            $booking->update(['confirmation_email_sent' => true]);

            Log::info('_sendConfirmedEmail: mail sent successfully', [
                'booking_id'  => $booking->id,
                'booking_ref' => $booking->booking_ref,
                'recipient'   => $booking->contact_email,
            ]);
        } catch (\Throwable $e) {
            Log::error('_sendConfirmedEmail: failed to send ETicketMail', [
                'booking_id'  => $booking->id,
                'booking_ref' => $booking->booking_ref,
                'recipient'   => $booking->contact_email,
                'error'       => $e->getMessage(),
                'exception'   => get_class($e),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }

    private function _sendPendingEmail(FlightBooking $booking, string $method = 'bank_transfer'): void
    {
        try {
            \Log::info('Attempting to send pending email', [
                'booking_id' => $booking->id,
                'email' => $booking->contact_email,
                'method' => $method
            ]);
    
            Mail::to($booking->contact_email)
                ->send(new BookingPendingMail($booking, $method));
    
            \Log::info('Pending email sent successfully', [
                'booking_id' => $booking->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send pending email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function _primeBookingSession(FlightBooking $booking): void
    {
        $flight = $booking->flight_snapshot ?? [];
        $contact = [
            'email' => $booking->contact_email,
            'phone' => $booking->contact_phone,
        ];
        $passengers = $booking->passengers_snapshot ?? [];

        session([
            'bookingFlight'      => ['flight' => $flight],
            'bookingContact'     => $contact,
            'bookingPassengers'  => $passengers,
            'bookingUniqueId'    => $booking->unique_id,
            'bookingRef'         => $booking->booking_ref,
            'bookingTktTimeLimit'=> optional($booking->tkt_time_limit)->toIso8601String(),
            'bookingStatus'      => strtoupper((string) $booking->booking_status),
            'flightBookingDbId'  => $booking->id,
            'tripType'           => $booking->trip_type,
        ]);
    }

    private function _callTripDetailsApi(string $uniqueId): array
    {
        $payload = [
            'user_id'       => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access'        => config('services.travelnext.access'),
            'ip_address'    => config('services.travelnext.ip'),
            'UniqueID'      => $uniqueId,
        ];
    
        try {
            $response = Http::timeout(30)->post('https://travelnext.works/api/aeroVE5/trip_details', $payload);
            if ($response->failed()) return [];
            $data    = $response->json();
            $result  = $data['TripDetailsResponse']['TripDetailsResult'] ?? [];
            $success = filter_var($result['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$success) return [];
            
            $tripData = $result['TravelItinerary'] ?? [];
            //dd($tripData['ItineraryInfo']['ReservationItems'][0]['ReservationItem']['AirlinePNR']);
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
            $customerInfos = collect(data_get($tripData, 'ItineraryInfo.CustomerInfos', []))
                ->map(fn($c) => $c['CustomerInfo'] ?? $c);
            
            $resItems = collect(data_get($tripData, 'ItineraryInfo.ReservationItems', []))
                ->map(fn($r) => $r['ReservationItem'] ?? $r);
            
            $itinPricing = data_get($tripData, 'ItineraryInfo.ItineraryPricing', []);
            
            $alertData = [
                'uniqueId'      => $uniqueId,
                'bookingStatus' => $tripData['BookingStatus'] ?? 'UNKNOWN',
                'ticketStatus'  => $tripData['TicketStatus'] ?? 'UNKNOWN',
                'origin'        => $tripData['Origin'] ?? '',
                'destination'   => $tripData['Destination'] ?? '',
                'fareType'      => $tripData['FareType'] ?? '',
                'passengers'    => $customerInfos->toArray(),
                'flights'       => $resItems->toArray(),
                'pricing'       => $itinPricing,
                'timestamp'     => now(),
            ];
            
            Mail::to(config('mail.support_address', 'support@travelwheel.com'))
                ->send(new \App\Mail\UnTicketedConfirmationAlert($alertData));
        } catch (\Throwable $e) {
            Log::error('UnTicketedConfirmationAlert failed', ['error' => $e->getMessage()]);
        }
    }
 
    // =========================================================================
    //  Updated confirmation() — now also fetches trip details for ticketed bookings
    // =========================================================================
    public function confirmation()
    {
        if (! session()->has('bookingConfirmation') && ! session()->has('bookingUniqueId')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No booking found.']);
        }
 
        $dbId      = session('flightBookingDbId');
        $dbBooking = $dbId ? \App\Models\FlightBooking::find($dbId) : null;
 
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
 
        $uniqueId     = session('bookingUniqueId', '');
        $paymentMethod= session('paymentMethod', 'gateway');
 
        // ── Fetch live trip details if booking is ticketed ────────────────────
        $tripDetails  = [];
        $isTicketed   = $dbBooking?->isTicketed()
            || in_array($paymentMethod, ['gateway', 'flex_gateway'])
            || (session('ticketSuccess') === true);
 
        if ($isTicketed && $uniqueId) {
            $tripDetails = $this->_callTripDetailsApi($uniqueId);
        }
 
        return view('livewire.pages.flight.flight-confirmation', [
            'flight'            => $mappedFlight,
            'bookingResult'     => session('bookingConfirmation', []),
            'ticketOrderResult' => session('ticketOrderResult', []),
            'ticketSuccess'     => session('ticketSuccess', false),
            'uniqueId'          => $uniqueId,                         // API e-ticket / hold ref
            'bookingRef'        => session('bookingRef', $dbBooking?->booking_ref ?? ''), // OUR ref
            'tktTimeLimit'      => session('bookingTktTimeLimit'),
            'bookingStatus'     => session('bookingStatus', 'CONFIRMED'),
            'paymentMethod'     => $paymentMethod,
            'dbBooking'         => $dbBooking,
            'contact'           => session('bookingContact', []),
            'passengers'        => session('bookingPassengers', []),
            'tripDetails'       => $tripDetails,   // ← NEW: live trip details from API
        ]);
    }
 
    // =========================================================================
    //  travelFlex() — Updated: block if ticket is NOT refundable
    // =========================================================================
    public function travelFlex()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }
 
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
 
        // ── Refundable check ──────────────────────────────────────────────────
        $isRefundable = $mappedFlight['isRefundable'] ?? false;
        if (! $isRefundable) {
            return redirect()->route('flights.payment.options')
                ->withErrors(['flex_error' => 'TravelFlex is only available for refundable tickets. This fare is non-refundable.']);
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
                'down_payment'   => 'required|numeric|min:1',
                'down_percent'   => 'required|integer|between:30,90',
                'repayment_plan' => 'required|string',
                'grand_total'    => 'required|numeric',
                'total_interest' => 'required|numeric',
                'schedule_json'  => 'required|string',
                'pay_method'     => 'required|in:bank_transfer,gateway',
            ]);
 
            $schedule = json_decode($request->input('schedule_json', '[]'), true) ?: [];
 
            session(['travelFlexPlan' => [
                'down_payment'   => (float) $request->input('down_payment'),
                'down_percent'   => (int)   $request->input('down_percent'),
                'repayment_plan' => $request->input('repayment_plan'),
                'grand_total'    => (float) $request->input('grand_total'),
                'total_interest' => (float) $request->input('total_interest'),
                'schedule'       => $schedule,
                'payment_method' => $request->input('pay_method'),
            ]]);
        }
 
        if (! session()->has('travelFlexPlan')) {
            return redirect()->route('flights.travelflex');
        }
 
        return view('livewire.pages.flight.flight-travelflex-application');
    }
 
    // =========================================================================
    //  travelFlexSubmitApplication() — Validate + upload docs + send emails + pay
    // =========================================================================
    public function travelFlexSubmitApplication(Request $request)
    {
        $validated = $request->validate([
            'full_name'         => 'required|string|max:200',
            'home_address'      => 'required|string|max:500',
            'email'             => 'required|email',
            'bvn'               => 'required|string|size:11|regex:/^\d{11}$/',
            'employer_name'     => 'required|string|max:200',
            'employer_address'  => 'required|string|max:500',
            'occupation'        => 'required|string|max:150',
            'job_description'   => 'required|string|max:1000',
            'staff_number'      => 'required|string|max:50',
            // Documents
            'valid_id'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'passport_photo'    => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'work_id_card'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'employment_letter' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_statements'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            // Plan hidden fields
            'down_payment'      => 'required|numeric',
            'down_percent'      => 'required|integer',
            'repayment_plan'    => 'required|string',
            'grand_total'       => 'required|numeric',
            'total_interest'    => 'required|numeric',
            'schedule_json'     => 'required|string',
            'pay_method'        => 'required|in:bank_transfer,gateway',
        ], [
            'bvn.size'  => 'BVN must be exactly 11 digits.',
            'bvn.regex' => 'BVN must contain only numbers.',
        ]);
 
        // ── Store uploaded documents ──────────────────────────────────────────
        $docKeys   = ['valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements'];
        $uploadPaths = [];
        $storagePaths = [];
 
        foreach ($docKeys as $key) {
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('travelflex_docs', 'local');
                $storagePaths[$key] = $path;
                $uploadPaths[$key]  = storage_path('app/' . $path);
            }
        }
 
        // ── Update plan in session ────────────────────────────────────────────
        $schedule = json_decode($request->input('schedule_json', '[]'), true) ?: [];
        $tfPlan = [
            'down_payment'   => (float) $request->input('down_payment'),
            'down_percent'   => (int)   $request->input('down_percent'),
            'repayment_plan' => $request->input('repayment_plan'),
            'grand_total'    => (float) $request->input('grand_total'),
            'total_interest' => (float) $request->input('total_interest'),
            'schedule'       => $schedule,
            'payment_method' => $request->input('pay_method'),
        ];
        session(['travelFlexPlan' => $tfPlan]);
 
        $applicant = [
            'full_name'        => $validated['full_name'],
            'email'            => $validated['email'],
            'home_address'     => $validated['home_address'],
            'bvn'              => $validated['bvn'],
            'employer_name'    => $validated['employer_name'],
            'employer_address' => $validated['employer_address'],
            'occupation'       => $validated['occupation'],
            'job_description'  => $validated['job_description'],
            'staff_number'     => $validated['staff_number'],
        ];
 
        session(['travelFlexApplicant' => $applicant, 'travelFlexDocPaths' => $storagePaths]);
 
        // ── Now branch on payment method ──────────────────────────────────────
        $payMethod = $request->input('pay_method');
 
        if ($payMethod === 'bank_transfer') {
            // Send application emails
            $this->_sendTravelFlexApplicationEmails($applicant, $tfPlan, $uploadPaths);
            return redirect()->route('flights.travelflex.bank-transfer-form');
        }
 
        // Gateway: simulate payment → book → send emails → redirect to confirmation
        return redirect()->route('flights.travelflex.gateway-process');
    }
 
    // =========================================================================
    //  travelFlexBankTransferForm() — Show bank details after application submitted
    // =========================================================================
    public function travelFlexBankTransferForm()
    {
        return redirect()->route('flights.travelflex.bank-transfer');
    }
 
    // =========================================================================
    //  travelFlexGatewayProcess() — Process gateway payment + book + email + confirm
    // =========================================================================
    public function travelFlexGatewayProcess(Request $request)
    {
        $contact    = session('bookingContact', []);
        $passengers = session('bookingPassengers', []);
        $applicant  = session('travelFlexApplicant', []);
        $tfPlan     = session('travelFlexPlan', []);
        $docPaths   = session('travelFlexDocPaths', []);
 
        if (empty($contact) || empty($passengers) || empty($tfPlan)) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'Session expired.']);
        }
 
        $validatedData = [
            'fare_source_code' => session('bookingFlight.flight.fareSourceCode', session('bookingFlight.fareSourceCode', '')),
            'session_id'       => session('bookingSessionId', ''),
            'contact'          => $contact,
            'passengers'       => $passengers,
        ];
 
        $existingUniqueId = session('bookingUniqueId', '');
        $dbId             = session('flightBookingDbId');
 
        if ($existingUniqueId) {
            // Already on hold — ticket it
            $ticketResult   = $this->_callTicketOrderApi($existingUniqueId);
            $ticketResponse = $ticketResult['data'];
            $ticketSuccess  = filter_var(
                ($ticketResponse['AirOrderTicketRS']['TicketOrderResult']['Success'] ?? false),
                FILTER_VALIDATE_BOOLEAN
            );
 
            if ($dbId && $dbBooking = \App\Models\FlightBooking::find($dbId)) {
                $dbBooking->update([
                    'payment_method'      => 'flex_gateway',
                    'payment_status'      => $ticketSuccess ? 'paid'     : 'failed',
                    'booking_status'      => $ticketSuccess ? 'ticketed' : 'on_hold',
                    'ticket_ordered'      => $ticketSuccess,
                    'ticket_ordered_at'   => $ticketSuccess ? now() : null,
                    'ticket_api_response' => $ticketResponse,
                ]);
                if ($ticketSuccess) $this->_sendConfirmedEmail($dbBooking);
            }
 
            session(['ticketOrderResult' => $ticketResponse, 'ticketSuccess' => $ticketSuccess]);
 
        } else {
            // WebFare — call book API
            $result = $this->_callBookApi($validatedData, $request);
 
            if ($result['error']) {
                return redirect()->route('flights.travelflex.confirmation')
                    ->withErrors(['error' => $result['message']]);
            }
 
            $apiResponse = $result['data'];
            $bookResult  = $apiResponse['BookFlightResponse']['BookFlightResult'] ?? [];
            $success     = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $uniqueId    = $bookResult['UniqueID'] ?? '';
 
            if ($success && $uniqueId) {
                $bookingFlight = session('bookingFlight', []);
                $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
 
                $dbBooking = $this->_persistBooking($mappedFlight, $validatedData, $apiResponse, [
                    'unique_id'      => $uniqueId,
                    'booking_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_method' => 'flex_gateway',
                    'extra_services_snapshot' => session('selectedExtras', []),
                ]);
 
                $this->_sendConfirmedEmail($dbBooking);
 
                session([
                    'bookingConfirmation' => $apiResponse,
                    'bookingUniqueId'     => $uniqueId,           // API ref
                    'bookingRef'          => $dbBooking->booking_ref, // OUR ref
                    'bookingStatus'       => $bookResult['Status'] ?? 'CONFIRMED',
                    'flightBookingDbId'   => $dbBooking->id,
                ]);
            }
        }
 
        // ── Send TravelFlex application emails ────────────────────────────────
        $bookingFlight = session('bookingFlight', []);
        $mappedFlight  = $bookingFlight['flight'] ?? $bookingFlight;
        $uploadPaths   = array_map(
            fn($p) => $p ? storage_path('app/' . $p) : null,
            $docPaths
        );
        $this->_sendTravelFlexApplicationEmails($applicant, $tfPlan, $uploadPaths, session('bookingUniqueId', ''));
 
        session(['paymentMethod' => 'flex_gateway']);
 
        return redirect()->route('flights.travelflex.confirmation');
    }
 
    // ── Private: send TravelFlex application emails ───────────────────────────
    private function _sendTravelFlexApplicationEmails(
        array  $applicant,
        array  $tfPlan,
        array  $uploadPaths,
        string $bookingRef = ''
     ): void {
        $bookingFlight = session('bookingFlight', []);
        $flightInfo    = $bookingFlight['flight'] ?? $bookingFlight;
 
        $mail = new \App\Mail\TravelFlexApplicationMail($applicant, $tfPlan, $flightInfo, $uploadPaths, $bookingRef);
 
        try {
            // To loan provider + CC to Travelwheel
            \Illuminate\Support\Facades\Mail::to(config('mail.travelflex_provider', 'loans@travelwheel.com'))
                ->cc(config('mail.travelwheel_address', 'support@travelwheel.com'))
                ->send($mail);
        } catch (\Throwable $e) {
            Log::error('TravelFlexApplicationMail failed', ['error' => $e->getMessage()]);
        }
    }

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

        $collected = [
            'baggage' => [],
            'meal' => [],
            'total_amount' => 0,
            'currency' => 'USD',
        ];

        // ── Process selected baggage ──────────────────────────────────────
        foreach ($extraBaggage as $direction => $items) {
            foreach ($items as $serviceId => $quantity) {
                if ($quantity <= 0) continue;

                // Find matching baggage service from API data
                foreach ($dynBaggage as $bag) {
                    $bagDir = str_contains(strtoupper($bag['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                    if ($bagDir === $direction) {
                        foreach (($bag['Services'][0] ?? []) as $svc) {
                            if ((string)$svc['ServiceId'] === (string)$serviceId) {
                                $price = (float)($svc['ServiceCost']['Amount'] ?? 0);
                                $currency = $svc['ServiceCost']['CurrencyCode'] ?? 'USD';
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
                    if (!$checked) continue;

                    // Find matching meal service from API data
                    foreach ($dynMeal as $meal) {
                        $mealDir = str_contains(strtoupper($meal['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                        if ($mealDir === $direction) {
                            foreach (($meal['Services'][$segmentIndex] ?? []) as $svc) {
                                if ((string)$svc['ServiceId'] === (string)$serviceId) {
                                    $price = (float)($svc['ServiceCost']['Amount'] ?? 0);
                                    $currency = $svc['ServiceCost']['CurrencyCode'] ?? 'USD';
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


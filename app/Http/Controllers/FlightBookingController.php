<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class FlightBookingController extends Controller
{
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
        //dd($payload);
        // ── 1️⃣ Revalidate ─────────────────────────────
        $revalidateResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/revalidate', $payload);

        // ❌ HTTP failure
        if ($revalidateResponse->failed()) {
            return back()->withErrors([
                'error' => 'Revalidation failed. Please try again.'
            ]);
        }

        $revalidateData = $revalidateResponse->json();
        //dd($revalidateData);
        // ✅ Check API logical response
        $isValid = data_get($revalidateData, 'AirRevalidateResponse.AirRevalidateResult.IsValid');

        if (!$isValid) {
            return back()->withErrors([
                'error' => 'This fare is no longer available or has changed. Please select another flight.'
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
        
        // ── Reference data (same files used in search()) ─────────────────────────────
        $airlines = collect(
            json_decode(file_get_contents(public_path('assets/data/airline.json')), true)
        )->keyBy('AirLineCode');
        
        $airports = collect(
            json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true)
        )->keyBy('AirportCode');
        
        // ── Recover trip context from session so we know how to slice legs ────────────
        // search() stored the validated search params in session under 'searchParamsStore'.
        $searchParams = session('searchParamsStore', []);
        $tripType     = strtolower($searchParams['trip'] ?? 'oneway'); // oneway | return | multi
        $searchLegs   = $searchParams['multi_legs'] ?? [];
        
        // ── Helpers (identical to the ones in search()) ───────────────────────────────
        
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
        
                $opCode    = $fs['OperatingAirline']['Code'] ?? $airlineCode;
                $opAirline = $airlines->get($opCode);
        
                return [
                    // ── Route ─────────────────────────────────────────────────────────
                    'from'        => $fromCode,
                    'to'          => $toCode,
                    'fromCity'    => $fromAirport
                                        ? ($fromAirport['City'] . ' (' . $fromCode . ')')
                                        : $fromCode,
                    'toCity'      => $toAirport
                                        ? ($toAirport['City'] . ' (' . $toCode . ')')
                                        : $toCode,
                    'fromAirport' => $fromAirport['AirportName'] ?? $fromCode,
                    'toAirport'   => $toAirport['AirportName']   ?? $toCode,
                    'fromCountry' => $fromAirport['Country']     ?? '',
                    'toCountry'   => $toAirport['Country']       ?? '',
                    'fromLat'     => $fromAirport['Latitude']    ?? null,
                    'fromLon'     => $fromAirport['Longitude']   ?? null,
                    'toLat'       => $toAirport['Latitude']      ?? null,
                    'toLon'       => $toAirport['Longitude']     ?? null,
        
                    // ── Times ─────────────────────────────────────────────────────────
                    'departTime'  => $dep->format('H:i'),
                    'arriveTime'  => $arr->format('H:i'),
                    'departDate'  => $dep->format('D, d M Y'),
                    'arriveDate'  => $arr->format('D, d M Y'),
                    'departDT'    => $fs['DepartureDateTime'],
                    'arriveDT'    => $fs['ArrivalDateTime'],
                    'duration'    => (int) $fs['JourneyDuration'],
        
                    // ── Flight ────────────────────────────────────────────────────────
                    'flightNo'    => $airlineCode . $fs['FlightNumber'],
                    'airline'     => $fs['MarketingAirlineName'],
                    'airlineCode' => $airlineCode,
                    'airlineLogo' => $airline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
        
                    // ── Aircraft ──────────────────────────────────────────────────────
                    'equipment'   => $fs['OperatingAirline']['Equipment'] ?? '',
        
                    // ── Cabin ─────────────────────────────────────────────────────────
                    'cabin'       => $fs['CabinClassText'] ?? '',
                    'cabinCode'   => $fs['CabinClassCode'] ?? 'Y',
        
                    // ── Booking class & meal ──────────────────────────────────────────
                    'resBookCode' => $seg['ResBookDesigCode'] ?? '',
                    'mealCode'    => $fs['MealCode']          ?? '',
        
                    // ── Availability ──────────────────────────────────────────────────
                    'seatsLeft'    => (int)  ($seg['SeatsRemaining']['Number']       ?? 9),
                    'belowMinimum' => (bool) ($seg['SeatsRemaining']['BelowMinimum'] ?? false),
        
                    // ── Codeshare ─────────────────────────────────────────────────────
                    'isCodeshare'       => $opCode !== $airlineCode,
                    'operatingCode'     => $opCode,
                    'operatingAirline'  => $fs['OperatingAirline']['Name']         ?? '',
                    'operatingFlightNo' => $opCode . ($fs['OperatingAirline']['FlightNumber'] ?? ''),
                    'operatingLogo'     => $opAirline['AirLineLogo']               ?? '/assets/img/airlines/default.png',
        
                    // ── e-Ticket ──────────────────────────────────────────────────────
                    'eticket'     => (bool) ($fs['Eticket'] ?? true),
                ];
            })->values()->toArray();
        };
        
        $calcLayovers = function (array $segments): array {
            $durations = [];
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $arrive      = \Carbon\Carbon::parse($segments[$i]['arriveDT']);
                $depart      = \Carbon\Carbon::parse($segments[$i + 1]['departDT']);
                $mins        = $arrive->diffInMinutes($depart);
                $durations[] = floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
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
        
        $fmtMins = fn(int $mins): string => floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
        
        // Splits a flat segment list into per-leg arrays for multi-city trips.
        // Identical logic to search() — finds the cut point by matching the search
        // leg's destination IATA code against the segment 'to' field.
        $splitMultiLegs = function (array $allSegments, array $searchLegs): array {
            if (empty($searchLegs)) {
                return array_map(fn($s) => [$s], $allSegments);
            }
        
            $legs      = [];
            $remaining = $allSegments;
        
            foreach ($searchLegs as $legIdx => $legDef) {
                $extractIata = function (string $val): string {
                    if (preg_match('/\(([A-Z]{3})\)/', $val, $m)) return $m[1];
                    return strtoupper(trim($val));
                };
        
                $destIata = $extractIata($legDef['to'] ?? '');
        
                if ($legIdx === count($searchLegs) - 1) {
                    $legs[] = $remaining;
                    break;
                }
        
                $cutAt = -1;
                foreach ($remaining as $si => $seg) {
                    if (strtoupper($seg['to']) === $destIata) {
                        $cutAt = $si;
                        break;
                    }
                }
        
                $legs[] = $cutAt === -1
                    ? array_splice($remaining, 0, 1)
                    : array_splice($remaining, 0, $cutAt + 1);
            }
        
            if (!empty($remaining)) {
                $legs[] = $remaining;
            }
        
            return $legs;
        };
        
        // ── Map the single FareItinerary ─────────────────────────────────────────────
        
        $fareInfo = $fi['AirItineraryFareInfo'];
        $odos     = $fi['OriginDestinationOptions'] ?? [];
        
        // Per-flight defaults — same variable names as search()
        $segments               = [];
        $totalStops             = 0;
        $totalMins              = 0;
        $totalTimeMins          = 0;
        $returnSegments         = [];
        $returnStops            = 0;
        $returnDurationLabel    = '';
        $returnTotalTimeMins    = 0;
        $returnTotalTimeLabel   = '';
        $returnDateLabel        = '';
        $returnLayoverDurations = [];
        $multiLegs              = [];
        $layoverDurations       = [];
        $departDateLabel        = '';
        
        // ── ONE WAY ───────────────────────────────────────────────────────────────────
        if ($tripType === 'oneway') {
            $odo0             = $odos[0]['OriginDestinationOption'] ?? [];
            $segments         = $mapSegments($odo0);
            $totalStops       = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $totalMins        = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins    = $totalMins + $calcLayoverMins($segments);
        
        // ── RETURN ────────────────────────────────────────────────────────────────────
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
        
        // ── MULTI-CITY ────────────────────────────────────────────────────────────────
        } elseif ($tripType === 'multi') {
            $odo0       = $odos[0]['OriginDestinationOption'] ?? [];
            $allSegs    = $mapSegments($odo0);
            $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
            $legArrays  = $splitMultiLegs($allSegs, $searchLegs);
        
            $segments         = $legArrays[0] ?? [];
            $totalMins        = array_sum(array_column($segments, 'duration'));
            $layoverDurations = $calcLayovers($segments);
            $totalTimeMins    = $totalMins + $calcLayoverMins($segments);
        
            foreach (array_slice($legArrays, 1) as $legSegs) {
                $legMins          = array_sum(array_column($legSegs, 'duration'));
                $legLayoverMins   = $calcLayoverMins($legSegs);
                $legTotalTimeMins = $legMins + $legLayoverMins;
        
                $multiLegs[] = [
                    'segments'         => $legSegs,
                    'durationLabel'    => $fmtMins($legMins),
                    'stops'            => max(0, count($legSegs) - 1),
                    'layoverDurations' => $calcLayovers($legSegs),
                    'departDateLabel'  => !empty($legSegs[0]['departDT'])
                                            ? \Carbon\Carbon::parse($legSegs[0]['departDT'])->format('D, d M')
                                            : '',
                    'totalTimeMins'    => $legTotalTimeMins,
                    'totalTimeLabel'   => $fmtMins($legTotalTimeMins),
                ];
            }
        }
        
        // ── Shared shortcuts ──────────────────────────────────────────────────────────
        $firstSeg = $segments[0]               ?? [];
        $lastSeg  = !empty($segments) ? end($segments) : [];
        
        $deptHour = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
        $arrHour  = (int) substr($lastSeg['arriveTime']  ?? '00:00', 0, 2);
        
        if (!empty($firstSeg['departDT'])) {
            $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
        }
        
        $validatingCode    = $fi['ValidatingAirlineCode'] ?? '';
        $validatingAirline = $airlines->get($validatingCode);
        
        // ── Fare breakdown ────────────────────────────────────────────────────────────
        $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) {
            return [
                'passengerType' => $fb['PassengerTypeQuantity']['Code'],
                'qty'           => (int)   $fb['PassengerTypeQuantity']['Quantity'],
                'baseFare'      => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                'totalFare'     => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                'currency'      => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
        
                'baggage'      => $fb['Baggage']      ?? [],
                'cabinBaggage' => $fb['CabinBaggage'] ?? [],
                'taxes'        => $fb['PassengerFare']['Taxes'] ?? [],
        
                'serviceTax'  => (float) ($fb['PassengerFare']['ServiceTax']['Amount'] ?? 0),
                'surcharges'  => (float) ($fb['PassengerFare']['Surcharges']['Amount'] ?? 0),
        
                'changeAllowed' => $fb['PenaltyDetails']['ChangeAllowed']       ?? false,
                'changePenalty' => $fb['PenaltyDetails']['ChangePenaltyAmount'] ?? '0.00',
                'refundAllowed' => $fb['PenaltyDetails']['RefundAllowed']       ?? false,
                'refundPenalty' => $fb['PenaltyDetails']['RefundPenaltyAmount'] ?? '0.00',
            ];
        })->values()->toArray();
        
        // ── Assemble the normalised flight object ─────────────────────────────────────
        // Shape is identical to what search() produces, so the booking page blade
        // can consume it without any changes.
        $mappedFlight = [
            'fareSourceCode' => $fareInfo['FareSourceCode'],
        
            // ── Airline ───────────────────────────────────────────────────────────────
            'airline'     => $firstSeg['airline']     ?? '',
            'airlineCode' => $firstSeg['airlineCode'] ?? '',
            'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
        
            // ── Validating airline ────────────────────────────────────────────────────
            'validatingCode'    => $validatingCode,
            'validatingAirline' => $validatingAirline['AirLineName'] ?? $validatingCode,
            'validatingLogo'    => $validatingAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
        
            // ── Flight meta ───────────────────────────────────────────────────────────
            'cabin'     => $firstSeg['cabin']     ?? '',
            'cabinCode' => $firstSeg['cabinCode'] ?? 'Y',
            'stops'     => $totalStops,
        
            // ── Pricing ───────────────────────────────────────────────────────────────
            // NOTE: revalidate may return an updated price — always use this value,
            // not the one cached from search results.
            'price'    => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
            'baseFare' => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
            'totalTax' => (float) ($fareInfo['ItinTotalFares']['TotalTax']['Amount'] ?? 0),
            'currency' => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
        
            // ── Policies ──────────────────────────────────────────────────────────────
            'isRefundable'        => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
            'fareType'            => $fareInfo['FareType']             ?? 'Public',
            'ticketType'          => $fi['TicketType']                 ?? 'eTicket',
            'isPassportMandatory' => (bool) ($fi['IsPassportMandatory'] ?? false),
            'directionInd'        => $fi['DirectionInd']               ?? '',
            'ticketAdvisory'      => trim($fi['TicketAdvisory']        ?? ''),
        
            // ── Outbound ──────────────────────────────────────────────────────────────
            'segments'         => $segments,
            'departTime'       => $firstSeg['departTime'] ?? '',
            'arriveTime'       => $lastSeg['arriveTime']  ?? '',
            'departDT'         => $firstSeg['departDT']   ?? '',
            'arriveDT'         => $lastSeg['arriveDT']    ?? '',
            'totalDuration'    => $totalMins,
            'durationLabel'    => $fmtMins($totalMins),
            'layoverDurations' => $layoverDurations,
            'departDateLabel'  => $departDateLabel,
            'totalTimeMins'    => $totalTimeMins,
            'totalTimeLabel'   => $fmtMins($totalTimeMins),
        
            // ── Return inbound ────────────────────────────────────────────────────────
            'returnSegments'         => $returnSegments,
            'returnStops'            => $returnStops,
            'returnDurationLabel'    => $returnDurationLabel,
            'returnDateLabel'        => $returnDateLabel,
            'returnLayoverDurations' => $returnLayoverDurations,
            'returnTotalTimeMins'    => $returnTotalTimeMins,
            'returnTotalTimeLabel'   => $returnTotalTimeLabel,
        
            // ── Multi-city extra legs ─────────────────────────────────────────────────
            'multiLegs' => $multiLegs,
        
            // ── Sidebar filter slots ──────────────────────────────────────────────────
            'departSlot'  => $deptHour < 12 ? 'morning' : ($deptHour < 18 ? 'afternoon' : 'evening'),
            'arrivalSlot' => $arrHour  < 12 ? 'morning' : ($arrHour  < 18 ? 'afternoon' : 'evening'),
        
            'fareBreakdown' => $breakdown,
        ];
        
        
        $tripType = $mappedFlight['directionInd'] ?? 'N/A';
        //dd($mappedFlight);    

        // ── 2️⃣ Extra Services ─────────────────────────
        $extraResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/extra_services', $payload);

        if ($extraResponse->failed()) {
            return back()->withErrors(['error' => 'Extra services fetch failed.']);
        }

        $extraServicesData = $extraResponse->json();
        //dd($extraServicesData);

        // ── 3️⃣ Fare Rules ─────────────────────────────
        $fareRulesResponse = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/fare_rules', $payload);

        if ($fareRulesResponse->failed()) {
            return back()->withErrors(['error' => 'Fare rules fetch failed.']);
        }

        $fareRulesData = $fareRulesResponse->json();
        // ── Store in session for the booking Livewire component ───────────────────────
        session([
            'bookingFlight'    => [
                'flight'       => $mappedFlight,       // your existing mapped flight data
                'revalidate'   => $revalidateData,   // nested under revalidate key
                'segments'     => $mappedFlight['segments'],   // your existing mapped segments
                'fareBreakdown'=> $mappedFlight['fareBreakdown'],  // your existing mapped breakdown
                // ... all your other mapped fields
            ],
            'bookingSessionId'    => $validated['session_id'],
            'bookingSearchParams' => $searchParams,
            'extraServices'    => $extraServicesData,
            'fareRules'        => $fareRulesData,
            'tripType'         => $tripType, // e.g. 'oneway', 'return', 'multi'
        ]);
        

        // Plain redirect — no ->with([...]) flash needed

        return redirect()->route('flights.booking');
    }

    public function booking()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')
                ->withErrors(['error' => 'No flight selected. Please search again.']);
        }

        return view('livewire.pages.flight.flight-booking');
    }

    public function book(Request $request)
    {
        $validated = $request->validate([
            'fare_source_code'          => 'required|string',
            'session_id'                => 'required|string',
            'contact.email'             => 'required|email',
            'contact.phone'             => 'required|string|min:7',
            'passengers'                => 'required|array|min:1',
            'passengers.*.type'         => 'required|in:ADT,CHD,INF',
            'passengers.*.title'        => 'required|in:Mr,Mrs,Ms,Miss,Dr',
            'passengers.*.first_name'   => 'required|string|max:100',
            'passengers.*.last_name'    => 'required|string|max:100',
            'passengers.*.dob'          => 'required|date',
            'passengers.*.nationality'  => 'required|string|size:2',
            'passengers.*.passport_no'  => 'nullable|string|max:20',
            'passengers.*.passport_exp' => 'nullable|date|after:today',
        ]);

        // ── TODO: wire up TravelNext booking API here ─────────────────────────
        // $response = Http::timeout(60)->post('https://travelnext.works/api/aeroVE5/book', [
        //     'user_id'        => config('services.travelnext.user_id'),
        //     'user_password'  => config('services.travelnext.password'),
        //     'access'         => config('services.travelnext.access'),
        //     'ip_address'     => config('services.travelnext.ip'),
        //     'FareSourceCode' => $validated['fare_source_code'],
        //     'session_id'     => $validated['session_id'],
        //     'passengers'     => $validated['passengers'],
        //     'contact'        => $validated['contact'],
        // ]);
        // if ($response->failed()) {
        //     return back()->withErrors(['error' => 'Booking failed. Please try again.']);
        // }
        // session(['bookingConfirmation' => $response->json()]);
        // return redirect()->route('flights.confirmation');
        // ─────────────────────────────────────────────────────────────────────

        return back()->with('stub_notice',
            'Booking API not yet connected. Fare: ' . substr($validated['fare_source_code'], 0, 20) . '...'
        );
    }
}
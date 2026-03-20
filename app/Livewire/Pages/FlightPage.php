<?php

namespace App\Livewire\Pages;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use Livewire\Component;

class FlightPage extends Component
{
    public function render()
    {
        return view('livewire.pages.flight.flight-page-result');
    }

    public function search(Request $request)
    {
        //dd($request->all());
        // Normalize trip type
        $request->merge([
            'trip' => strtolower($request->trip)
        ]);
    
        // Decode multi legs if JSON string
        if ($request->multi_legs && is_string($request->multi_legs)) {
            $request->merge([
                'multi_legs' => json_decode($request->multi_legs, true)
            ]);
        }
    
        // Remove empty legs
        if (!empty($request->multi_legs)) {
            $legs = array_filter($request->multi_legs, function ($leg) {
                return !empty($leg['from']) && !empty($leg['to']) && !empty($leg['depart']);
            });
            $request->merge([
                'multi_legs' => array_values($legs)
            ]);
        }
    
        $rules = [
            'trip'        => 'required|in:oneway,return,multi',
            'adults'      => 'required|integer|min:1|max:9',
            'childs'      => 'nullable|integer|min:0|max:9',
            'kids'        => 'nullable|integer|min:0|max:9',
            'flight_type' => 'required|in:Y,S,C,F',
        ];
    
        if ($request->trip !== 'multi') {
            $rules['from']   = 'required|string|max:255';
            $rules['to']     = 'required|string|max:255';
            $rules['depart'] = 'required|date_format:d/m/Y';
        }
    
        if ($request->trip === 'return') {
            $rules['returning'] = 'required|date_format:d/m/Y|after_or_equal:depart';
        }
    
        if ($request->trip === 'multi') {
            $rules['multi_legs']          = 'required|array|min:1';
            $rules['multi_legs.*.from']   = 'required|string|max:255';
            $rules['multi_legs.*.to']     = 'required|string|max:255';
            $rules['multi_legs.*.depart'] = 'required|date_format:d/m/Y';
        }
        //dd($request->all());
    
        $validated = $request->validate($rules);
        
        $originDestination = [];
        $journeyType = match ($request->trip) {
            'oneway' => 'OneWay',
            'return' => 'Return',
            'multi'  => 'MultiCity',
            default  => 'OneWay'
        };
        $fromCode = Str::between($request->from, '(', ')');
        $toCode   = Str::between($request->to, '(', ')');  

        // 🟢 ONE WAY
        if ($validated['trip'] === 'oneway') {
            $originDestination[] = [
                "departureDate" => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                "airportOriginCode" => $fromCode,
                "airportDestinationCode" => $toCode,
            ];
        }

        // 🟡 RETURN
        elseif ($validated['trip'] === 'return') {
            $originDestination[] = [
                "departureDate" => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                "returnDate" => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['returning'])->format('Y-m-d'),
                "airportOriginCode" => $fromCode,
                "airportDestinationCode" => $toCode, 
            ];

            
        } 

        // 🔵 MULTI CITY
        elseif ($validated['trip'] === 'multi') {
            foreach ($validated['multi_legs'] as $leg) {
                $fromCode = Str::between($leg['from'], '(', ')');
                $toCode   = Str::between($leg['to'], '(', ')'); 
                $originDestination[] = [
                    "departureDate" => \Carbon\Carbon::createFromFormat('d/m/Y', $leg['depart'])->format('Y-m-d'),
                    "airportOriginCode" => $fromCode,
                    "airportDestinationCode" => $toCode,
                ];
            }
        }

        

        $payload = [
            "user_id" => "travelwheel_testAPI",
            "user_password" => "travelwheelTest@2025",
            "access" => "Test",
            "ip_address" => "102.88.115.201",

            "requiredCurrency" => "NGN",
            "journeyType" => $journeyType,

            "OriginDestinationInfo" => $originDestination,

            "class" => $this->mapCabin($validated['flight_type']),
            "adults" => (int) $validated['adults'],
            "childs" => (int) ($validated['childs'] ?? 0),
            "infants" => (int) ($validated['kids'] ?? 0),
        ];

        //dd($payload);

        $response = Http::timeout(60)
        ->post('https://travelnext.works/api/aeroVE5/availability', $payload);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Flight API failed']);
        }

        $data = $response->json();
        //dd($data);  
        
    
        $jsonData = $data;
        //dd($jsonData);
    
        $jsonAirlinesPath = public_path('assets/data/airline.json');
        $airlines = collect(json_decode(file_get_contents($jsonAirlinesPath), true))->keyBy('AirLineCode');
    

        $jsonAirportsPath = public_path('assets/data/airportsCode.json');
        $airports = collect(json_decode(file_get_contents($jsonAirportsPath), true))->keyBy('AirportCode');

        $tripType    = $request->trip;
        $searchLegs  = $request->multi_legs ?? [];   // the legs the user searched (for multi-city splitting)
    
        $itineraries = data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []);
    
        // ──────────────────────────────────────────────────────────────────────────
        // Helper: map a raw OriginDestinationOption array → clean segments array
        // ──────────────────────────────────────────────────────────────────────────
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

                return [
                    'from'        => $fromCode,
                    'to'          => $toCode,
                    'fromCity'    => $fromAirport['City'] . ' (' . $fromCode . ')' ?? $fromCode,
                    'toCity'      => $toAirport['City'] . ' (' . $toCode . ')' ?? $toCode,
                    'fromAirport' => $fromAirport
                                        ? $fromAirport['AirportName']
                                        : $fromCode,
                    'toAirport'   => $toAirport
                                        ? $toAirport['AirportName']
                                        : $toCode,
                    'departTime'  => $dep->format('H:i'),
                    'arriveTime'  => $arr->format('H:i'),
                    'departDT'    => $fs['DepartureDateTime'],
                    'arriveDT'    => $fs['ArrivalDateTime'],
                    'duration'    => (int) $fs['JourneyDuration'],
                    'flightNo'    => $airlineCode . $fs['FlightNumber'],
                    'airline'     => $fs['MarketingAirlineName'],
                    'airlineCode' => $airlineCode,
                    'airlineLogo' => $airline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'equipment'   => $fs['OperatingAirline']['Equipment'] ?? '',
                    'cabin'       => $fs['CabinClassText'],
                    'cabinCode'   => $fs['CabinClassCode'],
                    'seatsLeft'   => (int) ($seg['SeatsRemaining']['Number'] ?? 9),
                ];
            })->values()->toArray();
        };
    
        // ──────────────────────────────────────────────────────────────────────────
        // Helper: compute layover durations between consecutive segments
        // ──────────────────────────────────────────────────────────────────────────
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
    
        // ──────────────────────────────────────────────────────────────────────────
        // Helper: split a flat segments array into logical legs for multi-city.
        //
        // Multi-city API packs ALL segments into one ODO (e.g. AMS→LCY→DXB→LHR→AMS).
        // We split them by matching the user's requested leg destinations:
        //   leg 0: from=AMS to=DXB  →  segments until we arrive at DXB
        //   leg 1: from=DXB to=AMS  →  remaining segments
        //
        // Falls back to one-segment-per-leg if the destinations don't match cleanly.
        // ──────────────────────────────────────────────────────────────────────────
        $splitMultiLegs = function (array $allSegments, array $searchLegs): array {
            if (empty($searchLegs)) {
                // No leg info — treat every segment as its own leg
                return array_map(fn($s) => [$s], $allSegments);
            }
    
            $legs      = [];
            $remaining = $allSegments;
    
            foreach ($searchLegs as $legIdx => $legDef) {
                // Extract IATA code from strings like "Lagos (LOS)" or plain "LOS"
                $extractIata = function (string $val): string {
                    if (preg_match('/\(([A-Z]{3})\)/', $val, $m)) return $m[1];
                    return strtoupper(trim($val));
                };
    
                $destIata = $extractIata($legDef['to'] ?? '');
    
                // Last leg — all remaining segments belong here
                if ($legIdx === count($searchLegs) - 1) {
                    $legs[] = $remaining;
                    break;
                }
    
                // Find the segment that arrives at this leg's destination
                $cutAt = -1;
                foreach ($remaining as $si => $seg) {
                    if (strtoupper($seg['to']) === $destIata) {
                        $cutAt = $si;
                        break;
                    }
                }
    
                if ($cutAt === -1) {
                    // Destination not found — put one segment in this leg and continue
                    $legs[]    = array_splice($remaining, 0, 1);
                } else {
                    $legs[]    = array_splice($remaining, 0, $cutAt + 1);
                }
            }
    
            // Safety: if any segments are left over, append as an extra leg
            if (!empty($remaining)) {
                $legs[] = $remaining;
            }
    
            return $legs;
        };
        //dd($splitMultiLegs($mapSegments($itineraries[0]['FareItinerary']['OriginDestinationOptions']['OriginDestinationOption'] ?? []), $searchLegs));  
        // ──────────────────────────────────────────────────────────────────────────
        // Map itineraries → flights
        // ──────────────────────────────────────────────────────────────────────────
        $flights = collect($itineraries)->values()->map(
            function ($item, $index) use ($airlines, $tripType, $searchLegs, $mapSegments, $calcLayovers, $splitMultiLegs) {
    
            $fi       = $item['FareItinerary'];
            $fareInfo = $fi['AirItineraryFareInfo'];
            $odos     = $fi['OriginDestinationOptions'] ?? [];
    
            // ── DEFAULTS ──────────────────────────────────────────────────────────
            $segments               = [];
            $totalStops             = 0;
            $totalMins              = 0;
            $returnSegments         = [];
            $returnStops            = 0;
            $returnDurationLabel    = '';
            $returnDateLabel        = '';
            $returnLayoverDurations = [];
            $multiLegs              = [];
            $layoverDurations       = [];
            $departDateLabel        = '';
    
            // ─────────────────────────────────────────────────────────────────────
            // ONE WAY
            // Single ODO, all segments are outbound.
            // ─────────────────────────────────────────────────────────────────────
            if ($tripType === 'oneway') {
    
                $odo0       = $odos[0]['OriginDestinationOption'] ?? [];
                $segments   = $mapSegments($odo0);
                $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
                $totalMins  = array_sum(array_column($segments, 'duration'));
                $layoverDurations = $calcLayovers($segments);
    
            // ─────────────────────────────────────────────────────────────────────
            // RETURN
            // Two ODOs: first = outbound, second = inbound.
            // ─────────────────────────────────────────────────────────────────────
            } elseif ($tripType === 'return') {
    
                // Outbound
                $odo0       = $odos[0]['OriginDestinationOption'] ?? [];
                $segments   = $mapSegments($odo0);
                $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
                $totalMins  = array_sum(array_column($segments, 'duration'));
                $layoverDurations = $calcLayovers($segments);
    
                // Inbound (if present)
                if (!empty($odos[1])) {
                    $odo1                   = $odos[1]['OriginDestinationOption'] ?? [];
                    $returnSegments         = $mapSegments($odo1);
                    $returnStops            = (int) ($odos[1]['TotalStops'] ?? max(0, count($odo1) - 1));
                    $returnMins             = array_sum(array_column($returnSegments, 'duration'));
                    $returnDurationLabel    = floor($returnMins / 60) . 'h ' . ($returnMins % 60) . 'm';
                    $returnLayoverDurations = $calcLayovers($returnSegments);
    
                    if (!empty($returnSegments[0]['departDT'])) {
                        $returnDateLabel = \Carbon\Carbon::parse($returnSegments[0]['departDT'])->format('D, d M');
                    }
                }
    
            // ─────────────────────────────────────────────────────────────────────
            // MULTI-CITY
            // All segments are packed into ONE ODO.
            // We split them into logical legs using the user's search legs.
            // The first leg becomes the primary `segments` (shown in the card
            // summary row); the remaining legs go into `multiLegs[]` for the
            // expanded detail panel.
            // ─────────────────────────────────────────────────────────────────────
            } elseif ($tripType === 'multi') {
    
                $odo0       = $odos[0]['OriginDestinationOption'] ?? [];
                $allSegs    = $mapSegments($odo0);
                $totalStops = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
    
                // Split flat segment list into per-leg arrays
                $legArrays = $splitMultiLegs($allSegs, $searchLegs);
    
                // First leg → primary segments (card summary)
                $segments  = $legArrays[0] ?? [];
                $totalMins = array_sum(array_column($segments, 'duration'));
                $layoverDurations = $calcLayovers($segments);
    
                // Remaining legs → multiLegs (detail panel)
                foreach (array_slice($legArrays, 1) as $legSegs) {
                    $legMins   = array_sum(array_column($legSegs, 'duration'));
                    $multiLegs[] = [
                        'segments'         => $legSegs,
                        'durationLabel'    => floor($legMins / 60) . 'h ' . ($legMins % 60) . 'm',
                        'stops'            => max(0, count($legSegs) - 1),
                        'layoverDurations' => $calcLayovers($legSegs),
                        'departDateLabel'  => !empty($legSegs[0]['departDT'])
                                                ? \Carbon\Carbon::parse($legSegs[0]['departDT'])->format('D, d M')
                                                : '',
                    ];
                }
            }
    
            // ── Shared first/last segment shortcuts ───────────────────────────────
            $firstSeg = $segments[0]               ?? [];
            $lastSeg  = !empty($segments) ? end($segments) : [];
    
            $deptHour = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
            $arrHour  = (int) substr($lastSeg['arriveTime']  ?? '00:00', 0, 2);
    
            if (!empty($firstSeg['departDT'])) {
                $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
            }
    
            // ── FARE BREAKDOWN ────────────────────────────────────────────────────
            $breakdown = collect($fareInfo['FareBreakdown'] ?? [])->map(function ($fb) {
                return [
                    'passengerType' => $fb['PassengerTypeQuantity']['Code'],
                    'qty'           => (int)   $fb['PassengerTypeQuantity']['Quantity'],
                    'baseFare'      => (float) $fb['PassengerFare']['BaseFare']['Amount'],
                    'totalFare'     => (float) $fb['PassengerFare']['TotalFare']['Amount'],
                    'currency'      => $fb['PassengerFare']['TotalFare']['CurrencyCode'],
                    'baggage'       => $fb['Baggage'][0]      ?? '',
                    'cabinBaggage'  => $fb['CabinBaggage'][0] ?? '',
                    'changeAllowed' => $fb['PenaltyDetails']['ChangeAllowed']       ?? false,
                    'changePenalty' => $fb['PenaltyDetails']['ChangePenaltyAmount'] ?? '0.00',
                    'refundAllowed' => $fb['PenaltyDetails']['RefundAllowed']       ?? false,
                ];
            })->values()->toArray();
    
            // ── ASSEMBLE FLIGHT OBJECT ────────────────────────────────────────────
            return [
                'id'             => $index,
                'fareSourceCode' => $fareInfo['FareSourceCode'],
    
                'airline'     => $firstSeg['airline']    ?? '',
                'airlineCode' => $firstSeg['airlineCode'] ?? '',
                'airlineLogo' => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
    
                'cabin'     => $firstSeg['cabin']    ?? '',
                'cabinCode' => $firstSeg['cabinCode'] ?? 'Y',
                'stops'     => $totalStops,
    
                'price'    => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
                'baseFare' => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
                'currency' => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
    
                'isRefundable' => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
                'fareType'     => $fareInfo['FareType'] ?? 'Public',
    
                // ── Outbound / first leg ──
                'segments'         => $segments,
                'departTime'       => $firstSeg['departTime'] ?? '',
                'arriveTime'       => $lastSeg['arriveTime']  ?? '',
                'departDT'         => $firstSeg['departDT']   ?? '',
                'arriveDT'         => $lastSeg['arriveDT']    ?? '',
                'totalDuration'    => $totalMins,
                'durationLabel'    => floor($totalMins / 60) . 'h ' . ($totalMins % 60) . 'm',
                'layoverDurations' => $layoverDurations,
                'departDateLabel'  => $departDateLabel,
    
                // ── Return / inbound (Return trips only) ──
                'returnSegments'         => $returnSegments,
                'returnStops'            => $returnStops,
                'returnDurationLabel'    => $returnDurationLabel,
                'returnDateLabel'        => $returnDateLabel,
                'returnLayoverDurations' => $returnLayoverDurations,
    
                // ── Multi-city extra legs (legs 2…N) ──
                'multiLegs' => $multiLegs,
    
                // ── Sidebar filter slots ──
                'departSlot'  => $deptHour < 12 ? 'morning' : ($deptHour < 18 ? 'afternoon' : 'evening'),
                'arrivalSlot' => $arrHour  < 12 ? 'morning' : ($arrHour  < 18 ? 'afternoon' : 'evening'),
    
                'fareBreakdown' => $breakdown,
            ];
    
        })->values()->toArray();
        //dd($flights);
        // ✅ Store as plain array — Laravel serialises/deserialises automatically.
        return redirect()->route('air.flight-s')->with([
            'flightResults'   => $flights,
            'searchParams'    => $validated,
            'searchSessionId' => data_get($jsonData, 'AirSearchResponse.session_id', ''),
        ]);
    }

    

    private function mapCabin($code)
    {
        return match ($code) {
            'Y' => 'Economy',
            'S' => 'PremiumEconomy',
            'C' => 'Business',
            'F' => 'First',
            default => 'Economy'
        };
    }

    

}

        
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class FlightController extends Controller
{
    public function search(Request $request)
    {
        //dd($request->all());
        // ── Normalise trip type ───────────────────────────────────────────────
        $request->merge(['trip' => strtolower($request->trip)]);

        // ── Decode multi-city legs if sent as JSON string ─────────────────────
        if ($request->multi_legs && is_string($request->multi_legs)) {
            $request->merge([
                'multi_legs' => json_decode($request->multi_legs, true),
            ]);
        }

        // ── Strip incomplete legs ─────────────────────────────────────────────
        if (!empty($request->multi_legs)) {
            $request->merge([
                'multi_legs' => array_values(array_filter(
                    $request->multi_legs,
                    fn($leg) => !empty($leg['from']) && !empty($leg['to']) && !empty($leg['depart'])
                )),
            ]);
        }

        // ── Validation ────────────────────────────────────────────────────────
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

        $validated = $request->validate($rules);

        // ── Build origin-destination payload ──────────────────────────────────
        $originDestination = [];
        $journeyType = match ($request->trip) {
            'oneway' => 'OneWay',
            'return' => 'Return',
            'multi'  => 'Circle',
            default  => 'OneWay',
        };

        $fromCode = Str::between($request->from ?? '', '(', ')');
        $toCode   = Str::between($request->to   ?? '', '(', ')');

        if ($validated['trip'] === 'oneway') {
            $originDestination[] = [
                'departureDate'          => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                'airportOriginCode'      => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($validated['trip'] === 'return') {
            $originDestination[] = [
                'departureDate'          => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['depart'])->format('Y-m-d'),
                'returnDate'             => \Carbon\Carbon::createFromFormat('d/m/Y', $validated['returning'])->format('Y-m-d'),
                'airportOriginCode'      => $fromCode,
                'airportDestinationCode' => $toCode,
            ];
        } elseif ($validated['trip'] === 'multi') {
            foreach ($validated['multi_legs'] as $leg) {
                $lFrom = Str::between($leg['from'], '(', ')');
                $lTo   = Str::between($leg['to'],   '(', ')');
                $originDestination[] = [
                    'departureDate'          => \Carbon\Carbon::createFromFormat('d/m/Y', $leg['depart'])->format('Y-m-d'),
                    'airportOriginCode'      => $lFrom,
                    'airportDestinationCode' => $lTo,
                ];
            }
        }

        // ── API call ──────────────────────────────────────────────────────────
        $payload = [
            'user_id'       => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access'        => config('services.travelnext.access'),
            'ip_address'    => config('services.travelnext.ip'),

            'requiredCurrency'      => 'NGN',
            'journeyType'           => $journeyType,
            'OriginDestinationInfo' => $originDestination,

            'class'   => $this->mapCabin($validated['flight_type']),
            'adults'  => (int) $validated['adults'],
            'childs'  => (int) ($validated['childs'] ?? 0),
            'infants' => (int) ($validated['kids']   ?? 0),
        ];

        //dd($payload);

        $response = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/availability', $payload);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Flight search failed. Please try again.']);
        }

        $jsonData = $response->json();

        //dd($jsonData);
        // ── Reference data ────────────────────────────────────────────────────
        $airlines = collect(
            json_decode(file_get_contents(public_path('assets/data/airline.json')), true)
        )->keyBy('AirLineCode');

        $airports = collect(
            json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true)
        )->keyBy('AirportCode');

        $tripType    = $request->trip;
        $searchLegs  = $request->multi_legs ?? [];
        $itineraries = data_get($jsonData, 'AirSearchResponse.AirSearchResult.FareItineraries', []);

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
                    'operatingLogo'     => $opAirline['AirLineLogo']               ?? '/assets/img/airlines/default.png',
                    'eticket'           => (bool) ($fs['Eticket'] ?? true),
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

        $splitMultiLegs = function (array $allSegments, array $searchLegs): array {
            if (empty($searchLegs)) return array_map(fn($s) => [$s], $allSegments);
            $legs = []; $remaining = $allSegments;
            foreach ($searchLegs as $legIdx => $legDef) {
                $extractIata = fn(string $val) => preg_match('/\(([A-Z]{3})\)/', $val, $m) ? $m[1] : strtoupper(trim($val));
                $destIata = $extractIata($legDef['to'] ?? '');
                if ($legIdx === count($searchLegs) - 1) { $legs[] = $remaining; break; }
                $cutAt = -1;
                foreach ($remaining as $si => $seg) {
                    if (strtoupper($seg['to']) === $destIata) { $cutAt = $si; break; }
                }
                $legs[] = $cutAt === -1 ? array_splice($remaining, 0, 1) : array_splice($remaining, 0, $cutAt + 1);
            }
            if (!empty($remaining)) $legs[] = $remaining;
            return $legs;
        };

        //dd($mapSegments);

        $flights = collect($itineraries)->values()->map(
            function ($item, $index) use (
                $tripType, $searchLegs,
                $mapSegments, $calcLayovers, $calcLayoverMins, $fmtMins,
                $splitMultiLegs, $airlines
            ) {
                $fi       = $item['FareItinerary'];
                $fareInfo = $fi['AirItineraryFareInfo'];
                $odos     = $fi['OriginDestinationOptions'] ?? [];

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

                if ($tripType === 'oneway') {
                    $odo0             = $odos[0]['OriginDestinationOption'] ?? [];
                    $segments         = $mapSegments($odo0);
                    $totalStops       = (int) ($odos[0]['TotalStops'] ?? max(0, count($odo0) - 1));
                    $totalMins        = array_sum(array_column($segments, 'duration'));
                    $layoverDurations = $calcLayovers($segments);
                    $totalTimeMins    = $totalMins + $calcLayoverMins($segments);

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
                        $legTotalTimeMins = $legMins + $calcLayoverMins($legSegs);
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

                $firstSeg        = $segments[0] ?? [];
                $lastSeg         = !empty($segments) ? end($segments) : [];
                $deptHour        = (int) substr($firstSeg['departTime'] ?? '00:00', 0, 2);
                $arrHour         = (int) substr($lastSeg['arriveTime']  ?? '00:00', 0, 2);
                $validatingCode  = $fi['ValidatingAirlineCode'] ?? '';
                $validatingAir   = $airlines->get($validatingCode);

                if (!empty($firstSeg['departDT'])) {
                    $departDateLabel = \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
                }

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

                return [
                    'id'                     => $index,
                    'fareSourceCode'         => $fareInfo['FareSourceCode'],
                    'airline'                => $firstSeg['airline']     ?? '',
                    'airlineCode'            => $firstSeg['airlineCode'] ?? '',
                    'airlineLogo'            => $firstSeg['airlineLogo'] ?? '/assets/img/airlines/default.png',
                    'validatingCode'         => $validatingCode,
                    'validatingAirline'      => $validatingAir['AirLineName'] ?? $validatingCode,
                    'validatingLogo'         => $validatingAir['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'cabin'                  => $firstSeg['cabin']     ?? '',
                    'cabinCode'              => $firstSeg['cabinCode'] ?? 'Y',
                    'stops'                  => $totalStops,
                    'price'                  => (float) $fareInfo['ItinTotalFares']['TotalFare']['Amount'],
                    'baseFare'               => (float) $fareInfo['ItinTotalFares']['BaseFare']['Amount'],
                    'totalTax'               => (float) ($fareInfo['ItinTotalFares']['TotalTax']['Amount'] ?? 0),
                    'currency'               => $fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],
                    'isRefundable'           => strtolower($fareInfo['IsRefundable'] ?? 'no') === 'yes',
                    'fareType'               => $fareInfo['FareType']  ?? 'Public',
                    'ticketType'             => $fi['TicketType']       ?? 'eTicket',
                    'isPassportMandatory'    => (bool) ($fi['IsPassportMandatory'] ?? false),
                    'directionInd'           => $fi['DirectionInd']     ?? '',
                    'ticketAdvisory'         => trim($fi['TicketAdvisory'] ?? ''),
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
            }
        )->values()->toArray();
        //dd($flights);

        // ── Write ONLY to durable session — no flash data needed ─────────────
        // The Livewire FlightPage component reads directly from these session
        // keys in mount(), so data persists across refreshes and back-navigation.
        session([
            'flightResultsStore' => $flights,
            'searchParamsStore'  => $validated,
            'searchSessionId'    => data_get($jsonData, 'AirSearchResponse.session_id', ''),
        ]);

        // Plain redirect — no ->with([...]) flash needed
        return redirect()->route('air.flight-s');
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function mapCabin(string $code): string
    {
        return match ($code) {
            'Y'     => 'Economy',
            'S'     => 'PremiumEconomy',
            'C'     => 'Business',
            'F'     => 'First',
            default => 'Economy',
        };
    }
}
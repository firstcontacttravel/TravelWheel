<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPendingMail;
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

        // ── 1. Revalidate ─────────────────────────────────────────────────────
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

        // ── Reference data ────────────────────────────────────────────────────
        $airlines = collect(json_decode(file_get_contents(public_path('assets/data/airline.json')), true))->keyBy('AirLineCode');
        $airports = collect(json_decode(file_get_contents(public_path('assets/data/airportsCode.json')), true))->keyBy('AirportCode');
        $searchParams = session('searchParamsStore', []);
        $tripType     = strtolower($searchParams['trip'] ?? 'oneway');
        $searchLegs   = $searchParams['multi_legs'] ?? [];

        // ── Segment mapper ────────────────────────────────────────────────────
        $mapSegments = function (array $odo) use ($airlines, $airports): array {
            return collect($odo)->map(function ($seg) use ($airlines, $airports) {
                $fs = $seg['FlightSegment'];
                $dep = \Carbon\Carbon::parse($fs['DepartureDateTime']);
                $arr = \Carbon\Carbon::parse($fs['ArrivalDateTime']);
                $airlineCode = $fs['MarketingAirlineCode'];
                $airline     = $airlines->get($airlineCode);
                $fromCode    = $fs['DepartureAirportLocationCode'];
                $toCode      = $fs['ArrivalAirportLocationCode'];
                $fromAirport = $airports->get($fromCode);
                $toAirport   = $airports->get($toCode);
                $opCode      = $fs['OperatingAirline']['Code'] ?? $airlineCode;
                $opAirline   = $airlines->get($opCode);
                return [
                    'from' => $fromCode, 'to' => $toCode,
                    'fromCity'    => $fromAirport ? ($fromAirport['City'].'('.$fromCode.')') : $fromCode,
                    'toCity'      => $toAirport   ? ($toAirport['City']  .'('.$toCode  .')') : $toCode,
                    'fromAirport' => $fromAirport['AirportName'] ?? $fromCode,
                    'toAirport'   => $toAirport['AirportName']   ?? $toCode,
                    'fromCountry' => $fromAirport['Country']     ?? '',
                    'toCountry'   => $toAirport['Country']       ?? '',
                    'departTime'  => $dep->format('H:i'), 'arriveTime' => $arr->format('H:i'),
                    'departDate'  => $dep->format('D, d M Y'), 'arriveDate' => $arr->format('D, d M Y'),
                    'departDT'    => $fs['DepartureDateTime'], 'arriveDT' => $fs['ArrivalDateTime'],
                    'duration'    => (int) $fs['JourneyDuration'],
                    'flightNo'    => $airlineCode.$fs['FlightNumber'],
                    'airline'     => $fs['MarketingAirlineName'],
                    'airlineCode' => $airlineCode,
                    'airlineLogo' => $airline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'equipment'   => $fs['OperatingAirline']['Equipment'] ?? '',
                    'cabin'       => $fs['CabinClassText'] ?? '', 'cabinCode' => $fs['CabinClassCode'] ?? 'Y',
                    'resBookCode' => $seg['ResBookDesigCode'] ?? '', 'mealCode' => $fs['MealCode'] ?? '',
                    'seatsLeft'   => (int)($seg['SeatsRemaining']['Number'] ?? 9),
                    'belowMinimum'=> (bool)($seg['SeatsRemaining']['BelowMinimum'] ?? false),
                    'isCodeshare' => $opCode !== $airlineCode, 'operatingCode' => $opCode,
                    'operatingAirline'  => $fs['OperatingAirline']['Name']           ?? '',
                    'operatingFlightNo' => $opCode.($fs['OperatingAirline']['FlightNumber'] ?? ''),
                    'operatingLogo'     => $opAirline['AirLineLogo'] ?? '/assets/img/airlines/default.png',
                    'eticket'     => (bool)($fs['Eticket'] ?? true),
                ];
            })->values()->toArray();
        };

        $calcLayovers    = function (array $s): array { $o=[]; for($i=0;$i<count($s)-1;$i++){$m=\Carbon\Carbon::parse($s[$i]['arriveDT'])->diffInMinutes(\Carbon\Carbon::parse($s[$i+1]['departDT']));$o[]=floor($m/60).'h '.($m%60).'m';} return $o; };
        $calcLayoverMins = function (array $s): int   { $t=0; for($i=0;$i<count($s)-1;$i++){$t+=(int)\Carbon\Carbon::parse($s[$i]['arriveDT'])->diffInMinutes(\Carbon\Carbon::parse($s[$i+1]['departDT']));} return $t; };
        $fmtMins         = fn(int $m): string => floor($m/60).'h '.($m%60).'m';
        $splitMultiLegs  = function (array $all, array $legs) use (&$splitMultiLegs): array {
            if (empty($legs)) return array_map(fn($s) => [$s], $all);
            $result=[]; $remaining=$all;
            foreach ($legs as $idx => $legDef) {
                $iata = preg_match('/\(([A-Z]{3})\)/', $legDef['to']??'', $m) ? $m[1] : strtoupper(trim($legDef['to']??''));
                if ($idx === count($legs)-1) { $result[]=$remaining; break; }
                $cut=-1; foreach($remaining as $si=>$seg){if(strtoupper($seg['to'])===$iata){$cut=$si;break;}}
                $result[] = $cut===-1 ? array_splice($remaining,0,1) : array_splice($remaining,0,$cut+1);
            }
            if(!empty($remaining)) $result[]=$remaining;
            return $result;
        };

        $fareInfo = $fi['AirItineraryFareInfo'];
        $odos     = $fi['OriginDestinationOptions'] ?? [];
        $segments=$layoverDurations=$returnSegments=$returnLayoverDurations=$multiLegs=[];
        $totalStops=$totalMins=$totalTimeMins=$returnStops=$returnTotalTimeMins=0;
        $returnDurationLabel=$returnTotalTimeLabel=$returnDateLabel=$departDateLabel='';

        if ($tripType === 'oneway') {
            $odo0=$odos[0]['OriginDestinationOption']??[];
            $segments=$mapSegments($odo0); $totalStops=(int)($odos[0]['TotalStops']??max(0,count($odo0)-1));
            $totalMins=array_sum(array_column($segments,'duration')); $layoverDurations=$calcLayovers($segments);
            $totalTimeMins=$totalMins+$calcLayoverMins($segments);
        } elseif ($tripType === 'return') {
            $odo0=$odos[0]['OriginDestinationOption']??[];
            $segments=$mapSegments($odo0); $totalStops=(int)($odos[0]['TotalStops']??max(0,count($odo0)-1));
            $totalMins=array_sum(array_column($segments,'duration')); $layoverDurations=$calcLayovers($segments);
            $totalTimeMins=$totalMins+$calcLayoverMins($segments);
            if(!empty($odos[1])){$odo1=$odos[1]['OriginDestinationOption']??[];$returnSegments=$mapSegments($odo1);$returnStops=(int)($odos[1]['TotalStops']??max(0,count($odo1)-1));$rm=array_sum(array_column($returnSegments,'duration'));$returnDurationLabel=$fmtMins($rm);$returnLayoverDurations=$calcLayovers($returnSegments);$returnTotalTimeMins=$rm+$calcLayoverMins($returnSegments);$returnTotalTimeLabel=$fmtMins($returnTotalTimeMins);if(!empty($returnSegments[0]['departDT']))$returnDateLabel=\Carbon\Carbon::parse($returnSegments[0]['departDT'])->format('D, d M');}
        } elseif ($tripType === 'multi') {
            $odo0=$odos[0]['OriginDestinationOption']??[];$allSegs=$mapSegments($odo0);$totalStops=(int)($odos[0]['TotalStops']??max(0,count($odo0)-1));
            $legArrays=$splitMultiLegs($allSegs,$searchLegs);$segments=$legArrays[0]??[];
            $totalMins=array_sum(array_column($segments,'duration'));$layoverDurations=$calcLayovers($segments);$totalTimeMins=$totalMins+$calcLayoverMins($segments);
            foreach(array_slice($legArrays,1) as $ls){$lm=array_sum(array_column($ls,'duration'));$ll=$calcLayoverMins($ls);$multiLegs[]=['segments'=>$ls,'durationLabel'=>$fmtMins($lm),'stops'=>max(0,count($ls)-1),'layoverDurations'=>$calcLayovers($ls),'departDateLabel'=>!empty($ls[0]['departDT'])?\Carbon\Carbon::parse($ls[0]['departDT'])->format('D, d M'):'','totalTimeMins'=>$lm+$ll,'totalTimeLabel'=>$fmtMins($lm+$ll)];}
        }

        $firstSeg=$segments[0]??[]; $lastSeg=!empty($segments)?end($segments):[];
        $deptHour=(int)substr($firstSeg['departTime']??'00:00',0,2); $arrHour=(int)substr($lastSeg['arriveTime']??'00:00',0,2);
        if(!empty($firstSeg['departDT'])) $departDateLabel=\Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M');
        $validatingCode=$fi['ValidatingAirlineCode']??''; $validatingAirline=$airlines->get($validatingCode);

        $breakdown=collect($fareInfo['FareBreakdown']??[])->map(fn($fb)=>['passengerType'=>$fb['PassengerTypeQuantity']['Code'],'qty'=>(int)$fb['PassengerTypeQuantity']['Quantity'],'baseFare'=>(float)$fb['PassengerFare']['BaseFare']['Amount'],'totalFare'=>(float)$fb['PassengerFare']['TotalFare']['Amount'],'currency'=>$fb['PassengerFare']['TotalFare']['CurrencyCode'],'baggage'=>$fb['Baggage']??[],'cabinBaggage'=>$fb['CabinBaggage']??[],'taxes'=>$fb['PassengerFare']['Taxes']??[],'serviceTax'=>(float)($fb['PassengerFare']['ServiceTax']['Amount']??0),'surcharges'=>(float)($fb['PassengerFare']['Surcharges']['Amount']??0),'changeAllowed'=>$fb['PenaltyDetails']['ChangeAllowed']??false,'changePenalty'=>$fb['PenaltyDetails']['ChangePenaltyAmount']??'0.00','refundAllowed'=>$fb['PenaltyDetails']['RefundAllowed']??false,'refundPenalty'=>$fb['PenaltyDetails']['RefundPenaltyAmount']??'0.00'])->values()->toArray();

        $mappedFlight=['fareSourceCode'=>$fareInfo['FareSourceCode'],'airline'=>$firstSeg['airline']??'','airlineCode'=>$firstSeg['airlineCode']??'','airlineLogo'=>$firstSeg['airlineLogo']??'/assets/img/airlines/default.png','validatingCode'=>$validatingCode,'validatingAirline'=>$validatingAirline['AirLineName']??$validatingCode,'validatingLogo'=>$validatingAirline['AirLineLogo']??'/assets/img/airlines/default.png','cabin'=>$firstSeg['cabin']??'','cabinCode'=>$firstSeg['cabinCode']??'Y','stops'=>$totalStops,'price'=>(float)$fareInfo['ItinTotalFares']['TotalFare']['Amount'],'baseFare'=>(float)$fareInfo['ItinTotalFares']['BaseFare']['Amount'],'totalTax'=>(float)($fareInfo['ItinTotalFares']['TotalTax']['Amount']??0),'currency'=>$fareInfo['ItinTotalFares']['TotalFare']['CurrencyCode'],'isRefundable'=>strtolower($fareInfo['IsRefundable']??'no')==='yes','fareType'=>$fareInfo['FareType']??'Public','ticketType'=>$fi['TicketType']??'eTicket','isPassportMandatory'=>(bool)($fi['IsPassportMandatory']??false),'directionInd'=>$fi['DirectionInd']??'','ticketAdvisory'=>trim($fi['TicketAdvisory']??''),'segments'=>$segments,'departTime'=>$firstSeg['departTime']??'','arriveTime'=>$lastSeg['arriveTime']??'','departDT'=>$firstSeg['departDT']??'','arriveDT'=>$lastSeg['arriveDT']??'','totalDuration'=>$totalMins,'durationLabel'=>$fmtMins($totalMins),'layoverDurations'=>$layoverDurations,'departDateLabel'=>$departDateLabel,'totalTimeMins'=>$totalTimeMins,'totalTimeLabel'=>$fmtMins($totalTimeMins),'returnSegments'=>$returnSegments,'returnStops'=>$returnStops,'returnDurationLabel'=>$returnDurationLabel,'returnDateLabel'=>$returnDateLabel,'returnLayoverDurations'=>$returnLayoverDurations,'returnTotalTimeMins'=>$returnTotalTimeMins,'returnTotalTimeLabel'=>$returnTotalTimeLabel,'multiLegs'=>$multiLegs,'departSlot'=>$deptHour<12?'morning':($deptHour<18?'afternoon':'evening'),'arrivalSlot'=>$arrHour<12?'morning':($arrHour<18?'afternoon':'evening'),'fareBreakdown'=>$breakdown];

        $extraResponse     = Http::timeout(60)->post('https://travelnext.works/api/aeroVE5/extra_services', $payload);
        if ($extraResponse->failed()) return back()->withErrors(['error' => 'Extra services fetch failed.']);
        $fareRulesResponse = Http::timeout(60)->post('https://travelnext.works/api/aeroVE5/fare_rules', $payload);
        if ($fareRulesResponse->failed()) return back()->withErrors(['error' => 'Fare rules fetch failed.']);

        session(['bookingFlight'=>['flight'=>$mappedFlight,'revalidate'=>$revalidateData,'segments'=>$mappedFlight['segments'],'fareBreakdown'=>$mappedFlight['fareBreakdown']],'bookingSessionId'=>$validated['session_id'],'bookingSearchParams'=>$searchParams,'extraServices'=>$extraResponse->json(),'fareRules'=>$fareRulesResponse->json(),'tripType'=>$mappedFlight['directionInd']??'N/A']);

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
        ]);

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId'     => $uniqueId,
            'bookingTktTimeLimit' => $tktTimeLimit,
            'bookingStatus'       => $bookResult['Status'] ?? '',
            'flightBookingDbId'   => $dbBooking->id,
        ]);

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

        return view('livewire.pages.flight.flight-payment-gateway', [
            'flight'  => $mappedFlight,
            'contact' => session('bookingContact', []),
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
        ]);

        $this->_sendConfirmedEmail($dbBooking);

        session([
            'bookingConfirmation' => $apiResponse,
            'bookingUniqueId'     => $uniqueId,
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

        return view('livewire.pages.flight.flight-payment-options', [
            'flight'       => $mappedFlight,
            'uniqueId'     => session('bookingUniqueId'),
            'tktTimeLimit' => session('bookingTktTimeLimit'),
            'contact'      => session('bookingContact', []),
            'passengers'   => session('bookingPassengers', []),
            'dbId'         => session('flightBookingDbId'),
        ]);
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
            ]);
 
            session(['flightBookingDbId' => $dbBooking->id]);
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
            ]);
 
            $this->_sendConfirmedEmail($dbBooking);
 
            session([
                'bookingConfirmation' => $apiResponse,
                'bookingUniqueId'     => $uniqueId,
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
 
        return view('livewire.pages.flight.flight-travelflex-pending');
    }
 
    // =========================================================================
    //  travelFlexConfirmation() — Gateway paid + booked confirmation (TravelFlex)
    // =========================================================================
    public function travelFlexConfirmation()
    {
        if (! session()->has('travelFlexPlan')) {
            return redirect()->route('air.flight-s')->withErrors(['error' => 'No TravelFlex plan found.']);
        }
 
        return view('livewire.pages.flight.flight-travelflex-confirmation');
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
            'uniqueId'     => session('bookingUniqueId'),
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

    private function _persistBooking(array $mappedFlight, array $validated, array $apiResponse, array $overrides = []): FlightBooking
    {
        $segments   = $mappedFlight['segments'] ?? [];
        $firstSeg   = $segments[0] ?? [];
        $lastSeg    = !empty($segments) ? end($segments) : [];
        $contact    = $validated['contact']    ?? session('bookingContact', []);
        $passengers = $validated['passengers'] ?? session('bookingPassengers', []);

        $tktRaw = $overrides['tkt_time_limit'] ?? null;
        unset($overrides['tkt_time_limit']);

        return FlightBooking::create(array_merge([
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

    private function _sendConfirmedEmail(FlightBooking $booking): void
    {
        if ($booking->confirmation_email_sent || empty($booking->contact_email)) return;
        try {
            Mail::to($booking->contact_email)->send(new BookingConfirmedMail($booking));
            $booking->update(['confirmation_email_sent' => true]);
        } catch (\Throwable $e) {
            Log::error('BookingConfirmedMail failed', ['id' => $booking->id, 'err' => $e->getMessage()]);
        }
    }

    private function _sendPendingEmail(FlightBooking $booking, string $method = 'bank_transfer'): void
    {
        if ($booking->pending_email_sent || empty($booking->contact_email)) return;
        try {
            Mail::to($booking->contact_email)->send(new BookingPendingMail($booking, $method));
            $booking->update(['pending_email_sent' => true]);
        } catch (\Throwable $e) {
            Log::error('BookingPendingMail failed', ['id' => $booking->id, 'err' => $e->getMessage()]);
        }
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
            return $success ? ($result['TravelItinerary'] ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('TripDetails API error', ['message' => $e->getMessage()]);
            return [];
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
            'uniqueId'          => $uniqueId,
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
                ]);
 
                $this->_sendConfirmedEmail($dbBooking);
 
                session([
                    'bookingConfirmation' => $apiResponse,
                    'bookingUniqueId'     => $uniqueId,
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


    
}
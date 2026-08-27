<?php

namespace App\Services;

use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ItineraryPdfService
{
    private array $imageCache = [];

    public function generate(
        FlightBooking $booking,
        array $tripDetails = [],
        string $documentState = 'auto',
        string $audience = 'customer',
    ): string {
        return Pdf::loadView('pdf.itinerary', $this->buildViewData($booking, $tripDetails, $documentState, $audience))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_php' => false,
                'enable_javascript' => false,
            ])
            ->output();
    }

    public function buildViewData(
        FlightBooking $booking,
        array $tripDetails = [],
        string $documentState = 'auto',
        string $audience = 'customer',
    ): array {
        $flight = $booking->flight_snapshot ?? [];
        $tripDetails = $this->unwrapTripDetails($tripDetails ?: ($booking->itinerary_snapshot ?? []) ?: ($booking->ticket_api_response ?? []));
        $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
        $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->map(fn ($item) => $item['CustomerInfo'] ?? $item)
            ->values();

        $passengers = collect($passengers)->map(function (array $passenger, int $index) use ($customerInfos): array {
            $live = (array) $customerInfos->get($index, []);
            return array_merge($passenger, [
                'eticket' => $this->value($live, ['eTicketNumber', 'ETicketNumber', 'eTicket', 'ETicket', 'TicketNumber', 'ticketNumber'])
                    ?: $this->value($passenger, ['eticket', 'eTicket', 'eTicketNumber', 'ETicket', 'TicketNumber', 'ticketNumber']),
                'nationality' => $this->value($live, ['Nationality', 'nationality']) ?: $this->value($passenger, ['nationality', 'nationality_name']),
                'date_of_birth' => $this->value($live, ['DateOfBirth', 'dateOfBirth']) ?: $this->value($passenger, ['date_of_birth', 'dob']),
                'gender' => $this->value($live, ['Gender', 'gender']) ?: $this->value($passenger, ['gender', 'sex']),
            ]);
        })->all();

        $groups = $this->segmentGroups($flight);
        $segments = collect($groups)->flatMap(fn (array $group) => $group['segments'])->values()->all();
        $first = $segments[0] ?? [];
        $last = $segments ? $segments[array_key_last($segments)] : [];
        $heroSegments = collect($groups)->contains(fn ($group) => $group['type'] === 'leg')
            ? $segments
            : ($groups[0]['segments'] ?? $segments);
        $heroFirst = $heroSegments[0] ?? $first;
        $heroLast = $heroSegments ? $heroSegments[array_key_last($heroSegments)] : $last;
        $liveTicketed = strtoupper((string) data_get($tripDetails, 'TicketStatus')) === 'TICKETED';
        $hasTicketNumber = collect($passengers)->contains(fn (array $passenger) => filled($passenger['eticket'] ?? null));
        $isTicketed = $liveTicketed || $booking->isTicketed() || ($booking->ticket_ordered && $hasTicketNumber);
        $state = $documentState === 'auto' ? $this->stateFor($booking, $isTicketed) : $documentState;
        if ($state === 'ticketed' && ! $isTicketed) $state = 'ticketing_required';
        $showTicketData = $isTicketed || $audience === 'internal';
        $pnr = $this->airlinePnr($tripDetails);

        $stateDetails = match ($state) {
            'ticketed' => ['E-TICKET ITINERARY', 'Ticketed', '#039855', '#ecfdf3'],
            'on_hold' => ['BOOKING ITINERARY', 'On hold', '#b54708', '#fff7ed'],
            'payment_pending' => ['BOOKING ITINERARY', 'Payment pending', '#b54708', '#fff7ed'],
            'travelflex_review' => ['TRAVELFLEX ITINERARY', 'Under review', '#3538cd', '#eef4ff'],
            'travelflex_approved' => ['TRAVELFLEX ITINERARY', 'Approved', '#039855', '#ecfdf3'],
            'travelflex_rejected' => ['TRAVELFLEX ITINERARY', 'Application declined', '#b42318', '#fef3f2'],
            'ticketing_required' => ['BOOKING ITINERARY', 'Ticketing required', '#b42318', '#fef3f2'],
            default => ['BOOKING ITINERARY', 'Processing', '#3538cd', '#eef4ff'],
        };

        return [
            'booking' => $booking,
            'bookingRef' => $booking->booking_ref ?: $booking->unique_id,
            'documentTitle' => $stateDetails[0],
            'statusLabel' => $stateDetails[1],
            'statusColor' => $stateDetails[2],
            'statusBackground' => $stateDetails[3],
            'isTicketed' => $isTicketed,
            'showTicketData' => $showTicketData,
            'showWatermark' => ! $isTicketed,
            'watermarkLabel' => 'ITINERARY ONLY - NOT A TICKET',
            'ticketPNR' => $showTicketData ? $pnr : null,
            'issuedAt' => $isTicketed ? ($booking->ticket_ordered_at ?: $booking->updated_at) : null,
            'holdUntil' => ! $isTicketed ? $booking->tkt_time_limit : null,
            'tripLabel' => collect($groups)->contains(fn ($group) => $group['type'] === 'leg') ? 'Multi-City' : (collect($groups)->contains(fn ($group) => $group['type'] === 'return') ? 'Round Trip' : 'One Way'),
            'airline' => $flight['airline'] ?? $booking->airline ?? ($first['airline'] ?? 'Airline'),
            'airlineCode' => $first['airline_code'] ?? '',
            'airlineLogo' => $this->pdfImage($first['airline_logo'] ?? ($flight['airlineLogo'] ?? null)),
            'flightNumbers' => collect($segments)->pluck('flight_number')->filter()->unique()->implode(' / '),
            'origin' => $heroFirst,
            'destination' => $heroLast,
            'journeyDuration' => $this->journeyDuration($heroFirst['depart_at'] ?? null, $heroLast['arrive_at'] ?? null),
            'totalStops' => max(0, count($heroSegments) - 1),
            'travelDate' => $heroFirst['depart_at'] ?? null,
            'segmentGroups' => $groups,
            'passengers' => $passengers,
            'contactEmail' => $booking->contact_email,
            'cabin' => \App\Support\FlightDisplay::cabin($flight, $booking),
            'travelwheelLogo' => extension_loaded('gd') ? $this->imageDataUri(public_path('assets/img/alt-logo.png')) : null,
            'generatedAt' => now('Africa/Lagos'),
        ];
    }

    private function segmentGroups(array $flight): array
    {
        $groups = [];
        $multiLegs = is_array($flight['multiLegs'] ?? null) ? $flight['multiLegs'] : [];
        if ($multiLegs !== []) {
            foreach ($multiLegs as $index => $leg) {
                $segments = $this->normalizeSegments($leg['segments'] ?? []);
                if ($segments !== []) {
                    $groups[] = ['type' => 'leg', 'label' => 'LEG ' . ($index + 1), 'segments' => $segments];
                }
            }
            return $groups;
        }

        $outbound = $this->normalizeSegments($flight['segments'] ?? []);
        $return = $this->normalizeSegments($flight['returnSegments'] ?? []);
        if ($outbound !== []) $groups[] = ['type' => 'outbound', 'label' => 'OUTBOUND', 'segments' => $outbound];
        if ($return !== []) $groups[] = ['type' => 'return', 'label' => 'RETURN', 'segments' => $return];
        return $groups;
    }

    private function normalizeSegments(array $segments): array
    {
        return collect($segments)->filter(fn ($segment) => is_array($segment))->map(function (array $segment): array {
            $departAt = $this->dateValue($segment, ['departDT', 'DepartureDateTime', 'departureDate', 'departDate'], ['departTime', 'dep_time']);
            $arriveAt = $this->dateValue($segment, ['arriveDT', 'ArrivalDateTime', 'arrivalDate', 'arriveDate'], ['arriveTime', 'arr_time']);
            $airlineCode = (string) ($this->value($segment, ['airlineCode', 'marketingAirlineCode', 'carrierCode']) ?: '');
            $flightNumber = (string) ($this->value($segment, ['flightNo', 'flight_number']) ?: trim($airlineCode . ' ' . ($this->value($segment, ['flightNumber', 'FlightNumber']) ?: '')));

            return [
                'from' => $this->value($segment, ['from', 'dep_iata', 'airportOriginCode', 'OriginLocation.LocationCode']) ?: '',
                'to' => $this->value($segment, ['to', 'arr_iata', 'airportDestinationCode', 'DestinationLocation.LocationCode']) ?: '',
                'from_city' => $this->value($segment, ['fromCity', 'dep_city', 'originCity']) ?: '',
                'to_city' => $this->value($segment, ['toCity', 'arr_city', 'destinationCity']) ?: '',
                'from_airport' => $this->value($segment, ['fromAirport', 'originAirport', 'departureAirport']) ?: '',
                'to_airport' => $this->value($segment, ['toAirport', 'destinationAirport', 'arrivalAirport']) ?: '',
                'depart_at' => $departAt,
                'arrive_at' => $arriveAt,
                'duration' => $this->duration($segment, $departAt, $arriveAt),
                'stops' => (int) ($this->value($segment, ['stops', 'stopCount']) ?: 0),
                'flight_number' => trim($flightNumber),
                'airline' => $this->value($segment, ['airline', 'airline_name', 'operatingAirline']) ?: 'Airline',
                'airline_code' => $airlineCode,
                'airline_logo' => $this->pdfImage($this->value($segment, ['airlineLogo', 'airline_logo'])),
                'aircraft' => $this->value($segment, ['equipment', 'aircraft', 'equipmentName']) ?: '-',
                'cabin' => \App\Support\FlightDisplay::cabin($segment),
                'booking_class' => $this->value($segment, ['bookingClass', 'class', 'cabinCode']) ?: '-',
                'fare_basis' => $this->value($segment, ['fareBasis', 'fare_basis']) ?: '-',
                'baggage' => $this->value($segment, ['baggage', 'baggageInfo', 'checkedBaggage']) ?: '-',
                'carry_on' => $this->value($segment, ['carryOnBaggage', 'cabinBaggage']) ?: '-',
                'meals' => $this->value($segment, ['meals', 'meal']) ?: 'Subject to airline service',
            ];
        })->filter(fn (array $segment) => $segment['from'] !== '' && $segment['to'] !== '')->values()->all();
    }

    private function stateFor(FlightBooking $booking, bool $ticketed): string
    {
        if ($ticketed) return 'ticketed';
        if ($booking->booking_status === 'on_hold') return 'on_hold';
        if ($booking->payment_status !== 'paid') return 'payment_pending';
        return 'ticketing_required';
    }

    private function unwrapTripDetails(array $data): array
    {
        return data_get($data, 'TripDetailsResponse.TripDetailsResult.TravelItinerary')
            ?? data_get($data, 'TravelItinerary')
            ?? $data;
    }

    private function airlinePnr(array $tripDetails): ?string
    {
        $items = data_get($tripDetails, 'ItineraryInfo.ReservationItems', []);
        foreach ((array) $items as $item) {
            $pnr = data_get($item, 'ReservationItem.AirlinePNR') ?? data_get($item, 'AirlinePNR');
            if (filled($pnr)) return (string) $pnr;
        }
        return $this->value($tripDetails, ['ItineraryInfo.AirlinePNR', 'AirlinePNR']);
    }

    private function dateValue(array $data, array $dateKeys, array $timeKeys): ?Carbon
    {
        $date = $this->value($data, $dateKeys);
        if (! filled($date)) return null;
        $time = $this->value($data, $timeKeys);
        try {
            $value = (string) $date;
            if ($time && ! preg_match('/\d{1,2}:\d{2}/', $value)) $value .= ' ' . $time;
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function duration(array $segment, ?Carbon $departAt, ?Carbon $arriveAt): string
    {
        $duration = $this->value($segment, ['durationLabel', 'duration']);
        if (is_numeric($duration)) return intdiv((int) $duration, 60) . 'h ' . ((int) $duration % 60) . 'm';
        if (filled($duration)) return (string) $duration;
        return $this->journeyDuration($departAt, $arriveAt);
    }

    private function journeyDuration(?Carbon $departAt, ?Carbon $arriveAt): string
    {
        if (! $departAt || ! $arriveAt) return '-';
        $minutes = max(0, $departAt->diffInMinutes($arriveAt, false));
        return intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm';
    }

    private function value(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);
            if (filled($value)) return $value;
        }
        return null;
    }

    private function imageDataUri(string $path): ?string
    {
        if (! is_file($path)) return null;
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    private function pdfImage(mixed $source): ?string
    {
        if (! is_string($source) || trim($source) === '') return null;
        if (array_key_exists($source, $this->imageCache)) return $this->imageCache[$source];
        if (str_starts_with($source, 'data:')) return $this->imageCache[$source] = $source;
        if (! str_starts_with($source, 'http://') && ! str_starts_with($source, 'https://')) {
            return $this->imageCache[$source] = (is_file($source) ? $this->imageDataUri($source) : null);
        }

        // Cache::remember() treats a cached null as a cache miss and recomputes
        // on every call, so a logo URL that fails once (timeout, 404, wrong
        // content-type) would otherwise trigger a fresh 4s HTTP fetch on every
        // single PDF render, forever. Cache a sentinel for failures instead so
        // they're remembered too (with a shorter TTL, in case it's transient).
        $cacheKey = 'pdf-airline-logo:'.sha1($source);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $embedded = $cached === 'FETCH_FAILED' ? null : $cached;
        } else {
            $embedded = (function () use ($source): ?string {
                try {
                    $response = Http::timeout(4)->get($source);
                    if (! $response->successful()) return null;
                    $mime = strtolower((string) $response->header('Content-Type'));
                    if (! str_starts_with($mime, 'image/')) return null;
                    $requiresGd = str_contains($mime, 'png')
                        || str_contains($mime, 'gif')
                        || str_contains($mime, 'webp');
                    if ($requiresGd && ! extension_loaded('gd')) return null;
                    return 'data:'.strtok($mime, ';').';base64,'.base64_encode($response->body());
                } catch (\Throwable) {
                    return null;
                }
            })();

            Cache::put($cacheKey, $embedded ?? 'FETCH_FAILED', $embedded ? now()->addDays(30) : now()->addHours(1));
        }

        if (! extension_loaded('gd') && is_string($embedded) && preg_match('#^data:image/(png|gif|webp)#i', $embedded)) {
            $embedded = null;
        }

        return $this->imageCache[$source] = $embedded;
    }
}

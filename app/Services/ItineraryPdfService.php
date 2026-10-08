<?php

namespace App\Services;

use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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
        // DomPDF writes metrics for the @font-face files in resources/fonts/pdf
        // here on first use. A fresh deploy may not have the folder yet.
        File::ensureDirectoryExists(storage_path('fonts'));

        $pdf = Pdf::loadView('pdf.itinerary', $this->buildViewData($booking, $tripDetails, $documentState, $audience))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                // Embed only the glyphs used. Whole Plex files would add
                // roughly 200 KB per weight to every attachment.
                'isFontSubsettingEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_php' => false,
                'enable_javascript' => false,
            // Merged over config/dompdf.php. Without the merge, chroot falls
            // back to DomPDF's vendor folder and the bundled fonts are refused.
            ], true);

        $pdf->render();
        $this->numberPages($pdf->getDomPDF());

        return $pdf->output();
    }

    /**
     * "Page 1 of 2" in the footer's right-hand corner. CSS counter(pages) is
     * not supported by DomPDF, so the text is drawn once the page count is
     * known. The footer leaves room for it (pdf.partials.itinerary-styles).
     */
    private function numberPages(\Dompdf\Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('Plex Sans') ?? $metrics->getFont('DejaVu Sans');
        $size = 7.5;
        $widest = $metrics->getTextWidth('Page 0 of 0', $font, $size);
        $muted = sscanf(config('brand.colors.muted'), '#%02x%02x%02x');

        $canvas->page_text(
            $canvas->get_width() - 40 - $widest,
            $canvas->get_height() - 33.8,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            $size,
            array_map(fn (int $channel) => $channel / 255, $muted),
        );
    }

    public function buildViewData(
        FlightBooking $booking,
        array $tripDetails = [],
        string $documentState = 'auto',
        string $audience = 'customer',
    ): array {
        $flight = $booking->flight_snapshot ?? [];
        $tripDetails = $this->resolveTripDetails($booking, $tripDetails);
        $passengers = $this->passengers($booking, $tripDetails);

        $groups = array_map(
            fn (array $group) => $group + ['duration' => $this->minutesLabel($this->groupMinutes($group['segments']))],
            $this->segmentGroups($flight),
        );
        $segments = collect($groups)->flatMap(fn (array $group) => $group['segments'])->values()->all();
        $first = $segments[0] ?? [];
        $last = $segments ? $segments[array_key_last($segments)] : [];
        $heroSegments = collect($groups)->contains(fn ($group) => $group['type'] === 'leg')
            ? $segments
            : ($groups[0]['segments'] ?? $segments);
        $heroFirst = $heroSegments[0] ?? $first;
        $heroLast = $heroSegments ? $heroSegments[array_key_last($heroSegments)] : $last;
        $isTicketed = $this->ticketedFrom($booking, $tripDetails, $passengers);
        $state = $documentState === 'auto' ? $this->stateFor($booking, $isTicketed) : $documentState;
        if ($state === 'ticketed' && ! $isTicketed) $state = 'ticketing_required';
        // "Ticketing required" in red is an instruction to ops. A customer
        // who has paid is told the ticket is on its way instead.
        if ($state === 'ticketing_required' && $audience !== 'internal') {
            $state = $this->stateFor($booking, false);
            if ($state === 'ticketing_required') $state = 'ticket_processing';
        }
        $showTicketData = $isTicketed || $audience === 'internal';
        // SkyLink has no trip details; its reserve() PNR is the airline reference.
        $pnr = $this->airlinePnr($tripDetails) ?: ($booking->isSkylink() ? $booking->unique_id : null);

        $c = config('brand.colors');
        $stateDetails = match ($state) {
            'ticketed' => ['E-ticket itinerary', 'Ticketed', $c['success'], $c['success_bg']],
            'on_hold' => ['Booking itinerary', 'On hold', $c['warning'], $c['warning_bg']],
            'payment_pending' => ['Booking itinerary', 'Payment pending', $c['warning'], $c['warning_bg']],
            'travelflex_review' => ['TravelFlex itinerary', 'Under review', $c['brand'], $c['brand_bg']],
            'travelflex_approved' => ['TravelFlex itinerary', 'Approved', $c['success'], $c['success_bg']],
            'travelflex_rejected' => ['TravelFlex itinerary', 'Application declined', $c['danger'], $c['danger_bg']],
            'ticketing_required' => ['Booking itinerary', 'Ticketing required', $c['danger'], $c['danger_bg']],
            'ticket_processing' => ['Booking itinerary', 'Ticket being issued', $c['brand'], $c['brand_bg']],
            default => ['Booking itinerary', 'Processing', $c['brand'], $c['brand_bg']],
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
            // A paid SkyLink booking waiting on its ticket. Its deadline is a
            // hold between us and SkyLink, not something the customer can act
            // on, so customers never see it; the internal copy keeps it.
            'awaitingSupplierTicket' => ! $isTicketed && $booking->isSkylink() && $booking->payment_status === 'paid' && $audience !== 'internal',
            'tripLabel' => collect($groups)->contains(fn ($group) => $group['type'] === 'leg') ? 'Multi-City' : (collect($groups)->contains(fn ($group) => $group['type'] === 'return') ? 'Round Trip' : 'One Way'),
            'airline' => $flight['airline'] ?? $booking->airline ?? ($first['airline'] ?? 'Airline'),
            'airlineCode' => $first['airline_code'] ?? '',
            'airlineLogo' => $this->pdfImage($first['airline_logo'] ?? ($flight['airlineLogo'] ?? null)),
            'flightNumbers' => collect($segments)->pluck('flight_number')->filter()->unique()->implode(' / '),
            'origin' => $heroFirst,
            'destination' => $heroLast,
            'journeyDuration' => $this->minutesLabel($this->groupMinutes($heroSegments)) ?? $this->journeyDuration($heroFirst['depart_at'] ?? null, $heroLast['arrive_at'] ?? null),
            'totalStops' => max(0, count($heroSegments) - 1),
            'travelDate' => $heroFirst['depart_at'] ?? null,
            'segmentGroups' => $groups,
            'passengers' => $passengers,
            'contactEmail' => $booking->contact_email,
            'cabin' => \App\Support\FlightDisplay::cabin($flight, $booking),
            // No travelwheelLogo. public/assets/img/alt-logo.png is a white
            // wordmark on a near-opaque white field, so it rendered invisible on
            // this document's white header while base64-encoding 272 KB into
            // every generation — the itinerary weighed 347 KB against the
            // e-ticket's 51 KB for a logo nobody could see. The masthead is set
            // in type until a usable asset exists.
            'documentState' => $state,
            'tripTitle' => $this->tripTitle($groups),
            'tripStart' => $first['depart_at'] ?? null,
            'tripEnd' => count($groups) > 1 ? ($last['arrive_at'] ?? null) : null,
            'bookedAt' => $booking->created_at,
            'generatedAt' => now('Africa/Lagos'),
        ];
    }

    /**
     * "Lagos to London" for one-way and return trips, the whole chain for
     * multi-city, so the heading names where the traveller is going rather
     * than three-letter codes.
     */
    private function tripTitle(array $groups): string
    {
        $legs = collect($groups)->map(fn (array $group) => [
            $this->cityName($group['segments'][0], 'from'),
            $this->cityName($group['segments'][array_key_last($group['segments'])], 'to'),
        ]);
        if ($legs->isEmpty()) return 'Your trip';
        if (($groups[0]['type'] ?? '') !== 'leg') return $legs[0][0] . ' to ' . $legs[0][1];

        $stops = [$legs[0][0]];
        foreach ($legs as [$from, $to]) {
            if (end($stops) !== $from) $stops[] = $from;
            $stops[] = $to;
        }
        return implode(' to ', $stops);
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
                'duration_minutes' => $this->durationMinutes($segment, $departAt, $arriveAt),
                'from_terminal' => $this->value($segment, ['terminal', 'departureTerminal', 'DepartureTerminal', 'depTerminal']) ?: '',
                'to_terminal' => $this->value($segment, ['arrivalTerminal', 'ArrivalTerminal', 'arrTerminal']) ?: '',
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

    /**
     * Whether the document for this booking is an e-ticket, by the same rule
     * the PDF itself uses, so an email can name its attachment to match.
     */
    public function isTicketed(FlightBooking $booking, array $tripDetails = []): bool
    {
        $tripDetails = $this->resolveTripDetails($booking, $tripDetails);

        return $this->ticketedFrom($booking, $tripDetails, $this->passengers($booking, $tripDetails));
    }

    private function resolveTripDetails(FlightBooking $booking, array $tripDetails): array
    {
        return $this->unwrapTripDetails($tripDetails ?: ($booking->itinerary_snapshot ?? []) ?: ($booking->ticket_api_response ?? []));
    }

    private function passengers(FlightBooking $booking, array $tripDetails): array
    {
        $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->map(fn ($item) => $item['CustomerInfo'] ?? $item)
            ->values();

        return collect(\App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []))->map(function (array $passenger, int $index) use ($customerInfos): array {
            $live = (array) $customerInfos->get($index, []);
            return array_merge($passenger, [
                'eticket' => $this->value($live, ['eTicketNumber', 'ETicketNumber', 'eTicket', 'ETicket', 'TicketNumber', 'ticketNumber'])
                    ?: $this->value($passenger, ['eticket', 'eTicket', 'eTicketNumber', 'ETicket', 'TicketNumber', 'ticketNumber']),
                'nationality' => $this->value($live, ['Nationality', 'nationality']) ?: $this->value($passenger, ['nationality', 'nationality_name']),
                'date_of_birth' => $this->value($live, ['DateOfBirth', 'dateOfBirth']) ?: $this->value($passenger, ['date_of_birth', 'dob']),
                'gender' => $this->value($live, ['Gender', 'gender']) ?: $this->value($passenger, ['gender', 'sex']),
            ]);
        })->all();
    }

    private function ticketedFrom(FlightBooking $booking, array $tripDetails, array $passengers): bool
    {
        $liveTicketed = strtoupper((string) data_get($tripDetails, 'TicketStatus')) === 'TICKETED';
        $hasTicketNumber = collect($passengers)->contains(fn (array $passenger) => filled($passenger['eticket'] ?? null));

        return $liveTicketed || $booking->isTicketed() || ($booking->ticket_ordered && $hasTicketNumber);
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

    /**
     * Supplier durations are the only reliable figure: departure and arrival
     * times are local to each airport, so subtracting them is wrong whenever
     * the two airports sit in different time zones.
     */
    private function durationMinutes(array $segment, ?Carbon $departAt, ?Carbon $arriveAt): ?int
    {
        $duration = $this->value($segment, ['duration', 'durationLabel']);
        if (is_numeric($duration)) return (int) $duration;
        if (is_string($duration) && preg_match('/^\s*(?:(\d+)\s*h)?\s*(?:(\d+)\s*m)?\s*$/i', $duration, $m) && ($m[1] ?? '') . ($m[2] ?? '') !== '') {
            return (int) ($m[1] ?? 0) * 60 + (int) ($m[2] ?? 0);
        }
        return $departAt && $arriveAt ? max(0, (int) $departAt->diffInMinutes($arriveAt, false)) : null;
    }

    /**
     * Flying time plus time on the ground between flights. Connections are
     * measured at one airport, so their local times can be subtracted.
     */
    private function groupMinutes(array $segments): ?int
    {
        $total = 0;
        foreach ($segments as $index => $segment) {
            if ($segment['duration_minutes'] === null) return null;
            $total += $segment['duration_minutes'];
            $previous = $segments[$index - 1] ?? null;
            if ($previous && $previous['arrive_at'] && $segment['depart_at']) {
                $total += max(0, (int) $previous['arrive_at']->diffInMinutes($segment['depart_at'], false));
            }
        }
        return $total;
    }

    private function minutesLabel(?int $minutes): ?string
    {
        return $minutes === null ? null : intdiv($minutes, 60) . 'h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
    }

    /** "Lagos (LOS)" and "Lagos" both become "Lagos"; a bare code stays a code. */
    private function cityName(array $segment, string $side): string
    {
        $city = trim((string) preg_replace('/\s*\([A-Z]{3}\)\s*$/', '', (string) ($segment[$side . '_city'] ?? '')));
        return $city !== '' ? $city : (string) ($segment[$side] ?? '');
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

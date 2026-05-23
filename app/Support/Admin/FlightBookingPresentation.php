<?php

namespace App\Support\Admin;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;

class FlightBookingPresentation
{
    public static function workspaceSummary(object $record): HtmlString
    {
        $flight = self::normalize($record->flight_snapshot ?? []);
        $flight = is_array($flight) ? $flight : [];
        $segments = is_array($flight) ? (self::value($flight, 'segments', []) ?: []) : [];
        $firstSegment = is_array($segments) ? ($segments[0] ?? []) : [];
        $lastSegment = is_array($segments) && $segments !== [] ? end($segments) : [];

        $route = $record->route ?: trim((self::value($firstSegment, 'from', '-') . ' -> ' . self::value($lastSegment, 'to', '-')), ' -');
        $departure = self::value($flight, 'departDateLabel')
            ?: trim(self::value($firstSegment, 'departDate', '') . ' ' . self::value($firstSegment, 'departTime', ''));
        $arrival = trim(self::value($lastSegment, 'arriveDate', '') . ' ' . self::value($lastSegment, 'arriveTime', ''));
        $passengers = (int) ($record->adult_count + $record->child_count + $record->infant_count);
        $queue = self::queueLabel($record);

        $html = '<div class="tw-booking-hero">';
        $html .= '<div class="tw-booking-hero-main">';
        $html .= '<div class="tw-booking-eyebrow">' . e($queue) . '</div>';
        $html .= '<div class="tw-booking-title">' . e($record->booking_ref ?: 'Booking') . '</div>';
        $html .= '<div class="tw-booking-route">' . e($route ?: '-') . '</div>';
        $html .= '<div class="tw-booking-meta">';
        $html .= '<span>' . e($record->airline ?: 'Unknown airline') . '</span>';
        $html .= '<span>' . e(self::label($record->trip_type)) . '</span>';
        $html .= '<span>' . e($passengers . ' passenger' . ($passengers === 1 ? '' : 's')) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="tw-booking-hero-side">';
        $html .= '<div class="tw-booking-price">' . e(self::money($record->total_price, $record->currency)) . '</div>';
        $html .= '<div class="tw-booking-pill-row">';
        $html .= self::statusPill('Booking', $record->booking_status);
        $html .= self::statusPill('Payment', $record->payment_status);
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="tw-booking-timeline">';
        $html .= self::timelineItem('Created', optional($record->created_at)->format('d M Y, H:i'), true);
        $html .= self::timelineItem('Payment', $record->payment_verified_at ? $record->payment_verified_at->format('d M Y, H:i') : self::label($record->payment_status), $record->payment_status === 'paid');
        $html .= self::timelineItem('Ticket', $record->ticket_ordered_at ? $record->ticket_ordered_at->format('d M Y, H:i') : self::label($record->booking_status), (bool) $record->ticket_ordered);
        $html .= self::timelineItem('Depart', $departure ?: '-', filled($departure));
        $html .= self::timelineItem('Arrive', $arrival ?: '-', filled($arrival));
        $html .= '</div>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public static function passengers(mixed $payload): HtmlString
    {
        $passengers = self::normalize($payload);

        if (! is_array($passengers) || $passengers === []) {
            return self::empty('No passenger snapshot stored.');
        }

        if (self::isAssoc($passengers)) {
            $passengers = [$passengers];
        }

        $html = '<div class="tw-passenger-grid">';

        foreach ($passengers as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $name = trim(implode(' ', array_filter([
                self::value($passenger, 'title'),
                self::value($passenger, 'first_name'),
                self::value($passenger, 'last_name'),
            ])));

            $html .= '<div class="tw-passenger-card">';
            $html .= '<div class="tw-passenger-head">';
            $html .= '<div>';
            $html .= '<div class="tw-passenger-name">' . e($name ?: 'Passenger ' . ($index + 1)) . '</div>';
            $html .= '<div class="tw-passenger-type">' . e(self::value($passenger, 'type', 'Passenger')) . '</div>';
            $html .= '</div>';
            $html .= self::badge(self::value($passenger, 'gender', '-'));
            $html .= '</div>';

            $identity = [
                'Date of birth' => self::value($passenger, 'dob'),
                'Nationality' => self::value($passenger, 'nationality'),
                'Frequent flyer' => self::value($passenger, 'frequent_flyer_number'),
            ];
            $document = [
                'Passport no' => self::value($passenger, 'passport_no'),
                'Passport country' => self::value($passenger, 'passport_issue_country'),
                'Passport issued' => self::value($passenger, 'passport_issue_date'),
                'Passport expires' => self::value($passenger, 'passport_exp'),
            ];

            $html .= '<dl class="tw-passenger-details">';
            $html .= self::detailList($identity);
            $html .= '</dl>';
            $html .= '<dl class="tw-passenger-doc-grid">';
            $html .= self::detailList($document);
            $html .= '</dl>';
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function flight(mixed $payload): HtmlString
    {
        $flight = self::normalize($payload);

        if (! is_array($flight) || $flight === []) {
            return self::empty('No flight snapshot stored.');
        }

        $segments = self::value($flight, 'segments', []);
        $segments = is_array($segments) ? $segments : [];

        $html = '<div class="space-y-4">';
        $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<div class="flex flex-wrap items-start justify-between gap-4">';
        $html .= '<div class="flex items-center gap-3">';

        if ($logo = self::value($flight, 'airlineLogo')) {
            $html .= '<img class="h-10 w-10 rounded bg-white object-contain ring-1 ring-gray-200" src="' . e($logo) . '" alt="">';
        }

        $html .= '<div>';
        $html .= '<div class="text-base font-semibold text-gray-950 dark:text-white">' . e(self::value($flight, 'airline', 'Unknown airline')) . '</div>';
        $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . e(self::value($flight, 'airlineCode', '-') . ' / ' . self::value($flight, 'validatingCode', '-')) . '</div>';
        $html .= '</div></div>';
        $html .= '<div class="text-right">';
        $html .= '<div class="text-base font-semibold text-gray-950 dark:text-white">' . e(self::money(self::value($flight, 'price'), self::value($flight, 'currency'))) . '</div>';
        $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . e(self::value($flight, 'fareType', '-') . ' fare') . '</div>';
        $html .= '</div></div>';
        $html .= '<div class="mt-4">';
        $html .= self::definitionGrid([
            'Cabin' => trim(self::value($flight, 'cabin', '-') . ' ' . self::value($flight, 'cabinCode', '')),
            'Stops' => self::value($flight, 'stops', '0'),
            'Direction' => self::value($flight, 'directionInd'),
            'Ticket type' => self::value($flight, 'ticketType'),
            'Refundable' => self::yesNo(self::value($flight, 'isRefundable')),
            'Passport mandatory' => self::yesNo(self::value($flight, 'isPassportMandatory')),
            'Base fare' => self::money(self::value($flight, 'baseFare'), self::value($flight, 'currency')),
            'Tax' => self::money(self::value($flight, 'totalTax'), self::value($flight, 'currency')),
        ]);
        $html .= '</div></div>';

        if ($segments !== []) {
            $html .= '<div class="space-y-3">';

            foreach ($segments as $index => $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
                $html .= '<div class="mb-3 flex items-center justify-between gap-3">';
                $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">Segment ' . e((string) ($index + 1)) . '</div>';
                $html .= self::badge(trim(self::value($segment, 'airlineCode', self::value($flight, 'airlineCode', '')) . ' ' . self::value($segment, 'flightNumber', '')));
                $html .= '</div>';
                $html .= '<div class="grid gap-4 md:grid-cols-[1fr_auto_1fr] md:items-center">';
                $html .= self::airportBlock('Depart', $segment, 'from');
                $html .= '<div class="hidden text-center text-gray-400 md:block">--></div>';
                $html .= self::airportBlock('Arrive', $segment, 'to');
                $html .= '</div>';
                $html .= '<div class="mt-4">';
                $html .= self::definitionGrid([
                    'Departure' => trim(self::value($segment, 'departDate', '-') . ' ' . self::value($segment, 'departTime', '')),
                    'Arrival' => trim(self::value($segment, 'arriveDate', '-') . ' ' . self::value($segment, 'arriveTime', '')),
                    'Duration' => self::value($segment, 'duration', self::value($segment, 'journeyDuration')),
                    'Cabin' => self::value($segment, 'cabin', self::value($flight, 'cabin')),
                    'Baggage' => self::value($segment, 'baggage'),
                    'Equipment' => self::value($segment, 'equipment'),
                ]);
                $html .= '</div></div>';
            }

            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function extras(mixed $payload): HtmlString
    {
        $extras = self::normalize($payload);

        if (! is_array($extras) || $extras === []) {
            return self::empty('No extra services stored.');
        }

        $html = '<div class="space-y-4">';
        $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= self::definitionGrid([
            'Total amount' => self::money(self::value($extras, 'total_amount'), self::value($extras, 'currency')),
            'Currency' => self::value($extras, 'currency'),
        ]);
        $html .= '</div>';

        foreach (['baggage' => 'Baggage', 'meal' => 'Meals'] as $key => $label) {
            $items = self::value($extras, $key, []);
            $items = is_array($items) ? $items : [];

            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">' . e($label) . '</div>';
            $html .= $items === []
                ? '<div class="text-sm text-gray-500 dark:text-gray-400">No ' . e(strtolower($label)) . ' selected.</div>'
                : self::tableFromItems($items);
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function apiResponse(mixed $payload, string $emptyMessage): HtmlString
    {
        $response = self::normalize($payload);

        if (! is_array($response) || $response === []) {
            return self::empty($emptyMessage);
        }

        $summary = self::responseSummary($response);

        $html = '<div class="space-y-4">';

        if ($summary !== []) {
            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= self::definitionGrid($summary);
            $html .= '</div>';
        }

        $html .= '<details class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Full structured response</summary>';
        $html .= '<div class="mt-4">' . self::tree($response) . '</div>';
        $html .= '</details>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public static function storedResponse(?object $record, string $emptyMessage): HtmlString
    {
        if (! $record) {
            return self::empty($emptyMessage);
        }

        $payload = self::normalize($record->response_payload ?? $record->gateway_response ?? null);

        if (! is_array($payload) || $payload === []) {
            return self::empty($emptyMessage);
        }

        $html = '<div class="space-y-3">';
        $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= self::definitionGrid([
            'Action' => str((string) $record->action)->headline(),
            'When' => optional($record->created_at)->format('d M Y, H:i'),
            'Status' => $record->ticket_status ?: $record->new_payment_status ?: $record->new_booking_status,
            'Airline PNR' => $record->airline_pnr,
            'Message' => $record->message ?: $record->verification_note,
        ]);
        $html .= '</div>';
        $html .= '<details class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Full response</summary>';
        $html .= '<div class="mt-4">' . self::tree($payload) . '</div>';
        $html .= '</details>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public static function paymentVerificationHistory(Collection $records): HtmlString
    {
        if ($records->isEmpty()) {
            return self::empty('No payment verification history yet.');
        }

        $html = '<div class="space-y-3">';

        foreach ($records as $record) {
            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="mb-3 flex flex-wrap items-start justify-between gap-3">';
            $html .= '<div>';
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">' . e(str((string) $record->action)->headline()) . '</div>';
            $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e(optional($record->created_at)->format('d M Y, H:i') ?: '-') . '</div>';
            $html .= '</div>';
            $html .= self::badge($record->new_payment_status ?: '-');
            $html .= '</div>';
            $html .= self::definitionGrid([
                'Verified by' => $record->verifier?->name ?: 'System',
                'Previous status' => $record->previous_payment_status,
                'New status' => $record->new_payment_status,
                'Reference' => $record->payment_reference,
                'Amount' => self::money($record->amount_received, $record->currency),
                'Note' => $record->verification_note,
            ]);
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function ticketingHistory(Collection $records): HtmlString
    {
        if ($records->isEmpty()) {
            return self::empty('No ticketing history yet.');
        }

        $html = '<div class="space-y-3">';

        foreach ($records as $record) {
            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="mb-3 flex flex-wrap items-start justify-between gap-3">';
            $html .= '<div>';
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">' . e(str((string) $record->action)->headline()) . '</div>';
            $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e(optional($record->created_at)->format('d M Y, H:i') ?: '-') . '</div>';
            $html .= '</div>';
            $html .= self::badge($record->ticket_status ?: $record->new_booking_status ?: '-');
            $html .= '</div>';
            $html .= self::definitionGrid([
                'Performed by' => $record->performer?->name ?: 'System',
                'Previous status' => $record->previous_booking_status,
                'New status' => $record->new_booking_status,
                'Ticket status' => $record->ticket_status,
                'Airline PNR' => $record->airline_pnr,
                'UniqueID' => $record->unique_id,
                'Message' => $record->message,
            ]);
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function postTicketingHistory(Collection $records): HtmlString
    {
        if ($records->isEmpty()) {
            return self::empty('No post-ticketing requests yet.');
        }

        $html = '<div class="space-y-3">';

        foreach ($records as $record) {
            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="mb-3 flex flex-wrap items-start justify-between gap-3">';
            $html .= '<div>';
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">' . e(str((string) $record->operation_type)->headline()) . '</div>';
            $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e(optional($record->created_at)->format('d M Y, H:i') ?: '-') . '</div>';
            $html .= '</div>';
            $html .= self::badge($record->status ?: '-');
            $html .= '</div>';
            $html .= self::definitionGrid([
                'Admin' => $record->admin?->name ?: 'System',
                'UniqueID' => $record->unique_id,
                'ptrUniqueID' => $record->ptr_unique_id,
                'Error' => $record->error_message,
                'Note' => $record->admin_note,
            ]);

            $payload = $record->response_payload ?? [];
            if (is_array($payload) && $payload !== []) {
                $html .= '<details class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">';
                $html .= '<summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Response payload</summary>';
                $html .= '<div class="mt-3">' . self::tree($payload) . '</div>';
                $html .= '</details>';
            }

            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function latestTripDetails(?object $record): HtmlString
    {
        if (! $record) {
            return self::empty('Fetch Trip Details to display live ticket status and Airline PNR.');
        }

        $payload = self::normalize($record->response_payload);
        $tripDetails = data_get($payload, 'TripDetailsResponse.TripDetailsResult.TravelItinerary', []);

        if (! is_array($tripDetails) || $tripDetails === []) {
            return self::empty('No Trip Details response stored yet.');
        }

        $html = '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= self::definitionGrid([
            'Booking status' => data_get($tripDetails, 'BookingStatus'),
            'Ticket status' => data_get($tripDetails, 'TicketStatus'),
            'Airline PNR' => $record->airline_pnr,
            'Origin' => data_get($tripDetails, 'Origin'),
            'Destination' => data_get($tripDetails, 'Destination'),
            'Fare type' => data_get($tripDetails, 'FareType'),
        ]);
        $html .= '</div>';

        return new HtmlString($html);
    }

    private static function normalize(mixed $payload): mixed
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
        }

        return $payload;
    }

    private static function responseSummary(array $payload): array
    {
        $flat = self::flatten($payload);
        $summary = [];

        foreach ($flat as $key => $value) {
            $last = strtolower((string) str($key)->afterLast('.'));

            if (in_array($last, ['success', 'status', 'uniqueid', 'errors', 'target', 'tkttimelimit', 'user_id'], true)) {
                $summary[(string) str($key)->afterLast('.')] = self::scalar($value);
            }
        }

        return $summary;
    }

    private static function flatten(array $payload, string $prefix = ''): array
    {
        $flat = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += self::flatten($value, $path);
                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    private static function tree(mixed $value): string
    {
        if (! is_array($value)) {
            return '<span class="text-sm text-gray-700 dark:text-gray-200">' . e(self::scalar($value)) . '</span>';
        }

        $html = '<div class="space-y-2">';

        foreach ($value as $key => $child) {
            $html .= '<div class="rounded-md border border-gray-100 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">';
            $html .= '<div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">' . e((string) $key) . '</div>';
            $html .= self::tree($child);
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private static function airportBlock(string $label, array $segment, string $prefix): string
    {
        $code = self::value($segment, $prefix);
        $city = self::value($segment, $prefix . 'City');
        $airport = self::value($segment, $prefix . 'Airport');
        $country = self::value($segment, $prefix . 'Country');

        return '<div>' .
            '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e($label) . '</div>' .
            '<div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">' . e($code ?: '-') . '</div>' .
            '<div class="text-sm text-gray-700 dark:text-gray-200">' . e($city ?: '-') . '</div>' .
            '<div class="text-xs text-gray-500 dark:text-gray-400">' . e(trim(($airport ?: '') . ', ' . ($country ?: ''), ', ')) . '</div>' .
            '</div>';
    }

    private static function definitionGrid(array $items): string
    {
        $html = '<dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">';

        foreach ($items as $label => $value) {
            if (blank($value)) {
                $value = '-';
            }

            $html .= '<div>';
            $html .= '<dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e((string) $label) . '</dt>';
            $html .= '<dd class="mt-1 break-words text-sm text-gray-950 dark:text-white">' . e(self::scalar($value)) . '</dd>';
            $html .= '</div>';
        }

        return $html . '</dl>';
    }

    private static function detailList(array $items): string
    {
        $html = '';

        foreach ($items as $label => $value) {
            if (blank($value)) {
                $value = '-';
            }

            $html .= '<div class="tw-passenger-detail">';
            $html .= '<dt>' . e((string) $label) . '</dt>';
            $html .= '<dd>' . e(self::scalar($value)) . '</dd>';
            $html .= '</div>';
        }

        return $html;
    }

    private static function tableFromItems(array $items): string
    {
        if ($items === []) {
            return '';
        }

        $rows = self::isAssoc($items) ? [$items] : $items;
        $headers = collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->flatMap(fn (array $row): array => array_keys($row))
            ->unique()
            ->take(8)
            ->values()
            ->all();

        if ($headers === []) {
            return self::tree($items);
        }

        $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e((string) $header) . '</th>';
        }

        $html .= '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-white/10">';

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $html .= '<tr>';

            foreach ($headers as $header) {
                $html .= '<td class="px-3 py-2 text-gray-950 dark:text-white">' . e(self::scalar($row[$header] ?? '-')) . '</td>';
            }

            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }

    private static function badge(string $value): string
    {
        return '<span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">' . e($value ?: '-') . '</span>';
    }

    private static function statusPill(string $label, ?string $status): string
    {
        $tone = match ($status) {
            'paid', 'ticketed', 'confirmed' => 'good',
            'awaiting_bank_transfer', 'pending', 'on_hold' => 'warn',
            'failed', 'ticketing_failed', 'cancelled' => 'bad',
            default => 'neutral',
        };

        return '<span class="tw-status-pill tw-status-' . e($tone) . '"><span>' . e($label) . '</span><strong>' . e(self::label($status)) . '</strong></span>';
    }

    private static function timelineItem(string $label, ?string $value, bool $isComplete): string
    {
        return '<div class="tw-timeline-item ' . ($isComplete ? 'is-complete' : '') . '">' .
            '<span class="tw-timeline-dot"></span>' .
            '<div><div class="tw-timeline-label">' . e($label) . '</div>' .
            '<div class="tw-timeline-value">' . e($value ?: '-') . '</div></div>' .
            '</div>';
    }

    private static function queueLabel(object $record): string
    {
        if (($record->payment_status ?? null) === 'awaiting_bank_transfer') {
            return 'Awaiting transfer verification';
        }

        if (($record->payment_status ?? null) === 'paid' && in_array($record->booking_status ?? null, ['failed', 'ticketing_failed'], true)) {
            return 'Ticketing failed';
        }

        if (($record->payment_status ?? null) === 'paid' && ! ($record->ticket_ordered ?? false) && ($record->booking_status ?? null) !== 'ticketed') {
            return 'Ready to ticket';
        }

        if (($record->booking_status ?? null) === 'ticketed') {
            return 'Ticketed';
        }

        if (($record->payment_status ?? null) === 'pending') {
            return 'Pending payment';
        }

        return 'Booking review';
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str($value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function empty(string $message): HtmlString
    {
        return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">' . e($message) . '</div>');
    }

    private static function value(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }

    private static function money(mixed $amount, mixed $currency): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return trim((string) ($currency ?: '') . ' ' . number_format((float) $amount, 2));
    }

    private static function yesNo(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    private static function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '-';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '-';
        }

        return (string) $value;
    }

    private static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

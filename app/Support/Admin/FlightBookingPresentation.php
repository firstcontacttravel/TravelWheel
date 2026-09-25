<?php

namespace App\Support\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class FlightBookingPresentation
{
    /**
     * The booking workspace hero.
     *
     * The data below is unchanged; only the markup moved. It used to be built
     * by string concatenation in this file, which meant the console's own
     * primitives — the drawn route connector, the status dots, the mono face
     * for anything read aloud — could not reach it, and ~900 lines of tw-*
     * CSS existed to compensate.
     */
    public static function workspaceSummary(object $record): HtmlString
    {
        $flight = self::normalize($record->flight_snapshot ?? []);
        $flight = is_array($flight) ? $flight : [];
        $groups = self::itineraryGroups($flight);
        $flatSegments = collect($groups)->flatMap(fn (array $group): array => $group['segments'])->values()->all();
        $firstSegment = $flatSegments[0] ?? [];
        $lastSegment = $flatSegments !== [] ? $flatSegments[array_key_last($flatSegments)] : [];
        $departure = self::segmentDateTime($firstSegment, 'depart');
        $arrival = self::segmentDateTime($lastSegment, 'arrive');
        $passengers = (int) ($record->adult_count + $record->child_count + $record->infant_count);
        $legsCount = max(1, count($groups));
        $stops = max(0, count($flatSegments) - $legsCount);

        $facts = ['Total' => self::money($record->total_price, $record->currency)];

        if ((float) ($record->markup_amount ?? 0) > 0) {
            $facts['Service charge'] = self::money($record->markup_amount, $record->currency);
        }

        $facts['Airline'] = $record->airline ?: 'Unknown airline';
        $facts['Passengers'] = $passengers.' passenger'.($passengers === 1 ? '' : 's');
        $facts['Legs'] = $legsCount.' leg'.($legsCount === 1 ? '' : 's');

        return new HtmlString(view('filament.booking.hero', [
            'ref' => $record->booking_ref ?: 'Booking',
            'meta' => array_values(array_filter([
                self::tripLabel($flight, $record->trip_type ?? null),
                $record->unique_id ?: null,
                $record->fare_type ?: null,
            ])),
            'states' => [
                'Booking' => self::stateDot($record->booking_status),
                'Payment' => self::stateDot($record->payment_status),
                'Queue' => self::stateDot(self::queueLabel($record)),
            ],
            'from' => [
                'code' => (string) (self::value($firstSegment, 'from', self::value($firstSegment, 'airportOriginCode', '')) ?: '---'),
                'city' => (string) self::value($firstSegment, 'fromCity', ''),
            ],
            'to' => [
                'code' => (string) (self::value($lastSegment, 'to', self::value($lastSegment, 'airportDestinationCode', '')) ?: '---'),
                'city' => (string) self::value($lastSegment, 'toCity', ''),
            ],
            'note' => $stops === 0 ? 'Non-stop' : $stops.' stop'.($stops === 1 ? '' : 's'),
            'facts' => $facts,
            'steps' => [
                ['label' => 'Created', 'value' => self::watDateTime($record->created_at), 'done' => true],
                ['label' => 'Payment', 'value' => $record->payment_verified_at ? self::watDateTime($record->payment_verified_at) : self::label($record->payment_status), 'done' => $record->payment_status === 'paid'],
                ['label' => 'Ticket', 'value' => $record->ticket_ordered_at ? self::watDateTime($record->ticket_ordered_at) : self::label($record->booking_status), 'done' => (bool) $record->ticket_ordered],
                ['label' => 'Depart', 'value' => $departure ?: '---', 'done' => filled($departure)],
                ['label' => 'Arrive', 'value' => $arrival ?: '---', 'done' => filled($arrival)],
            ],
            'legs' => collect($groups)
                ->map(fn (array $group): array => self::heroLeg($group))
                ->filter()
                ->values()
                ->all(),
        ])->render());
    }

    /**
     * A status word mapped onto the console's dot language, so a filled dot
     * means the same thing here as it does in the queue table.
     *
     * @return array{text: string, tone: string, shape: string}
     */
    private static function stateDot(?string $state): array
    {
        $text = self::label($state) ?: '---';

        [$tone, $shape] = match ($text) {
            'Ticketing Failed', 'Ticketing failed', 'Failed', 'Cancelled' => ['critical', ''],
            'Awaiting Bank Transfer', 'Awaiting transfer', 'On Hold', 'Awaiting Deposit' => ['warning', 'tc-status-progress'],
            'Ticketed', 'Paid', 'Confirmed' => ['positive', ''],
            'Ready to ticket', 'Review', 'Partially Paid' => ['info', 'tc-status-progress'],
            'Pending', 'Pending payment' => ['idle', 'tc-status-pending'],
            default => ['idle', ''],
        };

        return ['text' => $text, 'tone' => $tone, 'shape' => $shape];
    }


    public static function passengers(mixed $payload): HtmlString
    {
        $passengers = self::normalize($payload);

        if (! is_array($passengers) || $passengers === []) {
            return new HtmlString(view('filament.booking.passengers', ['passengers' => []])->render());
        }

        if (self::isAssoc($passengers)) {
            $passengers = [$passengers];
        }

        $cards = [];

        foreach (array_values($passengers) as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $name = trim(implode(' ', array_filter([
                self::value($passenger, 'title'),
                self::value($passenger, 'first_name'),
                self::value($passenger, 'last_name'),
            ])));

            $cards[] = [
                'name' => $name ?: 'Passenger '.($index + 1),
                'type' => (string) self::value($passenger, 'type', 'Passenger'),
                'rows' => array_filter([
                    'Date of birth' => self::value($passenger, 'dob'),
                    'Nationality' => self::value($passenger, 'nationality'),
                    'Gender' => self::value($passenger, 'gender'),
                    'Frequent flyer' => self::value($passenger, 'frequent_flyer_number'),
                    'Passport' => self::value($passenger, 'passport_no'),
                    'Issued by' => self::value($passenger, 'passport_issue_country'),
                    'Issued' => self::value($passenger, 'passport_issue_date'),
                    'Expires' => self::value($passenger, 'passport_exp'),
                ], fn ($value): bool => filled($value)),
            ];
        }

        return new HtmlString(view('filament.booking.passengers', ['passengers' => $cards])->render());
    }

    public static function flight(mixed $payload): HtmlString
    {
        $flight = self::normalize($payload);

        if (! is_array($flight) || $flight === []) {
            return self::empty('No flight snapshot stored.');
        }

        $facts = [
            'Fare' => (string) self::value($flight, 'fareType', '---'),
            'Cabin' => self::cabinText($flight),
            'Total' => self::money(self::value($flight, 'price'), self::value($flight, 'currency')),
        ];

        if ((float) self::value($flight, 'markupAmount', 0) > 0) {
            $facts['Supplier fare'] = self::money(self::value($flight, 'supplierPrice'), self::value($flight, 'currency'));
            $facts['Service charge'] = self::money(self::value($flight, 'markupAmount'), self::value($flight, 'currency'));
        }

        $facts['Refundable'] = self::yesNo(self::value($flight, 'isRefundable'));

        $groups = [];

        foreach (self::itineraryGroups($flight) as $index => $group) {
            $segments = array_values(array_filter($group['segments'] ?? [], 'is_array'));

            if ($segments === []) {
                continue;
            }

            $stops = max(0, count($segments) - 1);
            $duration = self::groupDurationLabel($segments);

            $groups[] = [
                'label' => (string) ($group['label'] ?? 'Leg '.($index + 1)),
                'meta' => array_values(array_filter([
                    $stops === 0 ? 'Non-stop' : $stops.' stop'.($stops === 1 ? '' : 's'),
                    filled($duration) ? $duration : null,
                ])),
                'segments' => collect($segments)
                    ->map(fn (array $segment, int $i): array => self::segmentRow(
                        $segment,
                        $flight,
                        $i > 0 ? $segments[$i - 1] : null,
                    ))
                    ->all(),
            ];
        }

        return new HtmlString(view('filament.booking.itinerary', [
            'carrier' => [
                'name' => (string) self::value($flight, 'airline', 'Unknown airline'),
                'codes' => collect([self::value($flight, 'airlineCode'), self::value($flight, 'validatingCode')])
                    ->filter()->unique()->implode(' / '),
            ],
            'facts' => $facts,
            'groups' => $groups,
        ])->render());
    }

    /**
     * One segment, plus the connection time before it.
     *
     * The layover is the number people actually worry about and the previous
     * layout had nowhere to put it — each segment was a separate bordered card
     * with nothing between them, so "arrive DEL 06:10" and "depart DEL 08:25"
     * read as two unrelated flights.
     *
     * @return array<string, mixed>
     */
    private static function segmentRow(array $segment, array $flight, ?array $previous): array
    {
        return [
            'depart' => [
                'time' => self::segmentClock($segment, 'depart'),
                'code' => (string) (self::value($segment, 'from', self::value($segment, 'airportOriginCode', '')) ?: '---'),
                'place' => (string) self::value($segment, 'fromCity', self::value($segment, 'fromAirport', '')),
            ],
            'arrive' => [
                'time' => self::segmentClock($segment, 'arrive'),
                'code' => (string) (self::value($segment, 'to', self::value($segment, 'airportDestinationCode', '')) ?: '---'),
                'place' => (string) self::value($segment, 'toCity', self::value($segment, 'toAirport', '')),
            ],
            'flight' => self::segmentFlightLabel($segment, $flight),
            'meta' => array_values(array_filter([
                self::segmentDurationLabel($segment),
                self::cabinText($segment, $flight),
                self::value($segment, 'baggage') ?: null,
            ])),
            'layover' => $previous ? self::layoverLabel($previous, $segment) : null,
        ];
    }

    /** The clock time alone; the date is carried by the leg header. */
    private static function segmentClock(array $segment, string $which): string
    {
        $stamp = self::segmentMoment($segment, $which);

        return $stamp?->format('H:i') ?? '--:--';
    }

    private static function segmentMoment(array $segment, string $which): ?CarbonImmutable
    {
        $keys = $which === 'depart'
            ? ['departDT', 'DepartureDateTime', 'departureDateTime', 'depart_at']
            : ['arriveDT', 'ArrivalDateTime', 'arrivalDateTime', 'arrive_at'];

        foreach ($keys as $key) {
            $value = self::value($segment, $key);

            if (blank($value)) {
                continue;
            }

            try {
                return CarbonImmutable::parse((string) $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private static function layoverLabel(array $previous, array $segment): ?string
    {
        $arrive = self::segmentMoment($previous, 'arrive');
        $depart = self::segmentMoment($segment, 'depart');

        if (! $arrive || ! $depart) {
            return null;
        }

        $minutes = (int) $arrive->diffInMinutes($depart, false);

        if ($minutes <= 0) {
            return null;
        }

        $airport = (string) (self::value($segment, 'fromCity') ?: self::value($segment, 'from', ''));

        return trim(sprintf(
            '%dh %02dm connection%s',
            intdiv($minutes, 60),
            $minutes % 60,
            filled($airport) ? ' in '.$airport : '',
        ));
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
            $html .= '<div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">'.e($label).'</div>';
            $html .= $items === []
                ? '<div class="text-sm text-gray-500 dark:text-gray-400">No '.e(strtolower($label)).' selected.</div>'
                : self::tableFromItems($items);
            $html .= '</div>';
        }

        return new HtmlString($html.'</div>');
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
        $html .= '<div class="mt-4">'.self::tree($response).'</div>';
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
            'When' => self::watDateTime($record->created_at),
            'Status' => $record->ticket_status ?: $record->new_payment_status ?: $record->new_booking_status,
            'Airline PNR' => $record->airline_pnr,
            'Message' => $record->message ?: $record->verification_note,
        ]);
        $html .= '</div>';
        $html .= '<details class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Full response</summary>';
        $html .= '<div class="mt-4">'.self::tree($payload).'</div>';
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
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">'.e(str((string) $record->action)->headline()).'</div>';
            $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">'.e(self::watDateTime($record->created_at)).'</div>';
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

        return new HtmlString($html.'</div>');
    }

    public static function ticketingHistory(Collection $records): HtmlString
    {
        if ($records->isEmpty()) {
            return self::empty('No ticketing history yet.');
        }

        return self::historyFeed(
            $records,
            'ticketing',
            fn ($record): array => self::historyItem(
                str((string) $record->action)->headline()->toString(),
                self::watDateTime($record->created_at),
                $record->ticket_status ?: $record->new_booking_status ?: '-',
                [
                    'Performed by' => $record->performer?->name ?: 'System',
                    'Previous status' => $record->previous_booking_status,
                    'New status' => $record->new_booking_status,
                    'Ticket status' => $record->ticket_status,
                    'Airline PNR' => $record->airline_pnr,
                    'UniqueID' => $record->unique_id,
                    'Message' => $record->message,
                ],
            ),
        );
    }

    public static function postTicketingHistory(Collection $records): HtmlString
    {
        if ($records->isEmpty()) {
            return self::empty('No post-ticketing requests yet.');
        }

        return self::historyFeed(
            $records,
            'post-ticketing',
            function ($record): array {
                $requestPayload = self::normalize($record->request_payload ?? []);
                $responsePayload = self::normalize($record->response_payload ?? []);

                $summary = [
                    'Admin' => $record->admin?->name ?: 'System',
                    'UniqueID' => $record->unique_id,
                    'ptrUniqueID' => $record->ptr_unique_id,
                    'Result' => self::postTicketingResponseValue($responsePayload, ['Success']) ?? ($record->status ?: '-'),
                    'Processing time' => self::postTicketingResponseValue($responsePayload, ['ProcessingTime']),
                    'Message' => self::postTicketingResponseValue($responsePayload, ['Message']),
                    'Error' => $record->error_message,
                    'Note' => $record->admin_note,
                ];

                $preferenceOption = self::postTicketingResponseValue($responsePayload, ['PreferenceOption', 'OptionID', 'OptionId', 'Option']);

                if (filled($preferenceOption)) {
                    $summary['Preference option'] = $preferenceOption;
                } elseif ($record->operation_type === 'reissue_quote') {
                    $summary['Process option'] = 'Use 1 unless provider support gives a different option ID.';
                }

                $body = self::definitionGrid($summary);

                if (is_array($requestPayload) && $requestPayload !== []) {
                    $body .= self::postTicketingRequestPanel($requestPayload);
                }

                if (is_array($responsePayload) && $responsePayload !== []) {
                    $body .= self::postTicketingResponsePanel($responsePayload, (string) $record->operation_type);
                }

                return self::historyItem(
                    str((string) $record->operation_type)->headline()->toString(),
                    self::watDateTime($record->created_at),
                    $record->status ?: '-',
                    [],
                    $body,
                );
            },
        );
    }

    private static function postTicketingRequestPanel(array $payload): string
    {
        $items = [
            'Selected quote PTR' => $payload['ptrUniqueID'] ?? ($payload['_selectedQuotePtrUniqueID'] ?? null),
            'Preference option' => $payload['PreferenceOption'] ?? null,
            'Affected flight' => $payload['_reissueScopeLabel'] ?? null,
            'Remark' => $payload['remark'] ?? null,
        ];

        $html = '<div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">';
        $html .= '<div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Request details</div>';
        $html .= self::definitionGrid($items);

        $passengers = $payload['paxDetails'] ?? [];
        if (is_array($passengers) && $passengers !== []) {
            $html .= '<div class="mt-4">';
            $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Passengers</div>';
            $html .= self::tableFromItems(collect($passengers)->map(fn ($passenger): array => is_array($passenger) ? [
                'Type' => $passenger['type'] ?? '-',
                'Name' => trim(($passenger['title'] ?? '').' '.($passenger['firstName'] ?? '').' '.($passenger['lastName'] ?? '')),
                'E-ticket' => $passenger['eTicket'] ?? '-',
            ] : [])->all());
            $html .= '</div>';
        }

        $replacementSegments = $payload['_displayReplacementSegments'] ?? [];
        if (is_array($replacementSegments) && $replacementSegments !== []) {
            $html .= '<div class="mt-4">';
            $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Selected replacement option</div>';
            $html .= self::tableFromItems(collect($replacementSegments)->map(fn ($segment): array => is_array($segment) ? [
                'From' => $segment['airportOriginCode'] ?? ($segment['from'] ?? '-'),
                'To' => $segment['airportDestinationCode'] ?? ($segment['to'] ?? '-'),
                'Depart' => self::watDateTime($segment['departDT'] ?? ($segment['departureDate'] ?? null), 'D, d M Y H:i'),
                'Arrive' => self::watDateTime($segment['arriveDT'] ?? null, 'D, d M Y H:i'),
                'Cabin' => $segment['cabin'] ?? ($segment['cabinPreference'] ?? '-'),
                'Flight' => trim(($segment['airlineCode'] ?? '').' '.($segment['flightNumber'] ?? ($segment['flightNo'] ?? ''))),
            ] : [])->all());
            $html .= '</div>';
        }

        $segments = $payload['OriginDestinationInfo'] ?? [];
        if (is_array($segments) && $segments !== []) {
            $html .= '<div class="mt-4">';
            $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Proposed itinerary sent to provider</div>';
            $html .= self::tableFromItems(collect($segments)->map(fn ($segment): array => is_array($segment) ? [
                'From' => $segment['airportOriginCode'] ?? '-',
                'To' => $segment['airportDestinationCode'] ?? '-',
                'Date' => $segment['departureDate'] ?? '-',
                'Cabin' => $segment['cabinPreference'] ?? '-',
                'Flight' => trim(($segment['airlineCode'] ?? '').' '.($segment['flightNumber'] ?? '')),
            ] : [])->all());
            $html .= '</div>';
        }

        return $html.'</div>';
    }

    private static function postTicketingResponsePanel(array $payload, string $operationType): string
    {
        $rows = self::postTicketingResponseRows($payload, $operationType);

        if ($rows === []) {
            return '';
        }

        $html = '<div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">';
        $html .= '<div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Provider response</div>';
        $html .= self::definitionGrid($rows);
        $html .= self::postTicketingVoidTables($payload);
        $html .= self::postTicketingRefundTables($payload);
        $html .= self::postTicketingReissueQuoteTables($payload);
        $html .= self::postTicketingPtrStatusTables($payload);
        $html .= '</div>';

        return $html;
    }

    private static function postTicketingVoidTables(array $payload): string
    {
        $voidRows = [];

        foreach (['VoidQuotes', 'VoidDetails'] as $key) {
            foreach (self::findNamedArrays($payload, $key) as $items) {
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $voidRows[] = [
                        'Type' => $item['PassengerType'] ?? $item['type'] ?? '-',
                        'Name' => trim(($item['Title'] ?? $item['title'] ?? '').' '.($item['FirstName'] ?? $item['firstName'] ?? '').' '.($item['LastName'] ?? $item['lastName'] ?? '')),
                        'E-ticket' => $item['ETicket'] ?? $item['eTicket'] ?? '-',
                        'Admin charge' => self::moneyNode($item['AdminCharges'] ?? null),
                        'GST' => self::moneyNode($item['GSTCharge'] ?? $item['GSTCharges'] ?? null),
                        'Voiding fee' => self::moneyNode($item['TotalVoidingFee'] ?? null),
                        'Refund' => self::moneyNode($item['TotalRefundAmount'] ?? null),
                    ];
                }
            }
        }

        if ($voidRows === []) {
            return '';
        }

        return '<div class="mt-4">'.
            '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Void fees and refund</div>'.
            self::tableFromItems($voidRows).
            '</div>';
    }

    private static function postTicketingRefundTables(array $payload): string
    {
        $refundRows = [];

        foreach (self::findNamedArrays($payload, 'PaxDetails') as $items) {
            foreach ($items as $item) {
                if (! is_array($item) || ! is_array($item['QuotedFares'] ?? null)) {
                    continue;
                }

                $fares = $item['QuotedFares'];
                $refundRows[] = [
                    'Type' => $item['PassengerType'] ?? $item['type'] ?? '-',
                    'Name' => trim(($item['Title'] ?? $item['title'] ?? '').' '.($item['FirstName'] ?? $item['firstName'] ?? '').' '.($item['LastName'] ?? $item['lastName'] ?? '')),
                    'E-ticket' => $item['ETicket'] ?? $item['eTicket'] ?? '-',
                    'Total fare' => self::moneyNode($fares['TotalFare'] ?? null),
                    'Unused fare' => self::moneyNode($fares['UnusedFare'] ?? null),
                    'Cancel charge' => self::moneyNode($fares['CancellationCharge'] ?? null),
                    'No-show' => self::moneyNode($fares['NoShowCharge'] ?? null),
                    'Tax' => self::moneyNode($fares['Tax'] ?? null),
                    'GST' => self::moneyNode($fares['GSTCharge'] ?? null),
                    'Refund charges' => self::moneyNode($fares['TotalRefundCharges'] ?? null),
                    'Refund amount' => self::moneyNode($fares['TotalRefundAmount'] ?? null),
                ];
            }
        }

        if ($refundRows === []) {
            return '';
        }

        return '<div class="mt-4">'.
            '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Refund quote amounts</div>'.
            self::tableFromItems($refundRows).
            '</div>';
    }

    private static function postTicketingReissueQuoteTables(array $payload): string
    {
        $html = '';
        $segmentRows = [];
        $fareRows = [];
        $totalRows = [];

        foreach (self::findNamedArrays($payload, 'RequestedPreferences') as $preferences) {
            foreach ($preferences as $preference) {
                if (! is_array($preference)) {
                    continue;
                }

                $option = $preference['PreferenceOption'] ?? '-';

                foreach (($preference['QuotedSegments'] ?? []) as $segment) {
                    if (! is_array($segment)) {
                        continue;
                    }

                    $segmentRows[] = [
                        'Option' => $option,
                        'From' => $segment['DepartureAirportLocationCode'] ?? '-',
                        'To' => $segment['ArrivalAirportLocationCode'] ?? '-',
                        'Depart' => self::watDateTime($segment['DepartureDateTime'] ?? null, 'D, d M Y H:i'),
                        'Arrive' => self::watDateTime($segment['ArrivalDateTime'] ?? null, 'D, d M Y H:i'),
                        'Airline' => $segment['AirlineCode'] ?? '-',
                        'Flight' => $segment['FlightNumber'] ?? '-',
                        'Class' => $segment['BookingClass'] ?? '-',
                    ];
                }

                foreach (($preference['QuotedFares'] ?? []) as $fare) {
                    if (! is_array($fare)) {
                        continue;
                    }

                    $fareRows[] = [
                        'Option' => $option,
                        'Passenger' => data_get($fare, 'PassengerTypeQuantity.Code') ?? data_get($fare, 'PassengerTypeQuantity.Quantity') ?? '-',
                        'Base diff' => self::moneyNode($fare['BaseFareDifference'] ?? null),
                        'Tax diff' => self::moneyNode($fare['TaxDifference'] ?? null),
                        'Penalty' => self::moneyNode($fare['Penalty'] ?? null),
                        'No-show' => self::moneyNode($fare['NoShowPenalty'] ?? null),
                        'GST' => self::moneyNode($fare['GST'] ?? null),
                        'Total diff' => self::moneyNode($fare['TotalFareDifference'] ?? null),
                    ];

                    $totalRows[] = [
                        'Option' => $option,
                        'Passenger' => data_get($fare, 'PassengerTypeQuantity.Code') ?? data_get($fare, 'PassengerTypeQuantity.Quantity') ?? '-',
                        'Amount to collect' => self::moneyNode($fare['TotalFareDifference'] ?? null),
                        'Penalty' => self::moneyNode($fare['Penalty'] ?? null),
                    ];
                }
            }
        }

        if ($segmentRows === [] && $fareRows === [] && self::isReissueQuotePending($payload)) {
            return '<div class="mt-4 rounded-lg border border-warning-200 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">'.
                '<div class="font-semibold">'.e('Reissue quote pricing is still processing.').'</div>'.
                '<div class="mt-1">'.e('Flightslogic returned a PTR reference but no fare amounts yet. Run PTR Status for this quote PTR after the processing time; once completed, the fare differences will show here.').'</div>'.
                '</div>';
        }

        if ($totalRows !== []) {
            $html .= '<div class="mt-4">'.
                '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reissue quote price summary</div>'.
                self::tableFromItems($totalRows).
                '</div>';
        }

        if ($segmentRows !== []) {
            $html .= '<div class="mt-4">'.
                '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reissue quote segments</div>'.
                self::tableFromItems($segmentRows).
                '</div>';
        }

        if ($fareRows !== []) {
            $html .= '<div class="mt-4">'.
                '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reissue quote fare differences</div>'.
                self::tableFromItems($fareRows).
                '</div>';
        }

        return $html;
    }

    private static function isReissueQuotePending(array $payload): bool
    {
        $flat = self::flatten($payload);
        $hasReissueQuoteResult = collect(array_keys($flat))
            ->contains(fn (string $path): bool => str_contains($path, 'ReissueQuoteResult'));

        if (! $hasReissueQuoteResult) {
            return false;
        }

        foreach ($flat as $path => $value) {
            $last = (string) str($path)->afterLast('.');

            if (in_array($last, ['Status', 'PtrStatus'], true)
                && in_array(strtolower(str_replace([' ', '-', '_'], '', (string) $value)), ['inprocess', 'pending', 'submitted'], true)) {
                return true;
            }
        }

        return false;
    }

    private static function postTicketingPtrStatusTables(array $payload): string
    {
        $details = self::ptrDetailsFromPayload($payload);

        if ($details === []) {
            return '';
        }

        $html = '<div class="mt-4">';
        $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">PTR details</div>';
        $html .= self::tableFromItems(collect($details)->map(fn (array $detail): array => [
            'Type' => $detail['PtrType'] ?? '-',
            'Status' => $detail['PtrStatus'] ?? '-',
            'Resolution' => $detail['Resolution'] ?? '-',
            'UniqueID' => $detail['UniqueID'] ?? '-',
            'PTR' => $detail['PtrUniqueID'] ?? '-',
            'Passengers' => is_array($detail['PaxDetails'] ?? null) ? count($detail['PaxDetails']) : '-',
        ])->all());

        foreach ($details as $detail) {
            $passengers = $detail['PaxDetails'] ?? [];

            if (! is_array($passengers) || $passengers === []) {
                continue;
            }

            $html .= '<div class="mt-3">';
            $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">PTR passengers</div>';
            $html .= self::tableFromItems(collect($passengers)->map(fn ($passenger): array => is_array($passenger) ? [
                'Type' => $passenger['PassengerType'] ?? $passenger['type'] ?? '-',
                'Name' => trim(($passenger['Title'] ?? $passenger['title'] ?? '').' '.($passenger['FirstName'] ?? $passenger['firstName'] ?? '').' '.($passenger['LastName'] ?? $passenger['lastName'] ?? '')),
                'E-ticket' => $passenger['ETicket'] ?? $passenger['eTicket'] ?? '-',
            ] : [])->all());
            $html .= '</div>';
        }

        foreach ($details as $detail) {
            $type = strtolower(str_replace([' ', '-', '_'], '', (string) ($detail['PtrType'] ?? '')));

            if ($type === 'refundquote') {
                $html .= self::ptrStatusRefundQuoteDetails($detail);
            }

            if ($type === 'reissuequote') {
                $html .= self::ptrStatusReissueQuoteDetails($detail);
            }

            if ($type === 'reissue') {
                $html .= '<div class="mt-3 rounded-lg border border-info-200 bg-info-50 p-3 text-sm text-info-800 dark:border-info-500/30 dark:bg-info-500/10 dark:text-info-200">';
                $html .= e('Reissue PTR status confirms the new e-ticket. Flight details should be refreshed from Trip Details and the stored reissue itinerary.');
                $html .= '</div>';
            }
        }

        return $html.'</div>';
    }

    private static function ptrDetailsFromPayload(array $payload): array
    {
        $details = [];

        foreach (self::findNamedArrays($payload, 'PtrDetails') as $items) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $details[] = $item;
                }
            }
        }

        return $details;
    }

    private static function ptrStatusRefundQuoteDetails(array $detail): string
    {
        $rows = [];

        foreach (($detail['PaxDetails'] ?? []) as $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $fare = $passenger['QuotedFares'] ?? [];

            if (! is_array($fare) || $fare === []) {
                continue;
            }

            $rows[] = [
                'Passenger' => trim(($passenger['Title'] ?? '').' '.($passenger['FirstName'] ?? '').' '.($passenger['LastName'] ?? '')),
                'E-ticket' => $passenger['ETicket'] ?? '-',
                'Total fare' => self::moneyNode($fare['TotalFare'] ?? null),
                'Unused fare' => self::moneyNode($fare['UnusedFare'] ?? null),
                'Cancel charge' => self::moneyNode($fare['CancellationCharge'] ?? null),
                'No-show' => self::moneyNode($fare['NoShowCharge'] ?? null),
                'Refund charges' => self::moneyNode($fare['TotalRefundCharges'] ?? null),
                'Refund amount' => self::moneyNode($fare['TotalRefundAmount'] ?? null),
            ];
        }

        if ($rows === []) {
            return '';
        }

        return '<div class="mt-3">'.
            '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Refund quote fare breakdown</div>'.
            self::tableFromItems($rows).
            '</div>';
    }

    private static function ptrStatusReissueQuoteDetails(array $detail): string
    {
        $segmentRows = [];
        $fareRows = [];

        foreach (($detail['RequestedPreferences'] ?? []) as $preference) {
            if (! is_array($preference)) {
                continue;
            }

            $option = $preference['PreferenceOption'] ?? '-';

            foreach (($preference['QuotedSegments'] ?? []) as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $segmentRows[] = [
                    'Option' => $option,
                    'From' => $segment['DepartureAirportLocationCode'] ?? '-',
                    'To' => $segment['ArrivalAirportLocationCode'] ?? '-',
                    'Depart' => self::watDateTime($segment['DepartureDateTime'] ?? null, 'D, d M Y H:i'),
                    'Arrive' => self::watDateTime($segment['ArrivalDateTime'] ?? null, 'D, d M Y H:i'),
                    'Airline' => $segment['AirlineCode'] ?? '-',
                    'Flight' => $segment['FlightNumber'] ?? '-',
                    'Class' => $segment['BookingClass'] ?? '-',
                    'Direction' => filter_var($segment['isReturn'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'Return' : 'Outbound',
                ];
            }

            foreach (($preference['QuotedFares'] ?? []) as $fare) {
                if (! is_array($fare)) {
                    continue;
                }

                $fareRows[] = [
                    'Option' => $option,
                    'Passenger' => data_get($fare, 'PassengerTypeQuantity.Code') ?? data_get($fare, 'PassengerTypeQuantity.Quantity') ?? '-',
                    'Base diff' => self::moneyNode($fare['BaseFareDifference'] ?? null),
                    'Tax diff' => self::moneyNode($fare['TaxDifference'] ?? null),
                    'Penalty' => self::moneyNode($fare['Penalty'] ?? null),
                    'No-show' => self::moneyNode($fare['NoShowPenalty'] ?? null),
                    'Total diff' => self::moneyNode($fare['TotalFareDifference'] ?? null),
                ];
            }
        }

        $html = '';

        if ($segmentRows !== []) {
            $html .= '<div class="mt-3">'.
                '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reissue preference segments</div>'.
                self::tableFromItems($segmentRows).
                '</div>';
        }

        if ($fareRows !== []) {
            $html .= '<div class="mt-3">'.
                '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Reissue fare differences</div>'.
                self::tableFromItems($fareRows).
                '</div>';
        }

        return $html;
    }

    private static function postTicketingResponseRows(array $payload, string $operationType): array
    {
        $flat = self::flatten($payload);
        $rows = [];

        $preferredKeys = [
            'Success',
            'UniqueID',
            'ptrUniqueID',
            'PtrUniqueID',
            'Status',
            'PtrStatus',
            'RequestStatus',
            'BookingStatus',
            'TicketStatus',
            'ProcessingTime',
            'Message',
            'ErrorMessage',
            'ErrorCode',
        ];

        foreach ($preferredKeys as $preferredKey) {
            foreach ($flat as $path => $value) {
                if ((string) str($path)->afterLast('.') !== $preferredKey || blank($value)) {
                    continue;
                }

                $rows[self::responseLabel($preferredKey)] = self::providerResponseScalar($value, $preferredKey);
                break;
            }
        }

        if ($operationType === 'ptr_status') {
            foreach (['PtrType', 'Resolution'] as $ptrKey) {
                foreach ($flat as $path => $value) {
                    if ((string) str($path)->afterLast('.') !== $ptrKey || blank($value) || is_array($value)) {
                        continue;
                    }

                    $rows[self::responseLabel($ptrKey)] = self::providerResponseScalar($value, $ptrKey);
                    break;
                }
            }
        }

        foreach ($flat as $path => $value) {
            if (blank($value) || is_array($value)) {
                continue;
            }

            $last = (string) str($path)->afterLast('.');

            if (in_array($last, ['user_id', 'user_password', 'access', 'ip_address'], true)) {
                continue;
            }

            $label = self::responseLabel($last);

            if (! array_key_exists($label, $rows) && count($rows) < 18) {
                $rows[$label] = self::providerResponseScalar($value, $last);
            }
        }

        return $rows;
    }

    private static function responseLabel(string $key): string
    {
        return match ($key) {
            'UniqueID' => 'UniqueID',
            'ptrUniqueID', 'PtrUniqueID' => 'ptrUniqueID',
            'PtrStatus' => 'PTR status',
            'RequestStatus' => 'Request status',
            'BookingStatus' => 'Booking status',
            'TicketStatus' => 'Ticket status',
            'ProcessingTime' => 'Processing time',
            'ErrorMessage' => 'Error message',
            'ErrorCode' => 'Error code',
            default => str($key)->replace(['_', '-'], ' ')->headline()->toString(),
        };
    }

    private static function postTicketingResponseValue(mixed $payload, array $keys): ?string
    {
        if (! is_array($payload) || $payload === []) {
            return null;
        }

        foreach (self::flatten($payload) as $key => $value) {
            $last = (string) str($key)->afterLast('.');

            if (in_array($last, $keys, true) && filled($value)) {
                return self::scalar($value);
            }
        }

        return null;
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

    public static function ticketStatusCard(object $record): HtmlString
    {
        $ticketed = (bool) ($record->ticket_ordered ?? false)
            || in_array($record->booking_status ?? null, ['ticketed', 'confirmed'], true);
        $sent = (bool) ($record->confirmation_email_sent ?? false);
        $pendingSent = (bool) ($record->pending_email_sent ?? false);

        return new HtmlString(view('filament.booking.ticket-status', [
            'headline' => $ticketed ? 'Ticketed' : (self::label($record->booking_status ?? null) ?: '---'),
            'state' => self::stateDot($record->booking_status ?? null),
            'steps' => [
                [
                    'label' => 'Payment',
                    'value' => self::label($record->payment_status ?? null) ?: '---',
                    'done' => ($record->payment_status ?? null) === 'paid',
                ],
                [
                    'label' => 'Ticket',
                    'value' => $record->ticket_ordered_at
                        ? self::watDateTime($record->ticket_ordered_at)
                        : (self::label($record->booking_status ?? null) ?: '---'),
                    'done' => $ticketed,
                ],
                [
                    'label' => 'Email',
                    'value' => $sent ? 'Sent' : ($pendingSent ? 'Pending sent' : 'Not sent'),
                    'done' => $sent,
                ],
            ],
            'metrics' => [
                'Deadline' => $record->tkt_time_limit ? self::watDateTime($record->tkt_time_limit) : '---',
                'Ordered at' => $record->ticket_ordered_at ? self::watDateTime($record->ticket_ordered_at) : '---',
                'Confirmation' => $sent ? 'Sent' : 'Not sent',
                'Pending email' => $pendingSent ? 'Sent' : 'Not sent',
            ],
        ])->render());
    }

    /**
     * One feed for all three audit trails.
     *
     * Ticketing, payment verification and post-ticketing each rendered their
     * own slightly different list of the same shape, which is how they had
     * drifted apart. The renderer callback now returns data rather than
     * markup.
     */
    private static function historyFeed(Collection $records, string $type, callable $renderer): HtmlString
    {
        return new HtmlString(view('filament.booking.feed', [
            'items' => $records->values()->map($renderer)->all(),
            'empty' => 'No '.$type.' history yet.',
        ])->render());
    }

    /**
     * A single feed entry, as data. The badge word picks the dot tone, so a
     * failed action reads as failed in the same language the queue table uses.
     *
     * @return array{title: string, when: string, body: string|null, tone: string}
     */
    private static function historyItem(string $title, string $date, string $badge, array $details = [], ?string $body = null): array
    {
        $summary = $body !== null
            ? trim(strip_tags($body))
            : collect($details)
                ->filter(fn ($value): bool => filled($value))
                ->map(fn ($value, string $label): string => $label.': '.$value)
                ->implode(' · ');

        $word = strtolower(trim($badge));

        $tone = match (true) {
            str_contains($word, 'fail'), str_contains($word, 'error'), str_contains($word, 'cancel') => 'critical',
            str_contains($word, 'pending'), str_contains($word, 'await'), str_contains($word, 'hold') => 'warning',
            str_contains($word, 'ticketed'), str_contains($word, 'paid'), str_contains($word, 'success'), str_contains($word, 'confirmed') => 'positive',
            default => 'info',
        };

        return [
            'title' => $title,
            'when' => $date,
            'body' => $summary !== '' ? $summary : null,
            'tone' => $tone,
        ];
    }



    /**
     * One leg of the journey for the hero strip.
     *
     * Returns the airport codes rather than a joined string, because the
     * connector between them is drawn: U+2192 is in none of Inter's subsets,
     * so a typed arrow falls back to a system font mid-route.
     *
     * @return array{kind: string, legs: list<string>, meta: list<string>}|null
     */
    private static function heroLeg(array $group): ?array
    {
        $segments = collect($group['segments'] ?? [])
            ->filter(fn ($segment): bool => is_array($segment))
            ->values()
            ->all();

        if ($segments === []) {
            return null;
        }

        $codes = [];

        foreach ($segments as $index => $segment) {
            if ($index === 0) {
                $codes[] = (string) (self::value($segment, 'from', self::value($segment, 'airportOriginCode', '')) ?: '---');
            }

            $codes[] = (string) (self::value($segment, 'to', self::value($segment, 'airportDestinationCode', '')) ?: '---');
        }

        $stops = max(0, count($segments) - 1);

        $flights = collect($segments)
            ->map(fn (array $segment): string => trim((string) (self::value($segment, 'flightNo') ?: trim((string) self::value($segment, 'airlineCode').' '.(string) self::value($segment, 'flightNumber')))))
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            'kind' => (string) ($group['label'] ?? 'Leg'),
            'legs' => $codes,
            'meta' => array_values(array_filter([
                self::segmentDateTime($segments[0], 'depart') ?: null,
                $stops === 0 ? 'Non-stop' : $stops.' stop'.($stops === 1 ? '' : 's'),
                $flights ?: null,
            ])),
        ];
    }


    private static function itineraryGroups(array $flight): array
    {
        $multiLegs = collect(self::value($flight, 'multiLegs', []))
            ->filter(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== [])
            ->values();

        if ($multiLegs->isNotEmpty()) {
            return $multiLegs
                ->map(fn (array $leg, int $index): array => [
                    'label' => $leg['label'] ?? 'Leg '.($index + 1),
                    'segments' => array_values($leg['segments'] ?? []),
                ])
                ->all();
        }

        $groups = [];
        $outbound = self::value($flight, 'segments', []);
        $return = self::value($flight, 'returnSegments', []);
        $outbound = is_array($outbound) ? array_values($outbound) : [];
        $return = is_array($return) ? array_values($return) : [];

        if ($outbound !== []) {
            $groups[] = ['label' => $return !== [] ? 'Outbound' : 'Flight', 'segments' => $outbound];
        }

        if ($return !== []) {
            $groups[] = ['label' => 'Return', 'segments' => $return];
        }

        return $groups;
    }

    private static function tripLabel(array $flight, ?string $storedTripType = null): string
    {
        $groups = self::itineraryGroups($flight);
        $hasMultiLegs = collect(self::value($flight, 'multiLegs', []))
            ->contains(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== []);

        if ($hasMultiLegs) {
            return 'Multi-city';
        }

        if (count($groups) > 1) {
            return 'Round trip';
        }

        return self::label($storedTripType ?: self::value($flight, 'tripType', 'oneway'));
    }

    private static function routeFromSegments(array $segments): string
    {
        $segments = collect($segments)->filter(fn ($segment): bool => is_array($segment))->values()->all();

        if ($segments === []) {
            return '';
        }

        $first = $segments[0];
        $last = $segments[array_key_last($segments)];

        return trim((self::value($first, 'from', self::value($first, 'airportOriginCode', '')) ?: '-').' -> '.(self::value($last, 'to', self::value($last, 'airportDestinationCode', '')) ?: '-'));
    }


    private static function segmentDateTime(array $segment, string $prefix): string
    {
        $date = self::value($segment, $prefix.'Date');
        $time = self::value($segment, $prefix.'Time');
        $dateTime = self::value($segment, $prefix.'DT')
            ?: self::value($segment, $prefix === 'depart' ? 'DepartureDateTime' : 'ArrivalDateTime');

        if (filled($dateTime)) {
            return self::watDateTime($dateTime, 'D, d M Y H:i');
        }

        return trim(($date ?: '').' '.($time ?: ''));
    }

    private static function segmentFlightLabel(array $segment, array $flight = []): string
    {
        $airline = self::value($segment, 'airline', self::value($flight, 'airline', 'Flight'));
        $code = self::value($segment, 'airlineCode', self::value($flight, 'airlineCode'));
        $flightNo = self::value($segment, 'flightNo')
            ?: trim((string) $code.' '.(string) self::value($segment, 'flightNumber'));

        return trim(($airline ?: 'Flight').' '.($flightNo ?: ''));
    }

    private static function cabinText(array $primary, array $fallback = []): string
    {
        $cabin = self::value($primary, 'cabin') ?: self::value($fallback, 'cabin');
        $code = self::value($primary, 'cabinCode') ?: self::value($primary, 'CabinClassCode') ?: self::value($fallback, 'cabinCode');

        if (filled($cabin) && strlen((string) $cabin) > 1) {
            return trim((string) $cabin);
        }

        return match (strtoupper((string) ($code ?: $cabin))) {
            'F' => 'First Class',
            'C', 'J' => 'Business',
            'S', 'W' => 'Premium Economy',
            default => filled($cabin) ? (string) $cabin : 'Economy',
        };
    }

    private static function segmentDurationLabel(array $segment): string
    {
        $duration = self::value($segment, 'duration', self::value($segment, 'journeyDuration'));

        if (blank($duration)) {
            return '';
        }

        if (is_numeric($duration)) {
            $minutes = (int) $duration;

            return trim(floor($minutes / 60).'h '.($minutes % 60).'m');
        }

        return (string) $duration;
    }

    private static function groupDurationLabel(array $segments): string
    {
        $minutes = collect($segments)
            ->map(fn ($segment): int => is_array($segment) && is_numeric(self::value($segment, 'duration')) ? (int) self::value($segment, 'duration') : 0)
            ->sum();

        if ($minutes <= 0) {
            return '';
        }

        return trim(floor($minutes / 60).'h '.($minutes % 60).'m');
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
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += self::flatten($value, $path);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    private static function findNamedArrays(array $payload, string $name): array
    {
        $matches = [];

        foreach ($payload as $key => $value) {
            if ((string) $key === $name && is_array($value)) {
                $matches[] = self::isAssoc($value) ? [$value] : $value;
            }

            if (is_array($value)) {
                $matches = array_merge($matches, self::findNamedArrays($value, $name));
            }
        }

        return $matches;
    }

    private static function tree(mixed $value): string
    {
        if (! is_array($value)) {
            return '<span class="text-sm text-gray-700 dark:text-gray-200">'.e(self::scalar($value)).'</span>';
        }

        $html = '<div class="space-y-2">';

        foreach ($value as $key => $child) {
            $html .= '<div class="rounded-md border border-gray-100 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">';
            $html .= '<div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">'.e((string) $key).'</div>';
            $html .= self::tree($child);
            $html .= '</div>';
        }

        return $html.'</div>';
    }

    private static function airportBlock(string $label, array $segment, string $prefix): string
    {
        $code = self::value($segment, $prefix);
        $city = self::value($segment, $prefix.'City');
        $airport = self::value($segment, $prefix.'Airport');
        $country = self::value($segment, $prefix.'Country');

        return '<div>'.
            '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">'.e($label).'</div>'.
            '<div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">'.e($code ?: '-').'</div>'.
            '<div class="text-sm text-gray-700 dark:text-gray-200">'.e($city ?: '-').'</div>'.
            '<div class="text-xs text-gray-500 dark:text-gray-400">'.e(trim(($airport ?: '').', '.($country ?: ''), ', ')).'</div>'.
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
            $html .= '<dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">'.e((string) $label).'</dt>';
            $html .= '<dd class="mt-1 break-words text-sm text-gray-950 dark:text-white">'.e(self::scalar($value)).'</dd>';
            $html .= '</div>';
        }

        return $html.'</dl>';
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
            ->take(12)
            ->values()
            ->all();

        if ($headers === []) {
            return self::tree($items);
        }

        $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">'.e((string) $header).'</th>';
        }

        $html .= '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-white/10">';

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $html .= '<tr>';

            foreach ($headers as $header) {
                $html .= '<td class="px-3 py-2 text-gray-950 dark:text-white">'.e(self::scalar($row[$header] ?? '-')).'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function badge(string $value): string
    {
        return '<span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">'.e($value ?: '-').'</span>';
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
        return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">'.e($message).'</div>');
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

        return trim((string) ($currency ?: '').' '.number_format((float) $amount, 2));
    }

    private static function moneyNode(mixed $value): string
    {
        if (! is_array($value)) {
            return self::scalar($value);
        }

        return self::money($value['Amount'] ?? $value['amount'] ?? null, $value['CurrencyCode'] ?? $value['currency'] ?? null);
    }

    private static function watDateTime(mixed $value, string $format = 'd M Y, H:i'): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            $value = self::normalizeProviderDateTimeValue((string) $value);
            $formatted = \Carbon\Carbon::parse($value)->timezone('Africa/Lagos')->format($format);

            return $formatted;
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function providerResponseScalar(mixed $value, string $key): string
    {
        if (! is_scalar($value) || ! self::looksLikeProviderDateTime($value, $key)) {
            return self::scalar($value);
        }

        return self::watDateTime($value, 'D, d M Y H:i');
    }

    private static function looksLikeProviderDateTime(mixed $value, string $key): bool
    {
        $key = strtolower($key);

        if (in_array($key, ['processingtime', 'journeyduration', 'duration'], true)) {
            return false;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        $keySuggestsTime = str_contains($key, 'datetime')
            || str_contains($key, 'departure')
            || str_contains($key, 'arrival')
            || str_contains($key, 'depart')
            || str_contains($key, 'arrive')
            || str_contains($key, 'window')
            || str_contains($key, 'deadline')
            || str_contains($key, 'timelimit')
            || str_contains($key, 'time_limit');

        $valueHasTime = (bool) preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}/', $value);

        return $keySuggestsTime && $valueHasTime;
    }

    private static function normalizeProviderDateTimeValue(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^(\d{4}-\d{2}-\d{2}[T\s]\d{2}):(\d{2})(\d{2})$/', $value, $matches)) {
            return $matches[1].':'.$matches[2].':'.$matches[3];
        }

        return $value;
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

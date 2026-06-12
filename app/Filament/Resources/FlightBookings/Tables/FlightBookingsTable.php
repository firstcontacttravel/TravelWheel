<?php

namespace App\Filament\Resources\FlightBookings\Tables;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Mail\PaymentReceiptMail;
use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\PostTicketingRequest;
use App\Models\TicketingRecord;
use App\Services\AdminPostTicketingService;
use App\Services\AdminReplacementFlightSearchService;
use App\Services\AdminTicketingService;
use App\Services\SeerbitPaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Throwable;

class FlightBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Flight Bookings')
            ->description('Operational queue for payment verification, ticketing, and customer support.')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->striped()
            ->poll('60s')
            ->columns([
                TextColumn::make('attention')
                    ->label('Queue')
                    ->state(fn (FlightBooking $record): string => self::queueLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Awaiting transfer' => 'warning',
                        'Ready to ticket' => 'success',
                        'Ticketing failed' => 'danger',
                        'Ticketed' => 'success',
                        'Pending payment' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('booking_ref')
                    ->label('Booking')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (FlightBooking $record): string => trim(($record->unique_id ?: 'No UniqueID') . ' | ' . ($record->fare_type ?: 'No fare type'))),
                TextColumn::make('unique_id')
                    ->label('UniqueID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('route')
                    ->label('Journey')
                    ->state(fn (FlightBooking $record): HtmlString => self::journeyColumn($record))
                    ->html()
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('airline')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('fare_type')
                    ->label('Fare')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn ($record): string => trim(($record->currency ?? 'NGN') . ' ' . number_format((float) $record->total_price, 2)))
                    ->description(fn (FlightBooking $record): string => self::passengerSummary($record))
                    ->sortable(),
                TextColumn::make('booking_status')
                    ->label('Booking')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'ticketed', 'confirmed' => 'success',
                        'failed', 'cancelled', 'ticketing_failed' => 'danger',
                        'on_hold' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'awaiting_bank_transfer', 'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->toggleable(),
                TextColumn::make('payment_reference')
                    ->copyable()
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_flow')
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('ticket_ordered')
                    ->label('Ticket')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contact_email')
                    ->label('Customer')
                    ->searchable()
                    ->copyable()
                    ->description(fn (FlightBooking $record): string => $record->contact_phone ?: '-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->description(fn (FlightBooking $record): string => self::watDateTime($record->created_at))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('operations_queue')
                    ->label('Operations queue')
                    ->options([
                        'awaiting_transfer' => 'Awaiting transfer',
                        'ready_to_ticket' => 'Paid, not ticketed',
                        'ticketing_failed' => 'Ticketing failed',
                        'ticketed' => 'Ticketed',
                        'pending_payment' => 'Pending payment',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'awaiting_transfer' => $query->where('payment_status', 'awaiting_bank_transfer'),
                            'ready_to_ticket' => $query
                                ->where('payment_status', 'paid')
                                ->where('ticket_ordered', false)
                                ->where('booking_status', '!=', 'ticketed'),
                            'ticketing_failed' => $query
                                ->where('payment_status', 'paid')
                                ->whereIn('booking_status', ['failed', 'ticketing_failed']),
                            'ticketed' => $query->where('booking_status', 'ticketed'),
                            'pending_payment' => $query->where('payment_status', 'pending'),
                            default => $query,
                        };
                    }),
                Filter::make('booking_lookup')
                    ->label('Booking lookup')
                    ->form([
                        TextInput::make('booking_ref')->label('Booking ref'),
                        TextInput::make('unique_id')->label('UniqueID'),
                        TextInput::make('contact_email')->label('Contact email'),
                        TextInput::make('airline'),
                        TextInput::make('route'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['booking_ref'] ?? null, fn (Builder $query, string $value): Builder => $query->where('booking_ref', 'like', "%{$value}%"))
                            ->when($data['unique_id'] ?? null, fn (Builder $query, string $value): Builder => $query->where('unique_id', 'like', "%{$value}%"))
                            ->when($data['contact_email'] ?? null, fn (Builder $query, string $value): Builder => $query->where('contact_email', 'like', "%{$value}%"))
                            ->when($data['airline'] ?? null, fn (Builder $query, string $value): Builder => $query->where('airline', 'like', "%{$value}%"))
                            ->when($data['route'] ?? null, fn (Builder $query, string $value): Builder => $query->where('route', 'like', "%{$value}%"));
                    }),
                Filter::make('seerbit_lookup')
                    ->label('SeerBit lookup')
                    ->form([
                        TextInput::make('payment_reference')->label('Payment reference'),
                        TextInput::make('payment_flow')->label('Payment flow'),
                        DatePicker::make('verified_from'),
                        DatePicker::make('verified_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['payment_reference'] ?? null, fn (Builder $query, string $value): Builder => $query->where('payment_reference', 'like', "%{$value}%"))
                            ->when($data['payment_flow'] ?? null, fn (Builder $query, string $value): Builder => $query->where('payment_flow', 'like', "%{$value}%"))
                            ->when($data['verified_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment_verified_at', '>=', $date))
                            ->when($data['verified_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment_verified_at', '<=', $date));
                    }),
                SelectFilter::make('fare_type')
                    ->options([
                        'WebFare' => 'WebFare',
                        'Public' => 'Public',
                        'Private' => 'Private',
                    ]),
                SelectFilter::make('booking_status')
                    ->options([
                        'on_hold' => 'On hold',
                        'confirmed' => 'Confirmed',
                        'ticketed' => 'Ticketed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'awaiting_bank_transfer' => 'Awaiting bank transfer',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'gateway' => 'Gateway',
                        'bank_transfer' => 'Bank transfer',
                        'flex' => 'TravelFlex',
                    ]),
                TernaryFilter::make('ticket_ordered')
                    ->label('Ticket ordered'),
                TernaryFilter::make('ticket_deadline_expired')
                    ->label('Ticket deadline expired')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('tkt_time_limit')->where('tkt_time_limit', '<', now()),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                            $query->whereNull('tkt_time_limit')->orWhere('tkt_time_limit', '>=', now());
                        }),
                    ),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Open'),
                ActionGroup::make([
                    self::markBankTransferPaidAction(),
                    self::verifySeerbitPaymentAction(),
                    self::sendPaymentReceiptAction(),
                    self::orderTicketAction(),
                    self::fetchTripDetailsAction(),
                    self::resendETicketAction(),
                    self::sendTicketingFailureAlertAction(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray'),
            ]);
    }

    private static function queueLabel(FlightBooking $record): string
    {
        if ($record->payment_status === 'awaiting_bank_transfer') {
            return 'Awaiting transfer';
        }

        if ($record->payment_status === 'paid' && in_array($record->booking_status, ['failed', 'ticketing_failed'], true)) {
            return 'Ticketing failed';
        }

        if ($record->payment_status === 'paid' && ! $record->ticket_ordered && $record->booking_status !== 'ticketed') {
            return 'Ready to ticket';
        }

        if ($record->booking_status === 'ticketed') {
            return 'Ticketed';
        }

        if ($record->payment_status === 'pending') {
            return 'Pending payment';
        }

        return 'Review';
    }

    private static function passengerSummary(FlightBooking $record): string
    {
        return collect([
            $record->adult_count . ' adult' . ($record->adult_count === 1 ? '' : 's'),
            $record->child_count > 0 ? $record->child_count . ' child' . ($record->child_count === 1 ? '' : 'ren') : null,
            $record->infant_count > 0 ? $record->infant_count . ' infant' . ($record->infant_count === 1 ? '' : 's') : null,
        ])->filter()->implode(' | ');
    }

    private static function journeyColumn(FlightBooking $record): HtmlString
    {
        $groups = self::journeyGroups($record);

        if ($groups === []) {
            return new HtmlString('<span class="text-gray-500">' . e($record->route ?: '-') . '</span>');
        }

        $hasMultiLegs = collect($record->flight_snapshot['multiLegs'] ?? [])
            ->contains(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== []);
        $tripLabel = count($groups) > 1
            ? ($hasMultiLegs ? 'Multi-city' : 'Round trip')
            : self::label($record->trip_type ?: 'one_way');

        $html = '<div class="tw-journey-cell">';
        $html .= '<div class="tw-journey-kind">' . e($tripLabel) . '</div>';

        foreach ($groups as $group) {
            $segments = $group['segments'];
            $first = $segments[0] ?? [];
            $last = $segments[array_key_last($segments)] ?? [];
            $origin = (string) self::segmentValue($first, ['from', 'airportOriginCode'], '');
            $destination = (string) self::segmentValue($last, ['to', 'airportDestinationCode'], '');
            $date = self::journeySegmentDate($first);
            $flightLines = collect($segments)
                ->map(function (array $segment): string {
                    $airline = trim((string) self::segmentValue($segment, ['airline', 'airlineCode', 'MarketingAirlineCode'], ''));
                    $flight = trim((string) self::segmentValue($segment, ['flightNo', 'flightNumber', 'FlightNumber'], ''));
                    $cabin = trim((string) self::segmentValue($segment, ['cabin', 'cabinCode', 'CabinClassCode'], ''));
                    $cabin = strlen($cabin) === 1 ? self::cabinLabel($cabin) : $cabin;
                    $flightLabel = $flight;

                    if (filled($airline) && filled($flight) && ! str_starts_with(strtoupper($flight), strtoupper($airline))) {
                        $flightLabel = trim($airline . ' ' . $flight);
                    }

                    $time = trim(collect([
                        self::journeySegmentTime($segment, ['departTime', 'DepartureDateTime', 'departDT']),
                        self::journeySegmentTime($segment, ['arriveTime', 'ArrivalDateTime', 'arriveDT']),
                    ])->filter()->implode('-'));

                    $html = '';

                    if (filled($time)) {
                        $html .= '<span class="tw-journey-time">' . e($time) . '</span>';
                    }

                    if (filled($flightLabel)) {
                        $html .= '<span>' . e($flightLabel) . '</span>';
                    }

                    if (filled($cabin)) {
                        $html .= '<span class="tw-journey-cabin">' . e($cabin) . '</span>';
                    }

                    return $html;
                })
                ->filter()
                ->map(fn (string $line): string => '<div class="tw-journey-flight">' . $line . '</div>')
                ->implode('');

            $html .= '<div class="tw-journey-leg">';
            $html .= '<div class="tw-journey-dot"></div>';
            $html .= '<div class="tw-journey-leg-main">';
            $html .= '<div class="tw-journey-leg-top">';
            $html .= '<div class="tw-journey-route">';
            $html .= '<span>' . e($origin ?: '-') . '</span>';
            $html .= '<span class="tw-journey-arrow">-></span>';
            $html .= '<span>' . e($destination ?: '-') . '</span>';
            $html .= '</div>';
            $html .= '<span class="tw-journey-label">' . e($group['label']) . '</span>';
            $html .= '</div>';
            $html .= '<div class="tw-journey-date">' . e($date ?: '-') . '</div>';
            $html .= '<div class="tw-journey-flights">' . ($flightLines ?: '<div class="tw-journey-flight">-</div>') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    private static function journeyGroups(FlightBooking $record): array
    {
        $snapshot = $record->flight_snapshot ?? [];
        $multiLegs = collect($snapshot['multiLegs'] ?? [])
            ->filter(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== [])
            ->values();

        if ($multiLegs->isNotEmpty()) {
            return $multiLegs
                ->map(fn (array $leg, int $index): array => [
                    'label' => $leg['label'] ?? 'Leg ' . ($index + 1),
                    'segments' => array_values($leg['segments'] ?? []),
                ])
                ->all();
        }

        $groups = [];
        $outbound = is_array($snapshot['segments'] ?? null) ? array_values($snapshot['segments']) : [];
        $return = is_array($snapshot['returnSegments'] ?? null) ? array_values($snapshot['returnSegments']) : [];

        if ($outbound !== []) {
            $groups[] = ['label' => $return !== [] ? 'Outbound' : 'Flight', 'segments' => $outbound];
        }

        if ($return !== []) {
            $groups[] = ['label' => 'Return', 'segments' => $return];
        }

        return $groups;
    }

    private static function journeySegmentDate(array $segment): string
    {
        $value = self::segmentValue($segment, ['departDT', 'DepartureDateTime', 'departureDate', 'departDate'], null);

        if (blank($value)) {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)
            ? self::watDateTime($value, 'D, d M Y')
            : (string) $value;
    }

    private static function journeySegmentTime(array $segment, array $keys): string
    {
        $value = self::segmentValue($segment, $keys, null);

        if (blank($value)) {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)
            ? self::watDateTime($value, 'H:i')
            : (string) $value;
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str((string) $value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function actionContext(FlightBooking $record, string $title): HtmlString
    {
        $amount = trim(($record->currency ?? 'NGN') . ' ' . number_format((float) $record->total_price, 2));
        $customer = $record->contact_email ?: ($record->contact_phone ?: '-');

        return new HtmlString(
            '<div class="tw-action-context">' .
                '<div>' .
                    '<div class="tw-action-context-kicker">' . e($title) . '</div>' .
                    '<div class="tw-action-context-title">' . e($record->booking_ref ?: 'Booking') . '</div>' .
                    '<div class="tw-action-context-sub">' . e(trim(($record->route ?: '-') . ' | ' . ($record->airline ?: '-'))) . '</div>' .
                '</div>' .
                '<dl>' .
                    '<div><dt>Customer</dt><dd>' . e($customer) . '</dd></div>' .
                    '<div><dt>Amount</dt><dd>' . e($amount) . '</dd></div>' .
                    '<div><dt>Payment</dt><dd>' . e(self::label($record->payment_status)) . '</dd></div>' .
                    '<div><dt>Booking</dt><dd>' . e(self::label($record->booking_status)) . '</dd></div>' .
                '</dl>' .
            '</div>',
        );
    }

    public static function markBankTransferPaidAction(): Action
    {
        return Action::make('markBankTransferPaid')
            ->label('Mark paid')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (FlightBooking $record): bool => $record->payment_status === 'awaiting_bank_transfer')
            ->modalHeading(fn (FlightBooking $record): string => 'Verify bank transfer for ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Use this only after confirming that funds have reached the company bank account. This records an audit entry but does not issue a ticket.')
            ->modalIcon('heroicon-o-banknotes')
            ->modalIconColor('success')
            ->modalSubmitActionLabel('Mark payment as verified')
            ->modalWidth('lg')
            ->form(fn (FlightBooking $record): array => [
                Placeholder::make('booking_context')
                    ->hiddenLabel()
                    ->content(fn () => self::actionContext($record, 'Bank transfer verification')),
                TextInput::make('payment_reference')
                    ->label('Payment reference')
                    ->default($record->bank_transfer_reference ?: $record->payment_reference)
                    ->helperText('Use the customer transfer reference, bank narration, or internal reconciliation reference.')
                    ->maxLength(255),
                TextInput::make('amount_received')
                    ->label('Amount received')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('Confirm this against the bank credit before marking the booking paid.')
                    ->default($record->payment_amount ?: $record->total_price),
                Select::make('currency')
                    ->options([
                        'NGN' => 'NGN',
                        'USD' => 'USD',
                        'GBP' => 'GBP',
                        'EUR' => 'EUR',
                    ])
                    ->default($record->payment_currency ?: $record->currency ?: 'NGN')
                    ->required(),
                Textarea::make('verification_note')
                    ->label('Verification note')
                    ->required()
                    ->rows(4)
                    ->helperText('Add enough detail for another admin to understand how the transfer was verified.')
                    ->maxLength(2000),
                Toggle::make('send_receipt')
                    ->label('Send payment receipt now')
                    ->default(false),
            ])
            ->action(function (FlightBooking $record, array $data): void {
                $previousStatus = $record->payment_status;

                $record->update([
                    'payment_status' => 'paid',
                    'payment_method' => $record->payment_method ?: 'bank_transfer',
                    'payment_reference' => $data['payment_reference'] ?: $record->payment_reference,
                    'payment_amount' => $data['amount_received'],
                    'payment_charged_amount' => $data['amount_received'],
                    'payment_currency' => $data['currency'],
                    'payment_verified_at' => now(),
                ]);

                self::recordPaymentVerification($record->fresh(), [
                    'action' => 'bank_transfer_marked_paid',
                    'previous_payment_status' => $previousStatus,
                    'new_payment_status' => 'paid',
                    'payment_reference' => $data['payment_reference'] ?: $record->payment_reference,
                    'amount_received' => $data['amount_received'],
                    'currency' => $data['currency'],
                    'verification_note' => $data['verification_note'],
                ]);

                if ($data['send_receipt'] ?? false) {
                    self::trySendReceipt($record->fresh());
                }

                Notification::make()
                    ->title('Bank transfer marked as paid')
                    ->body('Payment status was updated. Ticketing was not triggered.')
                    ->success()
                    ->send();
            });
    }

    public static function verifySeerbitPaymentAction(): Action
    {
        return Action::make('verifySeerbitPayment')
            ->label('Verify SeerBit')
            ->icon('heroicon-o-shield-check')
            ->color('info')
            ->visible(fn (FlightBooking $record): bool => filled($record->payment_reference) && in_array($record->payment_method, ['gateway', 'flex_gateway'], true))
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => 'Verify SeerBit payment for ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription(fn (FlightBooking $record): string => 'Confirm the payment status for this booking reference before any ticketing action is taken. Payment reference: ' . ($record->payment_reference ?: '-'))
            ->modalIcon('heroicon-o-shield-check')
            ->modalIconColor('info')
            ->modalSubmitActionLabel('Verify with SeerBit')
            ->modalWidth('lg')
            ->action(function (FlightBooking $record): void {
                $previousStatus = $record->payment_status;

                try {
                    $result = app(SeerbitPaymentService::class)->verifyPayment($record->payment_reference);
                } catch (Throwable $exception) {
                    self::recordPaymentVerification($record, [
                        'action' => 'seerbit_verification_failed',
                        'previous_payment_status' => $previousStatus,
                        'new_payment_status' => $previousStatus,
                        'payment_reference' => $record->payment_reference,
                        'amount_received' => $record->payment_amount,
                        'currency' => $record->payment_currency ?: $record->currency,
                        'verification_note' => $exception->getMessage(),
                    ]);

                    Notification::make()
                        ->title('SeerBit verification failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $newStatus = $result['ok'] ? 'paid' : $previousStatus;
                $gatewayResponse = $record->payment_gateway_response ?: [];
                $gatewayResponse['seerbit_verify'] = $result['raw'] ?? [];

                $record->update([
                    'payment_status' => $newStatus,
                    'payment_amount' => $result['amount'] ?? $record->payment_amount,
                    'payment_charged_amount' => $result['amount'] ?? $record->payment_charged_amount,
                    'payment_currency' => $result['currency'] ?? $record->payment_currency,
                    'payment_verified_at' => $result['ok'] ? now() : $record->payment_verified_at,
                    'payment_gateway_response' => $gatewayResponse,
                ]);

                self::recordPaymentVerification($record->fresh(), [
                    'action' => $result['ok'] ? 'seerbit_verified_paid' : 'seerbit_verified_unpaid',
                    'previous_payment_status' => $previousStatus,
                    'new_payment_status' => $newStatus,
                    'payment_reference' => $record->payment_reference,
                    'amount_received' => $result['amount'] ?? null,
                    'currency' => $result['currency'] ?? $record->payment_currency ?: $record->currency,
                    'verification_note' => $result['message'] ?? 'SeerBit verification completed.',
                    'gateway_response' => $result['raw'] ?? [],
                ]);

                Notification::make()
                    ->title($result['ok'] ? 'SeerBit payment verified' : 'SeerBit payment not confirmed')
                    ->body($result['message'] ?? 'Verification completed. Ticketing was not triggered.')
                    ->{$result['ok'] ? 'success' : 'warning'}()
                    ->send();
            });
    }

    public static function sendPaymentReceiptAction(): Action
    {
        return Action::make('sendPaymentReceipt')
            ->label('Send receipt')
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->visible(fn (FlightBooking $record): bool => $record->payment_status === 'paid' && filled($record->contact_email) && ! $record->payment_receipt_sent)
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => 'Send payment receipt to ' . $record->contact_email)
            ->modalDescription(fn (FlightBooking $record): string => 'A receipt will be emailed for ' . trim(($record->payment_currency ?: $record->currency ?: 'NGN') . ' ' . number_format((float) ($record->payment_charged_amount ?: ($record->payment_amount ?: $record->total_price)), 2)) . '. This does not change ticketing status.')
            ->modalIcon('heroicon-o-envelope')
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Send receipt')
            ->action(function (FlightBooking $record): void {
                if (! self::trySendReceipt($record)) {
                    return;
                }

                self::recordPaymentVerification($record->fresh(), [
                    'action' => 'payment_receipt_sent',
                    'previous_payment_status' => $record->payment_status,
                    'new_payment_status' => $record->payment_status,
                    'payment_reference' => $record->payment_reference,
                    'amount_received' => $record->payment_amount,
                    'currency' => $record->payment_currency ?: $record->currency,
                    'verification_note' => 'Payment receipt sent from Filament.',
                ]);

                Notification::make()
                    ->title('Payment receipt sent')
                    ->success()
                    ->send();
            });
    }

    private static function trySendReceipt(FlightBooking $record): bool
    {
        if (blank($record->contact_email)) {
            Notification::make()
                ->title('Receipt not sent')
                ->body('Booking has no contact email.')
                ->danger()
                ->send();

            return false;
        }

        try {
            Mail::to($record->contact_email)->send(new PaymentReceiptMail($record));
            $record->update(['payment_receipt_sent' => true]);

            return true;
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Receipt not sent')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    private static function recordPaymentVerification(FlightBooking $record, array $data): void
    {
        PaymentVerificationRecord::create([
            'flight_booking_id' => $record->id,
            'verified_by' => auth()->id(),
            ...$data,
        ]);
    }

    public static function orderTicketAction(): Action
    {
        return Action::make('orderTicket')
            ->label(fn (FlightBooking $record): string => $record->booking_status === 'ticketing_failed' ? 'Retry ticket' : 'Order ticket')
            ->icon('heroicon-o-ticket')
            ->color('warning')
            ->visible(fn (FlightBooking $record): bool => self::canOrderTicket($record))
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => ($record->booking_status === 'ticketing_failed' ? 'Retry ticket order for ' : 'Order ticket for ') . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Issue the ticket for this paid booking. Run this only after payment has been confirmed and the passenger details have been reviewed.')
            ->modalIcon('heroicon-o-ticket')
            ->modalIconColor('warning')
            ->modalSubmitActionLabel(fn (FlightBooking $record): string => $record->booking_status === 'ticketing_failed' ? 'Retry ticket order' : 'Order ticket now')
            ->modalWidth('lg')
            ->action(function (FlightBooking $record): void {
                if (! self::canOrderTicket($record)) {
                    Notification::make()
                        ->title('Ticket order blocked')
                        ->body('This booking is not eligible for ticket ordering.')
                        ->danger()
                        ->send();

                    return;
                }

                $previousStatus = $record->booking_status;

                try {
                    $result = app(AdminTicketingService::class)->ticketOrder($record);
                } catch (Throwable $exception) {
                    self::recordTicketing($record, [
                        'action' => 'ticket_order_exception',
                        'previous_booking_status' => $previousStatus,
                        'new_booking_status' => $previousStatus,
                        'unique_id' => $record->unique_id,
                        'message' => $exception->getMessage(),
                    ]);

                    Notification::make()
                        ->title('Ticket order failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $newStatus = $result['ok'] ? 'ticketed' : 'ticketing_failed';

                $record->update([
                    'booking_status' => $newStatus,
                    'ticket_ordered' => (bool) $result['ok'],
                    'ticket_ordered_at' => $result['ok'] ? now() : null,
                    'ticket_api_response' => $result['response'] ?? [],
                    'unique_id' => $result['unique_id'] ?? $record->unique_id,
                ]);

                self::recordTicketing($record->fresh(), [
                    'action' => $result['ok'] ? 'ticket_order_success' : 'ticket_order_failed',
                    'previous_booking_status' => $previousStatus,
                    'new_booking_status' => $newStatus,
                    'unique_id' => $result['unique_id'] ?? $record->unique_id,
                    'message' => $result['message'] ?? null,
                    'request_payload' => $result['request'] ?? [],
                    'response_payload' => $result['response'] ?? [],
                ]);

                Notification::make()
                    ->title($result['ok'] ? 'Ticket ordered' : 'Ticket order failed')
                    ->body(($result['message'] ?? 'Ticket order completed.') . ' Ticketing action was recorded.')
                    ->{$result['ok'] ? 'success' : 'danger'}()
                    ->send();
            });
    }

    public static function fetchTripDetailsAction(): Action
    {
        return Action::make('fetchTripDetails')
            ->label('Trip details')
            ->icon('heroicon-o-identification')
            ->color('info')
            ->visible(fn (FlightBooking $record): bool => filled($record->unique_id))
            ->modalHeading(fn (FlightBooking $record): string => 'Fetch Trip Details for ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Refresh the latest ticket status, airline PNR, and itinerary details for this booking.')
            ->modalIcon('heroicon-o-identification')
            ->modalIconColor('info')
            ->modalSubmitActionLabel('Fetch Trip Details')
            ->requiresConfirmation()
            ->successRedirectUrl(fn (FlightBooking $record): string => FlightBookingResource::getUrl('view', ['record' => $record]))
            ->action(function (FlightBooking $record): void {
                try {
                    $result = app(AdminTicketingService::class)->tripDetails($record);
                } catch (Throwable $exception) {
                    self::recordTicketing($record, [
                        'action' => 'trip_details_exception',
                        'previous_booking_status' => $record->booking_status,
                        'new_booking_status' => $record->booking_status,
                        'unique_id' => $record->unique_id,
                        'message' => $exception->getMessage(),
                    ]);

                    Notification::make()
                        ->title('Trip details failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $tripDetails = self::tripDetailsWithLatestReissueTickets($record->fresh(), $result['trip_details'] ?? []);

                self::recordTicketing($record, [
                    'action' => $result['ok'] ? 'trip_details_fetched' : 'trip_details_failed',
                    'previous_booking_status' => $record->booking_status,
                    'new_booking_status' => $record->booking_status,
                    'ticket_status' => $result['ticket_status'] ?? null,
                    'airline_pnr' => $result['airline_pnr'] ?? null,
                    'unique_id' => $record->unique_id,
                    'message' => $result['message'] ?? null,
                    'request_payload' => $result['request'] ?? [],
                    'response_payload' => self::responseWithTripDetails($result['response'] ?? [], $tripDetails),
                ]);

                Notification::make()
                    ->title($result['ok'] ? 'Trip details fetched' : 'Trip details not available')
                    ->body(trim('Ticket: ' . ($result['ticket_status'] ?? '-') . ' | PNR: ' . ($result['airline_pnr'] ?? '-') . '. See Latest Trip Details below.'))
                    ->{$result['ok'] ? 'success' : 'warning'}()
                    ->send();
            });
    }

    public static function resendETicketAction(): Action
    {
        return Action::make('resendETicket')
            ->label('Resend e-ticket')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->visible(fn (FlightBooking $record): bool => $record->booking_status === 'ticketed' && filled($record->contact_email))
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => 'Resend e-ticket to ' . $record->contact_email)
            ->modalDescription('Send the customer a fresh copy of the e-ticket using the latest available ticket status and airline PNR.')
            ->modalIcon('heroicon-o-paper-airplane')
            ->modalIconColor('success')
            ->modalSubmitActionLabel('Resend e-ticket')
            ->action(function (FlightBooking $record): void {
                try {
                    $tripResult = app(AdminTicketingService::class)->tripDetails($record);
                    $tripDetails = self::tripDetailsWithLatestReissueTickets($record->fresh(), $tripResult['trip_details'] ?? []);

                    if (($tripResult['ok'] ?? false) && $tripDetails !== []) {
                        self::mergeLatestReissueTicketsIntoBooking($record->fresh());
                        app(AdminTicketingService::class)->sendETicket($record->fresh(), $tripDetails);
                    } else {
                        throw new \RuntimeException($tripResult['message'] ?? 'Trip details are required before sending e-ticket.');
                    }
                } catch (Throwable $exception) {
                    self::recordTicketing($record, [
                        'action' => 'eticket_resend_failed',
                        'previous_booking_status' => $record->booking_status,
                        'new_booking_status' => $record->booking_status,
                        'unique_id' => $record->unique_id,
                        'message' => $exception->getMessage(),
                    ]);

                    Notification::make()
                        ->title('E-ticket not sent')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                self::recordTicketing($record, [
                    'action' => 'eticket_resent',
                    'previous_booking_status' => $record->booking_status,
                    'new_booking_status' => $record->booking_status,
                    'ticket_status' => $tripResult['ticket_status'] ?? null,
                    'airline_pnr' => $tripResult['airline_pnr'] ?? null,
                    'unique_id' => $record->unique_id,
                    'message' => 'E-ticket resent to ' . $record->contact_email,
                    'request_payload' => $tripResult['request'] ?? [],
                    'response_payload' => self::responseWithTripDetails($tripResult['response'] ?? [], $tripDetails ?? []),
                ]);

                Notification::make()
                    ->title('E-ticket sent')
                    ->success()
                    ->send();
            });
    }

    public static function sendTicketingFailureAlertAction(): Action
    {
        return Action::make('sendTicketingFailureAlert')
            ->label('Alert support')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->visible(fn (FlightBooking $record): bool => $record->payment_status === 'paid' && $record->booking_status !== 'ticketed')
            ->modalHeading(fn (FlightBooking $record): string => 'Alert support about ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Use this when payment is confirmed but ticketing needs manual support intervention. The message is recorded in ticketing history.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Send support alert')
            ->modalWidth('lg')
            ->form(fn (FlightBooking $record): array => [
                Placeholder::make('booking_context')
                    ->hiddenLabel()
                    ->content(fn () => self::actionContext($record, 'Ticketing support alert')),
                Textarea::make('message')
                    ->label('Failure message')
                    ->required()
                    ->rows(4)
                    ->helperText('Be specific. Include what failed, what has been checked, and the expected next action.')
                    ->default('Payment received, but ticket issuance needs manual processing.'),
            ])
            ->action(function (FlightBooking $record, array $data): void {
                try {
                    app(AdminTicketingService::class)->sendFailureAlert($record, $data['message'], $record->ticket_api_response ?? []);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Alert not sent')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                self::recordTicketing($record, [
                    'action' => 'ticketing_failure_alert_sent',
                    'previous_booking_status' => $record->booking_status,
                    'new_booking_status' => $record->booking_status,
                    'unique_id' => $record->unique_id,
                    'message' => $data['message'],
                    'response_payload' => $record->ticket_api_response ?? [],
                ]);

                Notification::make()
                    ->title('Support alert sent')
                    ->success()
                    ->send();
            });
    }

    private static function canOrderTicket(FlightBooking $record): bool
    {
        return $record->payment_status === 'paid'
            && filled($record->unique_id)
            && ! $record->ticket_ordered
            && $record->booking_status !== 'ticketed'
            && in_array($record->booking_status, ['on_hold', 'confirmed', 'failed', 'ticketing_failed'], true);
    }

    private static function recordTicketing(FlightBooking $record, array $data): void
    {
        TicketingRecord::create([
            'flight_booking_id' => $record->id,
            'performed_by' => auth()->id(),
            ...$data,
        ]);
    }

    public static function cancelBookingAction(): Action
    {
        return Action::make('cancelBooking')
            ->label('Cancel booking')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (FlightBooking $record): bool => self::canRunCancelBooking($record))
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => 'Cancel ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Cancel this provider booking using the booking UniqueID. For already ticketed bookings, use Void or Refund instead.')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Cancel booking')
            ->modalWidth('xl')
            ->form(fn (FlightBooking $record): array => [
                Placeholder::make('booking_context')
                    ->hiddenLabel()
                    ->content(fn () => self::actionContext($record, 'Booking cancellation')),
                Placeholder::make('provider_status')
                    ->hiddenLabel()
                    ->content(fn () => self::cancelProviderStatusContext($record)),
                Textarea::make('admin_note')
                    ->label('Admin note')
                    ->helperText('Record why this booking is being cancelled. This note is stored internally and is not sent to Flightslogic.')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (FlightBooking $record, array $data): void {
                self::runPostTicketingOperation($record, 'cancel', [], $data['admin_note'] ?? null);
            });
    }

    public static function voidQuoteAction(): Action
    {
        return self::postTicketingAction('void_quote', 'Get void quote', 'heroicon-o-document-magnifying-glass', 'warning', needsSelectablePaxDetails: true);
    }

    public static function voidTicketAction(): Action
    {
        return self::postTicketingAction('void', 'Void ticket', 'heroicon-o-no-symbol', 'danger', needsSelectablePaxDetails: true, requiresQuoteType: 'void_quote');
    }

    public static function refundQuoteAction(): Action
    {
        return self::postTicketingAction('refund_quote', 'Get refund quote', 'heroicon-o-document-currency-dollar', 'warning', needsSelectablePaxDetails: true);
    }

    public static function refundTicketAction(): Action
    {
        return self::postTicketingAction('refund', 'Process refund', 'heroicon-o-receipt-refund', 'danger', needsSelectablePaxDetails: true, requiresQuoteType: 'refund_quote');
    }

    public static function reissueQuoteAction(): Action
    {
        return self::postTicketingAction('reissue_quote', 'Get reissue quote', 'heroicon-o-document-plus', 'warning', needsPaxDetails: true, needsReissueSegments: true);
    }

    public static function reissueTicketAction(): Action
    {
        return self::postTicketingAction('reissue', 'Process reissue', 'heroicon-o-arrow-path', 'danger', requiresQuoteType: 'reissue_quote', needsPreferenceOption: true);
    }

    public static function searchPtrStatusAction(): Action
    {
        return Action::make('searchPtrStatus')
            ->label('PTR status')
            ->icon('heroicon-o-magnifying-glass-circle')
            ->color('info')
            ->visible(fn (FlightBooking $record): bool => filled($record->unique_id) && $record->postTicketingRequests()->whereNotNull('ptr_unique_id')->exists())
            ->modalHeading(fn (FlightBooking $record): string => 'Check PTR status for ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription('Check the latest status of a refund, void, reissue, or cancellation request.')
            ->modalIcon('heroicon-o-magnifying-glass-circle')
            ->modalIconColor('info')
            ->modalSubmitActionLabel('Check PTR status')
            ->form(fn (FlightBooking $record): array => [
                Placeholder::make('booking_context')
                    ->hiddenLabel()
                    ->content(fn () => self::actionContext($record, 'PTR status check')),
                Select::make('ptr_unique_id')
                    ->label('PTR reference')
                    ->options(fn () => self::ptrStatusOptions($record))
                    ->required(),
                Textarea::make('admin_note')
                    ->label('Admin note')
                    ->helperText('Optional note for why this status check is being run.')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->requiresConfirmation()
            ->action(function (FlightBooking $record, array $data): void {
                self::runPostTicketingOperation($record, 'ptr_status', [
                    'ptrUniqueID' => $data['ptr_unique_id'],
                ], $data['admin_note'] ?? null);
            });
    }

    private static function postTicketingAction(
        string $operationType,
        string $label,
        string $icon,
        string $color,
        bool $needsPaxDetails = false,
        bool $needsSelectablePaxDetails = false,
        bool $needsReissueSegments = false,
        bool $needsPreferenceOption = false,
        ?string $requiresQuoteType = null,
    ): Action {
        return Action::make('postTicketing' . str($operationType)->studly())
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (FlightBooking $record): bool => self::canRunPostTicketing($record, $operationType, $requiresQuoteType))
            ->requiresConfirmation()
            ->modalHeading(fn (FlightBooking $record): string => $label . ' for ' . ($record->booking_ref ?: 'booking'))
            ->modalDescription(fn (FlightBooking $record): string => self::postTicketingDescription($operationType, $record))
            ->modalIcon($icon)
            ->modalIconColor($color)
            ->modalSubmitActionLabel($label)
            ->modalWidth('xl')
            ->form(function (FlightBooking $record) use ($operationType, $needsPaxDetails, $needsSelectablePaxDetails, $needsReissueSegments, $needsPreferenceOption, $requiresQuoteType): array {
                $schema = [
                    Placeholder::make('booking_context')
                        ->hiddenLabel()
                        ->content(fn () => self::actionContext($record, 'Post-ticketing operation')),
                ];

                if ($requiresQuoteType) {
                    $schema[] = Select::make('ptr_unique_id')
                        ->label('Quote PTR reference')
                        ->options(fn (): array => self::availablePostTicketingQuoteOptions($record, $requiresQuoteType))
                        ->live()
                        ->required();
                }

                if ($needsPreferenceOption) {
                    $schema[] = TextInput::make('preference_option')
                        ->label('Preference option')
                        ->helperText('Flightslogic docs show 1 as the option value. Change only if provider support gives a different option ID.')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required();
                }

                if ($needsPaxDetails) {
                    $schema[] = Textarea::make('pax_details_json')
                        ->label('Passenger ticket details')
                        ->helperText('Locked from the booking and latest Trip Details so the passenger identity is not changed by mistake.')
                        ->default(json_encode(self::postTicketingPaxDetails($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->rows(8);
                }

                if ($needsSelectablePaxDetails) {
                    $schema[] = CheckboxList::make('pax_selection')
                        ->label($operationType === 'refund' || $operationType === 'refund_quote' ? 'Passengers to refund' : 'Passengers to void')
                        ->helperText(($operationType === 'refund' || $operationType === 'refund_quote')
                            ? 'Select the passenger tickets to refund. E-ticket numbers are required by Flightslogic.'
                            : 'Select the passenger tickets to void. E-ticket numbers are required by Flightslogic.')
                        ->options(fn (Get $get): array => self::postTicketingPaxOptions($record, $get('ptr_unique_id')))
                        ->default(fn (): array => array_keys(self::postTicketingPaxOptions($record)))
                        ->bulkToggleable()
                        ->columns(1)
                        ->required();
                }

                if ($needsReissueSegments) {
                    $schema[] = Select::make('replacement_scope')
                        ->label('Affected flight')
                        ->helperText('Choose one journey part, or rebuild the entire itinerary when the customer wants a full flight-plan change.')
                        ->options(fn (): array => self::reissueScopeOptions($record))
                        ->default(fn (): string => self::defaultReissueScope($record))
                        ->afterStateUpdated(function (Set $set, ?string $state) use ($record): void {
                            $set('replacement_from', self::defaultReissueAirport($record, 'from', $state));
                            $set('replacement_to', self::defaultReissueAirport($record, 'to', $state));
                            $set('replacement_departure_date', self::defaultReissueDate($record, $state));
                            $set('replacement_flight_option', null);

                            foreach (self::reissueWholeItineraryScopes($record) as $scope => $label) {
                                $key = self::reissueScopeFieldKey($scope);
                                $set('replacement_entire_' . $key . '_flight_option', null);
                            }
                        })
                        ->live()
                        ->required()
                        ->columnSpanFull();

                    $schema[] = Placeholder::make('replacement_scope_context')
                        ->label('Current itinerary part')
                        ->content(fn (Get $get): HtmlString => self::reissueScopeContext($record, $get('replacement_scope')))
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->columnSpanFull();

                    $schema[] = Select::make('replacement_from')
                        ->label('Replacement from')
                        ->helperText('Search by city, airport name, country, or IATA code. Defaults to the selected itinerary part.')
                        ->default(fn () => self::defaultReissueAirport($record, 'from', self::defaultReissueScope($record)))
                        ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                        ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                        ->searchable()
                        ->preload(false)
                        ->required(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire');

                    $schema[] = Select::make('replacement_to')
                        ->label('Replacement to')
                        ->helperText('Search by city, airport name, country, or IATA code. Defaults to the selected itinerary part.')
                        ->default(fn () => self::defaultReissueAirport($record, 'to', self::defaultReissueScope($record)))
                        ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                        ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                        ->searchable()
                        ->preload(false)
                        ->required(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire');

                    $schema[] = Select::make('replacement_cabin')
                        ->label('Cabin')
                        ->options([
                            'Y' => 'Economy (Y)',
                            'S' => 'Premium Economy (S)',
                            'C' => 'Business (C)',
                            'F' => 'First (F)',
                        ])
                        ->default(fn () => self::defaultReissueCabin($record))
                        ->required(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire');

                    $schema[] = DatePicker::make('replacement_departure_date')
                        ->label('New departure date')
                        ->native(false)
                        ->default(fn () => self::defaultReissueDate($record, self::defaultReissueScope($record)))
                        ->minDate(fn (Get $get): ?string => self::reissueDateBoundary($record, (string) $get('replacement_scope'), 'min'))
                        ->maxDate(fn (Get $get): ?string => self::reissueDateBoundary($record, (string) $get('replacement_scope'), 'max'))
                        ->required(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire');

                    $schema[] = Select::make('replacement_flight_option')
                        ->label('Replacement flight')
                        ->helperText(fn (): string => filled(self::replacementFlightAirlineCode($record))
                            ? 'Options are loaded from availability and limited to the original booking airline (' . self::replacementFlightAirlineCode($record) . '). Select a flight, then review the itinerary below.'
                            : 'Options are loaded from the availability API. Select a flight, then review the full itinerary below before requesting the quote.')
                        ->options(fn (Get $get): array => app(AdminReplacementFlightSearchService::class)->options($record, [
                            'from' => $get('replacement_from'),
                            'to' => $get('replacement_to'),
                            'departure_date' => $get('replacement_departure_date'),
                            'cabin' => $get('replacement_cabin'),
                            'scope' => $get('replacement_scope'),
                            'airline_code' => self::replacementFlightAirlineCode($record),
                        ]))
                        ->allowHtml()
                        ->searchable()
                        ->preload(false)
                        ->live()
                        ->required(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire')
                        ->columnSpanFull();

                    $schema[] = Placeholder::make('replacement_flight_preview')
                        ->label('Selected flight details')
                        ->content(fn (Get $get): HtmlString => self::replacementFlightPreview($get('replacement_flight_option')))
                        ->visible(fn (Get $get): bool => $get('replacement_scope') !== 'entire' && filled($get('replacement_flight_option')))
                        ->columnSpanFull();

                    $schema[] = Placeholder::make('replacement_entire_context')
                        ->label('Current itinerary')
                        ->content(fn (): HtmlString => self::reissueEntireScopeContext($record))
                        ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                        ->columnSpanFull();

                    foreach (self::reissueWholeItineraryScopes($record) as $scope => $scopeLabel) {
                        $key = self::reissueScopeFieldKey($scope);

                        $schema[] = Placeholder::make('replacement_entire_' . $key . '_heading')
                            ->hiddenLabel()
                            ->content(fn (): HtmlString => new HtmlString('<div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">' . e($scopeLabel) . '</div>'))
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->columnSpanFull();

                        $schema[] = Select::make('replacement_entire_' . $key . '_from')
                            ->label('From')
                            ->default(fn () => self::defaultReissueAirport($record, 'from', $scope))
                            ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                            ->searchable()
                            ->preload(false)
                            ->required(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($record, $scope): void {
                                self::syncRoundTripEntireReissueRoute($record, $set, $scope, 'from', $state);
                            })
                            ->live()
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire');

                        $schema[] = Select::make('replacement_entire_' . $key . '_to')
                            ->label('To')
                            ->default(fn () => self::defaultReissueAirport($record, 'to', $scope))
                            ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                            ->searchable()
                            ->preload(false)
                            ->required(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($record, $scope): void {
                                self::syncRoundTripEntireReissueRoute($record, $set, $scope, 'to', $state);
                            })
                            ->live()
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire');

                        $schema[] = Select::make('replacement_entire_' . $key . '_cabin')
                            ->label('Cabin')
                            ->options([
                                'Y' => 'Economy (Y)',
                                'S' => 'Premium Economy (S)',
                                'C' => 'Business (C)',
                                'F' => 'First (F)',
                            ])
                            ->default(fn () => self::defaultReissueCabin($record))
                            ->required(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->live()
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire');

                        $schema[] = DatePicker::make('replacement_entire_' . $key . '_departure_date')
                            ->label('New departure date')
                            ->native(false)
                            ->default(fn () => self::defaultReissueDate($record, $scope))
                            ->minDate(fn (Get $get): ?string => self::reissueDateBoundary($record, $scope, 'min', $get))
                            ->required(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->live()
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire');

                        $schema[] = Select::make('replacement_entire_' . $key . '_flight_option')
                            ->label('Replacement flight')
                            ->helperText(fn (): string => filled(self::replacementFlightAirlineCode($record))
                                ? 'Limited to the original booking airline (' . self::replacementFlightAirlineCode($record) . '). Select the replacement for this itinerary part.'
                                : 'Select the replacement for this itinerary part.')
                            ->options(fn (Get $get): array => app(AdminReplacementFlightSearchService::class)->options($record, [
                                'from' => $get('replacement_entire_' . $key . '_from'),
                                'to' => $get('replacement_entire_' . $key . '_to'),
                                'departure_date' => $get('replacement_entire_' . $key . '_departure_date'),
                                'cabin' => $get('replacement_entire_' . $key . '_cabin'),
                                'scope' => $scope,
                                'airline_code' => self::replacementFlightAirlineCode($record),
                            ]))
                            ->allowHtml()
                            ->searchable()
                            ->preload(false)
                            ->live()
                            ->required(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire')
                            ->columnSpanFull();

                        $schema[] = Placeholder::make('replacement_entire_' . $key . '_preview')
                            ->label('Selected flight details')
                            ->content(fn (Get $get): HtmlString => self::replacementFlightPreview($get('replacement_entire_' . $key . '_flight_option')))
                            ->visible(fn (Get $get): bool => $get('replacement_scope') === 'entire' && filled($get('replacement_entire_' . $key . '_flight_option')))
                            ->columnSpanFull();
                    }
                }

                $schema[] = Textarea::make('remark')
                    ->label('Remark / reason')
                    ->helperText('Explain the customer request or operational reason for this action.')
                    ->rows(3)
                    ->maxLength(2000);

                return $schema;
            })
            ->action(function (FlightBooking $record, array $data) use ($operationType, $needsPaxDetails, $needsSelectablePaxDetails, $needsReissueSegments, $needsPreferenceOption): void {
                $extraPayload = [];

                if (isset($data['ptr_unique_id'])) {
                    if (! self::isOpenPostTicketingQuote($record, $operationType, (string) $data['ptr_unique_id'])) {
                        Notification::make()
                            ->title('Quote already used')
                            ->body('Select a new quote before processing this post-ticketing action.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (in_array($operationType, ['void', 'refund'], true)) {
                        $extraPayload['_selectedQuotePtrUniqueID'] = $data['ptr_unique_id'];
                    } else {
                        $extraPayload['ptrUniqueID'] = $data['ptr_unique_id'];
                    }
                }

                if ($needsPreferenceOption) {
                    $extraPayload['PreferenceOption'] = (string) $data['preference_option'];
                }

                if ($needsPaxDetails) {
                    $decoded = json_decode($data['pax_details_json'] ?? '[]', true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Notification::make()->title('Invalid passenger ticket details')->body('Review the formatting and try again.')->danger()->send();
                        return;
                    }
                    $extraPayload['paxDetails'] = self::normalizePostTicketingPaxDetails($decoded);

                    $passengerError = self::postTicketingPassengerValidationMessage($extraPayload['paxDetails']);

                    if ($passengerError !== null) {
                        Notification::make()->title('Invalid passenger ticket details')->body($passengerError)->danger()->send();
                        return;
                    }
                }

                if ($needsSelectablePaxDetails) {
                    $selectedPassengers = self::selectedPostTicketingPaxDetails(
                        $record,
                        $data['pax_selection'] ?? [],
                        $data['ptr_unique_id'] ?? null,
                    );

                    if ($selectedPassengers === []) {
                        Notification::make()->title('No passengers selected')->body('Select at least one passenger ticket before submitting this request.')->danger()->send();
                        return;
                    }

                    $missingTickets = collect($selectedPassengers)
                        ->filter(fn (array $passenger): bool => blank($passenger['eTicket'] ?? null))
                        ->isNotEmpty();

                    if ($missingTickets) {
                        Notification::make()->title('Missing e-ticket number')->body('This request requires an e-ticket for every selected passenger. Run Trip Details first or update the passenger ticket details.')->danger()->send();
                        return;
                    }

                    $passengerError = self::postTicketingPassengerValidationMessage($selectedPassengers);

                    if ($passengerError !== null) {
                        Notification::make()->title('Invalid passenger ticket details')->body($passengerError)->danger()->send();
                        return;
                    }

                    $extraPayload['paxDetails'] = $selectedPassengers;
                }

                if ($needsReissueSegments) {
                    $replacementScope = (string) ($data['replacement_scope'] ?? self::defaultReissueScope($record));

                    if ($replacementScope === 'entire') {
                        $replacementMap = self::decodeEntireReissueReplacementMap($record, $data);

                        if (count($replacementMap) !== count(self::reissueWholeItineraryScopes($record))) {
                            Notification::make()->title('Incomplete replacement itinerary')->body('Select a replacement flight for every outbound, return, or multi-city part before requesting the quote.')->danger()->send();
                            return;
                        }

                        $proposedItinerary = self::buildEntireReissueItinerary($record, $replacementMap);
                        $displayReplacementSegments = collect($replacementMap)->flatMap(fn (array $segments): array => $segments)->values()->all();
                    } else {
                        $replacementSegments = app(AdminReplacementFlightSearchService::class)
                            ->decodeOption($data['replacement_flight_option'] ?? null);

                        if ($replacementSegments === []) {
                            Notification::make()->title('Invalid replacement flight')->body('Search again and select a replacement flight before requesting a quote.')->danger()->send();
                            return;
                        }

                        $proposedItinerary = self::buildProposedReissueItinerary($record, $replacementScope, $replacementSegments);
                        $displayReplacementSegments = $replacementSegments;
                    }

                    if (self::flattenReissueItineraryGroups($proposedItinerary) === []) {
                        Notification::make()->title('Invalid itinerary')->body('The selected booking does not have enough itinerary details for a reissue quote.')->danger()->send();
                        return;
                    }

                    $dateError = self::reissueItineraryDateValidationMessage($proposedItinerary);

                    if ($dateError !== null) {
                        Notification::make()->title('Invalid reissue dates')->body($dateError)->danger()->send();
                        return;
                    }

                    $extraPayload['_reissueScope'] = $replacementScope;
                    $extraPayload['_reissueScopeLabel'] = self::reissueScopeLabel($record, $replacementScope);
                    $extraPayload['_reissueItineraryStructure'] = $proposedItinerary;
                    $extraPayload['_displayReplacementSegments'] = $displayReplacementSegments;
                    $extraPayload['OriginDestinationInfo'] = self::apiReissueOriginDestinationInfo(self::flattenReissueItineraryGroups($proposedItinerary));
                }

                if (filled($data['remark'] ?? null)) {
                    $extraPayload['remark'] = $data['remark'];
                }

                self::runPostTicketingOperation($record, $operationType, $extraPayload, $data['remark'] ?? null);
            });
    }

    private static function runPostTicketingOperation(FlightBooking $record, string $operationType, array $extraPayload, ?string $adminNote = null): void
    {
        if (self::hasActivePostTicketingRequest($record, $operationType)) {
            Notification::make()
                ->title('Duplicate active request blocked')
                ->body('Resolve or check the existing active PTR request before starting another one.')
                ->danger()
                ->send();
            return;
        }

        $result = app(AdminPostTicketingService::class)->call($record, $operationType, $extraPayload);

        $ptr = PostTicketingRequest::create([
            'flight_booking_id' => $record->id,
            'admin_user_id' => auth()->id(),
            'operation_type' => $operationType,
            'unique_id' => $record->unique_id,
            'ptr_unique_id' => $result['ptr_unique_id'] ?? ($extraPayload['ptrUniqueID'] ?? null),
            'status' => $result['status'] ?? (($result['ok'] ?? false) ? 'submitted' : 'failed'),
            'error_message' => ($result['ok'] ?? false) ? null : ($result['message'] ?? null),
            'admin_note' => $adminNote,
            'request_payload' => $result['request'] ?? [],
            'response_payload' => $result['response'] ?? [],
            'preflight_trip_details' => $result['preflight_trip_details'] ?? [],
        ]);

        if (($result['ok'] ?? false) && $operationType === 'cancel') {
            $previousStatus = $record->booking_status;
            $record->update(['booking_status' => 'cancelled']);

            self::recordTicketing($record->fresh(), [
                'action' => 'booking_cancelled',
                'previous_booking_status' => $previousStatus,
                'new_booking_status' => 'cancelled',
                'unique_id' => $record->unique_id,
                'message' => $result['message'] ?? 'Booking cancelled.',
                'request_payload' => $result['request'] ?? [],
                'response_payload' => $result['response'] ?? [],
            ]);
        }

        if (($result['ok'] ?? false) && $operationType === 'reissue' && self::isCompletedPostTicketingStatus($result['status'] ?? null)) {
            self::finalizeSuccessfulReissue($record->fresh(), $ptr, $result);
        }

        if (($result['ok'] ?? false) && $operationType === 'void' && self::isCompletedPostTicketingStatus($result['status'] ?? null)) {
            self::finalizeSuccessfulVoid($record->fresh(), $ptr, $result);
        }

        if (($result['ok'] ?? false) && $operationType === 'refund' && self::isCompletedPostTicketingStatus($result['status'] ?? null)) {
            self::finalizeSuccessfulRefund($record->fresh(), $ptr, $result);
        }

        if (($result['ok'] ?? false) && $operationType === 'ptr_status') {
            self::syncOriginalPtrStatus($record, $ptr, $result);
        }

        Notification::make()
            ->title(($result['ok'] ?? false) ? 'Post-ticketing request stored' : 'Post-ticketing request failed')
            ->body(($result['message'] ?? 'Request completed.') . ' PTR: ' . ($ptr->ptr_unique_id ?: '-'))
            ->{($result['ok'] ?? false) ? 'success' : 'danger'}()
            ->send();
    }

    private static function finalizeSuccessfulReissue(FlightBooking $record, PostTicketingRequest $ptr, array $result): void
    {
        $previousStatus = $record->booking_status;
        $tripResult = null;

        try {
            $tripResult = app(AdminTicketingService::class)->tripDetails($record);
        } catch (Throwable $exception) {
            self::recordTicketing($record, [
                'action' => 'reissue_trip_details_exception',
                'previous_booking_status' => $previousStatus,
                'new_booking_status' => $record->booking_status,
                'unique_id' => $record->unique_id,
                'message' => $exception->getMessage(),
                'request_payload' => $result['request'] ?? [],
                'response_payload' => $result['response'] ?? [],
            ]);

            return;
        }

        $patchedTripDetails = self::tripDetailsWithLatestReissueTickets($record->fresh(), $tripResult['trip_details'] ?? []);

        $ticketResponse = $record->ticket_api_response ?: [];
        $ticketResponse['latest_reissue'] = $result['response'] ?? [];
        $ticketResponse['latest_reissue_trip_details'] = self::responseWithTripDetails($tripResult['response'] ?? [], $patchedTripDetails);
        $updatedFlightSnapshot = self::reissuedFlightSnapshot($record, $ptr);

        $bookingUpdates = [
            'booking_status' => 'ticketed',
            'ticket_ordered' => true,
            'ticket_ordered_at' => $record->ticket_ordered_at ?: now(),
            'ticket_api_response' => $ticketResponse,
        ];

        if ($updatedFlightSnapshot !== []) {
            $bookingUpdates['flight_snapshot'] = $updatedFlightSnapshot;
            $bookingUpdates['route'] = self::routeFromSnapshot($updatedFlightSnapshot) ?: $record->route;
            $bookingUpdates['airline'] = $updatedFlightSnapshot['airline'] ?? $record->airline;
            $bookingUpdates['cabin'] = $updatedFlightSnapshot['cabin'] ?? $record->cabin;
        }

        $record->update($bookingUpdates);

        self::recordTicketing($record->fresh(), [
            'action' => ($tripResult['ok'] ?? false) ? 'reissue_completed' : 'reissue_completed_trip_details_failed',
            'previous_booking_status' => $previousStatus,
            'new_booking_status' => 'ticketed',
            'ticket_status' => $tripResult['ticket_status'] ?? null,
            'airline_pnr' => $tripResult['airline_pnr'] ?? null,
            'unique_id' => $record->unique_id,
            'message' => trim(($result['message'] ?? 'Reissue completed.') . ' PTR: ' . ($ptr->ptr_unique_id ?: '-')),
            'request_payload' => $result['request'] ?? [],
            'response_payload' => [
                'reissue' => $result['response'] ?? [],
                'trip_details' => self::responseWithTripDetails($tripResult['response'] ?? [], $patchedTripDetails),
            ],
        ]);

        self::mergeLatestReissueTicketsIntoBooking($record->fresh());

        if (! ($tripResult['ok'] ?? false) || blank($record->contact_email) || empty($patchedTripDetails)) {
            return;
        }

        try {
            app(AdminTicketingService::class)->sendETicket($record->fresh(), $patchedTripDetails);

            self::recordTicketing($record->fresh(), [
                'action' => 'reissue_eticket_sent',
                'previous_booking_status' => 'ticketed',
                'new_booking_status' => 'ticketed',
                'ticket_status' => $tripResult['ticket_status'] ?? null,
                'airline_pnr' => $tripResult['airline_pnr'] ?? null,
                'unique_id' => $record->unique_id,
                'message' => 'Updated e-ticket sent to ' . $record->contact_email,
                'request_payload' => $tripResult['request'] ?? [],
                'response_payload' => self::responseWithTripDetails($tripResult['response'] ?? [], $patchedTripDetails),
            ]);
        } catch (Throwable $exception) {
            self::recordTicketing($record->fresh(), [
                'action' => 'reissue_eticket_failed',
                'previous_booking_status' => 'ticketed',
                'new_booking_status' => 'ticketed',
                'ticket_status' => $tripResult['ticket_status'] ?? null,
                'airline_pnr' => $tripResult['airline_pnr'] ?? null,
                'unique_id' => $record->unique_id,
                'message' => $exception->getMessage(),
                'request_payload' => $tripResult['request'] ?? [],
                'response_payload' => $tripResult['response'] ?? [],
            ]);
        }
    }

    private static function finalizeSuccessfulVoid(FlightBooking $record, PostTicketingRequest $ptr, array $result): void
    {
        $previousStatus = $record->booking_status;
        $selectedPassengers = self::normalizePostTicketingPaxDetails($ptr->request_payload['paxDetails'] ?? []);
        $allPassengers = self::postTicketingPaxDetails($record);
        $isFullVoid = $selectedPassengers !== []
            && count($allPassengers) > 0
            && count($selectedPassengers) >= count($allPassengers);
        $newStatus = $isFullVoid ? 'cancelled' : $record->booking_status;

        if ($isFullVoid && $record->booking_status !== 'cancelled') {
            $record->update(['booking_status' => 'cancelled']);
        }

        self::recordTicketing($record->fresh(), [
            'action' => $isFullVoid ? 'void_completed' : 'void_partial_completed',
            'previous_booking_status' => $previousStatus,
            'new_booking_status' => $newStatus,
            'unique_id' => $record->unique_id,
            'message' => trim(($result['message'] ?? 'Void completed.') . ' PTR: ' . ($ptr->ptr_unique_id ?: '-')),
            'request_payload' => $result['request'] ?? [],
            'response_payload' => $result['response'] ?? [],
        ]);
    }

    private static function finalizeSuccessfulRefund(FlightBooking $record, PostTicketingRequest $ptr, array $result): void
    {
        $previousStatus = $record->booking_status;
        $selectedPassengers = self::normalizePostTicketingPaxDetails($ptr->request_payload['paxDetails'] ?? []);
        $allPassengers = self::postTicketingPaxDetails($record);
        $isFullRefund = $selectedPassengers !== []
            && count($allPassengers) > 0
            && count($selectedPassengers) >= count($allPassengers);
        $newStatus = $isFullRefund ? 'cancelled' : $record->booking_status;

        if ($isFullRefund && $record->booking_status !== 'cancelled') {
            $record->update(['booking_status' => 'cancelled']);
        }

        self::recordTicketing($record->fresh(), [
            'action' => $isFullRefund ? 'refund_completed' : 'refund_partial_completed',
            'previous_booking_status' => $previousStatus,
            'new_booking_status' => $newStatus,
            'unique_id' => $record->unique_id,
            'message' => trim(($result['message'] ?? 'Refund completed.') . ' PTR: ' . ($ptr->ptr_unique_id ?: '-')),
            'request_payload' => $result['request'] ?? [],
            'response_payload' => $result['response'] ?? [],
        ]);
    }

    private static function syncOriginalPtrStatus(FlightBooking $record, PostTicketingRequest $statusCheck, array $result): void
    {
        if (blank($statusCheck->ptr_unique_id)) {
            return;
        }

        $ptrDetail = self::firstPtrDetail($result['response'] ?? []);
        $operationType = self::operationTypeFromPtrType($ptrDetail['PtrType'] ?? null);
        $status = self::normalizePostTicketingStatus($ptrDetail['PtrStatus'] ?? ($result['status'] ?? null));
        $responsePayload = $result['response'] ?? [];

        $query = $record->postTicketingRequests()
            ->where('id', '!=', $statusCheck->id)
            ->where('operation_type', '!=', 'ptr_status')
            ->where('ptr_unique_id', $statusCheck->ptr_unique_id);

        if ($operationType) {
            $query->where('operation_type', $operationType);
        }

        $original = $query->latest()->first();

        if (! $original && $operationType) {
            $original = $record->postTicketingRequests()
                ->where('id', '!=', $statusCheck->id)
                ->where('operation_type', $operationType)
                ->whereNotNull('ptr_unique_id')
                ->latest()
                ->first();
        }

        if (! $original) {
            return;
        }

        $original->update([
            'status' => $status ?: ($result['status'] ?? $original->status),
            'error_message' => ($result['ok'] ?? false) ? null : ($result['message'] ?? $original->error_message),
            'response_payload' => $responsePayload ?: $original->response_payload,
        ]);

        if ($original->operation_type === 'reissue'
            && ($result['ok'] ?? false)
            && self::isCompletedPostTicketingStatus($status ?? ($result['status'] ?? null))) {
            self::mergePtrStatusPassengerTicketsIntoBooking($record->fresh(), $ptrDetail);

            if (! self::hasFinalizedReissue($record, $statusCheck->ptr_unique_id)) {
                self::finalizeSuccessfulReissue($record->fresh(), $original->fresh(), $result);
            } elseif (! self::hasSentSnapshotRefreshEticket($record, $statusCheck->ptr_unique_id)) {
                self::refreshReissueSnapshotAndResendEticket($record->fresh(), $original->fresh(), $result);
            }
        }

        if ($original->operation_type === 'void'
            && ($result['ok'] ?? false)
            && self::isCompletedPostTicketingStatus($status ?? ($result['status'] ?? null))
            && ! self::hasFinalizedVoid($record, $statusCheck->ptr_unique_id)) {
            self::finalizeSuccessfulVoid($record->fresh(), $original->fresh(), $result);
        }

        if ($original->operation_type === 'refund'
            && ($result['ok'] ?? false)
            && self::isCompletedPostTicketingStatus($status ?? ($result['status'] ?? null))
            && ! self::hasFinalizedRefund($record, $statusCheck->ptr_unique_id)) {
            self::finalizeSuccessfulRefund($record->fresh(), $original->fresh(), $result);
        }
    }

    private static function mergePtrStatusPassengerTicketsIntoBooking(FlightBooking $record, array $ptrDetail): void
    {
        $ptrPassengers = self::normalizePostTicketingPaxDetails($ptrDetail['PaxDetails'] ?? []);

        if ($ptrPassengers === []) {
            return;
        }

        $rawPassengers = $record->passengers_snapshot ?? [];

        if (! is_array($rawPassengers) || $rawPassengers === []) {
            $record->update(['passengers_snapshot' => $ptrPassengers]);
            return;
        }

        if (array_keys($rawPassengers) !== range(0, count($rawPassengers) - 1)) {
            $rawPassengers = [$rawPassengers];
        }

        $updatedPassengers = collect($rawPassengers)
            ->filter(fn ($passenger): bool => is_array($passenger))
            ->map(function (array $passenger, int $index) use ($ptrPassengers): array {
                $normalized = self::normalizePostTicketingPaxDetails([$passenger])[0] ?? [];
                $matched = collect($ptrPassengers)->first(function (array $ptrPassenger) use ($normalized): bool {
                    return strtolower((string) ($ptrPassenger['firstName'] ?? '')) === strtolower((string) ($normalized['firstName'] ?? ''))
                        && strtolower((string) ($ptrPassenger['lastName'] ?? '')) === strtolower((string) ($normalized['lastName'] ?? ''))
                        && strtolower((string) ($ptrPassenger['type'] ?? '')) === strtolower((string) ($normalized['type'] ?? ''));
                }) ?? ($ptrPassengers[$index] ?? null);

                if (is_array($matched) && filled($matched['eTicket'] ?? null)) {
                    $passenger['eTicket'] = $matched['eTicket'];
                    $passenger['eticket'] = $matched['eTicket'];
                    $passenger['eTicketNumber'] = $matched['eTicket'];
                }

                return $passenger;
            })
            ->values()
            ->all();

        $record->update(['passengers_snapshot' => $updatedPassengers]);
    }

    private static function mergeLatestReissueTicketsIntoBooking(FlightBooking $record): void
    {
        $tickets = self::latestReissuePassengerTickets($record);

        if ($tickets !== []) {
            self::mergePtrStatusPassengerTicketsIntoBooking($record, ['PaxDetails' => $tickets]);
        }
    }

    private static function tripDetailsWithLatestReissueTickets(FlightBooking $record, array $tripDetails): array
    {
        $tickets = self::latestReissuePassengerTickets($record);

        if ($tickets === [] || $tripDetails === []) {
            return $tripDetails;
        }

        $customerInfos = data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []);

        if (! is_array($customerInfos) || $customerInfos === []) {
            data_set($tripDetails, 'ItineraryInfo.CustomerInfos', collect($tickets)
                ->map(fn (array $passenger): array => ['CustomerInfo' => self::tripDetailsCustomerInfoWithTicket([], $passenger)])
                ->all());

            return $tripDetails;
        }

        $patched = collect($customerInfos)
            ->map(function (array $customer, int $index) use ($tickets): array {
                $wrapped = isset($customer['CustomerInfo']) && is_array($customer['CustomerInfo']);
                $info = $wrapped ? $customer['CustomerInfo'] : $customer;
                $matched = self::matchingPassengerTicket($info, $tickets, $index);

                if ($matched === null) {
                    return $customer;
                }

                $info = self::tripDetailsCustomerInfoWithTicket($info, $matched);

                if ($wrapped) {
                    $customer['CustomerInfo'] = $info;

                    return $customer;
                }

                return $info;
            })
            ->values()
            ->all();

        data_set($tripDetails, 'ItineraryInfo.CustomerInfos', $patched);

        return $tripDetails;
    }

    private static function responseWithTripDetails(array $response, array $tripDetails): array
    {
        if ($response !== [] && $tripDetails !== []) {
            data_set($response, 'TripDetailsResponse.TripDetailsResult.TravelItinerary', $tripDetails);
        }

        return $response;
    }

    private static function latestReissuePassengerTickets(FlightBooking $record): array
    {
        return $record->postTicketingRequests()
            ->where('operation_type', 'reissue')
            ->latest()
            ->get()
            ->map(function (PostTicketingRequest $request): array {
                $paxDetails = self::findPaxDetails($request->response_payload ?? []);

                return self::normalizePostTicketingPaxDetails(is_array($paxDetails) ? $paxDetails : []);
            })
            ->first(fn (array $passengers): bool => collect($passengers)->contains(fn (array $passenger): bool => filled($passenger['eTicket'] ?? null))) ?? [];
    }

    private static function findPaxDetails(array $payload): array
    {
        foreach ([
            'PtrResponse.PtrResult.PtrDetails.PaxDetails',
            'PtrResponse.PtrResult.PtrDetails.0.PaxDetails',
            'ReIssueResponse.ReIssueResult.PtrDetails.PaxDetails',
            'ReIssueResponse.ReIssueResult.PtrDetails.0.PaxDetails',
            'ReIssueResponse.ReIssueResult.PaxDetails',
            'PaxDetails',
            'ptr_status.PtrResponse.PtrResult.PtrDetails.PaxDetails',
            'ptr_status.PtrResponse.PtrResult.PtrDetails.0.PaxDetails',
        ] as $path) {
            $value = data_get($payload, $path);

            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        return self::findFirstArrayKey($payload, 'PaxDetails') ?? [];
    }

    private static function findFirstArrayKey(array $payload, string $key): ?array
    {
        foreach ($payload as $payloadKey => $value) {
            if ($payloadKey === $key && is_array($value)) {
                return $value;
            }

            if (is_array($value)) {
                $found = self::findFirstArrayKey($value, $key);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function matchingPassengerTicket(array $customerInfo, array $tickets, int $index): ?array
    {
        $matched = collect($tickets)->first(function (array $ticket) use ($customerInfo): bool {
            $customerFirst = strtolower((string) self::firstFilled($customerInfo, ['firstName', 'FirstName', 'PassengerFirstName', 'GivenName']));
            $customerLast = strtolower((string) self::firstFilled($customerInfo, ['lastName', 'LastName', 'PassengerLastName', 'Surname']));

            return filled($customerFirst)
                && filled($customerLast)
                && $customerFirst === strtolower((string) ($ticket['firstName'] ?? ''))
                && $customerLast === strtolower((string) ($ticket['lastName'] ?? ''));
        });

        return $matched ?: ($tickets[$index] ?? null);
    }

    private static function tripDetailsCustomerInfoWithTicket(array $customerInfo, array $ticket): array
    {
        $ticketNumber = $ticket['eTicket'] ?? null;

        if (blank($ticketNumber)) {
            return $customerInfo;
        }

        $customerInfo['eTicketNumber'] = $ticketNumber;
        $customerInfo['ETicketNumber'] = $ticketNumber;
        $customerInfo['eTicket'] = $ticketNumber;
        $customerInfo['ETicket'] = $ticketNumber;

        return $customerInfo;
    }

    private static function ptrStatusOptions(FlightBooking $record): array
    {
        return $record->postTicketingRequests()
            ->whereNotNull('ptr_unique_id')
            ->where('operation_type', '!=', 'ptr_status')
            ->latest()
            ->get()
            ->unique('ptr_unique_id')
            ->mapWithKeys(function (PostTicketingRequest $request): array {
                $label = collect([
                    $request->ptr_unique_id,
                    str((string) $request->operation_type)->replace('_', ' ')->headline()->toString(),
                    self::label($request->status),
                    self::watDateTime($request->created_at),
                ])->filter()->implode(' - ');

                return [$request->ptr_unique_id => $label];
            })
            ->all();
    }

    private static function firstPtrDetail(array $payload): array
    {
        $details = data_get($payload, 'PtrResponse.PtrResult.PtrDetails', []);

        if (is_array($details) && isset($details[0]) && is_array($details[0])) {
            return $details[0];
        }

        if (is_array($details) && self::isAssocArray($details)) {
            return $details;
        }

        return [];
    }

    private static function operationTypeFromPtrType(?string $ptrType): ?string
    {
        return match (strtolower(str_replace([' ', '-', '_'], '', (string) $ptrType))) {
            'void' => 'void',
            'refundquote' => 'refund_quote',
            'refund' => 'refund',
            'reissuequote' => 'reissue_quote',
            'reissue' => 'reissue',
            default => null,
        };
    }

    private static function normalizePostTicketingStatus(mixed $status): ?string
    {
        if (blank($status)) {
            return null;
        }

        return str((string) $status)->lower()->replace([' ', '-'], '_')->toString();
    }

    private static function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private static function isCompletedPostTicketingStatus(?string $status): bool
    {
        if (blank($status)) {
            return true;
        }

        return in_array(str((string) $status)->lower()->replace([' ', '-'], '_')->toString(), [
            'completed',
            'complete',
            'success',
            'successful',
            'ticketed',
            'done',
        ], true);
    }

    private static function hasFinalizedReissue(FlightBooking $record, string $ptrUniqueId): bool
    {
        return $record->ticketingRecords()
            ->whereIn('action', ['reissue_completed', 'reissue_completed_trip_details_failed'])
            ->where('message', 'like', '%' . $ptrUniqueId . '%')
            ->exists();
    }

    private static function hasSentSnapshotRefreshEticket(FlightBooking $record, string $ptrUniqueId): bool
    {
        return $record->ticketingRecords()
            ->where('action', 'reissue_snapshot_eticket_sent')
            ->where('message', 'like', '%' . $ptrUniqueId . '%')
            ->exists();
    }

    private static function hasFinalizedVoid(FlightBooking $record, string $ptrUniqueId): bool
    {
        return $record->ticketingRecords()
            ->whereIn('action', ['void_completed', 'void_partial_completed'])
            ->where('message', 'like', '%' . $ptrUniqueId . '%')
            ->exists();
    }

    private static function hasFinalizedRefund(FlightBooking $record, string $ptrUniqueId): bool
    {
        return $record->ticketingRecords()
            ->whereIn('action', ['refund_completed', 'refund_partial_completed'])
            ->where('message', 'like', '%' . $ptrUniqueId . '%')
            ->exists();
    }

    private static function refreshReissueSnapshotAndResendEticket(FlightBooking $record, PostTicketingRequest $ptr, array $result): void
    {
        $updatedFlightSnapshot = self::reissuedFlightSnapshot($record, $ptr);

        if ($updatedFlightSnapshot !== []) {
            $record->update([
                'flight_snapshot' => $updatedFlightSnapshot,
                'route' => self::routeFromSnapshot($updatedFlightSnapshot) ?: $record->route,
                'airline' => $updatedFlightSnapshot['airline'] ?? $record->airline,
                'cabin' => $updatedFlightSnapshot['cabin'] ?? $record->cabin,
            ]);
        }

        try {
            $tripResult = app(AdminTicketingService::class)->tripDetails($record->fresh());
            $patchedTripDetails = self::tripDetailsWithLatestReissueTickets($record->fresh(), $tripResult['trip_details'] ?? []);

            self::mergeLatestReissueTicketsIntoBooking($record->fresh());

            if (($tripResult['ok'] ?? false) && filled($record->contact_email) && ! empty($patchedTripDetails)) {
                app(AdminTicketingService::class)->sendETicket($record->fresh(), $patchedTripDetails);
            }

            self::recordTicketing($record->fresh(), [
                'action' => 'reissue_snapshot_eticket_sent',
                'previous_booking_status' => 'ticketed',
                'new_booking_status' => 'ticketed',
                'ticket_status' => $tripResult['ticket_status'] ?? null,
                'airline_pnr' => $tripResult['airline_pnr'] ?? null,
                'unique_id' => $record->unique_id,
                'message' => 'Updated reissue e-ticket refreshed and sent. PTR: ' . ($ptr->ptr_unique_id ?: '-'),
                'request_payload' => $tripResult['request'] ?? [],
                'response_payload' => [
                    'ptr_status' => $result['response'] ?? [],
                    'trip_details' => self::responseWithTripDetails($tripResult['response'] ?? [], $patchedTripDetails),
                ],
            ]);
        } catch (Throwable $exception) {
            self::recordTicketing($record->fresh(), [
                'action' => 'reissue_snapshot_eticket_failed',
                'previous_booking_status' => 'ticketed',
                'new_booking_status' => 'ticketed',
                'unique_id' => $record->unique_id,
                'message' => $exception->getMessage(),
                'response_payload' => $result['response'] ?? [],
            ]);
        }
    }

    private static function apiReissueOriginDestinationInfo(array $segments): array
    {
        return collect($segments)
            ->filter(fn ($segment): bool => is_array($segment))
            ->map(function (array $segment): array {
                $departDt = self::segmentValue($segment, ['departureDate', 'departDT', 'DepartureDateTime', 'departDate']);
                $departureDate = '';

                if (filled($departDt)) {
                    try {
                        $departureDate = \Carbon\Carbon::parse(self::normalizeProviderDateTimeValue((string) $departDt))->toDateString();
                    } catch (Throwable) {
                        $departureDate = (string) $departDt;
                    }
                }

                $flightNumber = trim((string) self::segmentValue($segment, ['flightNumber', 'FlightNumber'], ''));

                if ($flightNumber === '' && filled($segment['flightNo'] ?? null)) {
                    $flightNumber = preg_replace('/^[A-Z]{2}\s*/', '', trim((string) $segment['flightNo'])) ?: '';
                }

                return [
                    'airportOriginCode' => strtoupper(trim((string) self::segmentValue($segment, ['airportOriginCode', 'from', 'DepartureAirportLocationCode'], ''))),
                    'airportDestinationCode' => strtoupper(trim((string) self::segmentValue($segment, ['airportDestinationCode', 'to', 'ArrivalAirportLocationCode'], ''))),
                    'cabinPreference' => strtoupper(trim((string) self::segmentValue($segment, ['cabinPreference', 'cabinCode', 'CabinClassCode'], 'Y'))),
                    'departureDate' => $departureDate,
                    'airlineCode' => strtoupper(trim((string) self::segmentValue($segment, ['airlineCode', 'MarketingAirlineCode'], ''))),
                    'flightNumber' => $flightNumber,
                ];
            })
            ->filter(fn (array $segment): bool => filled($segment['airportOriginCode'])
                && filled($segment['airportDestinationCode'])
                && filled($segment['departureDate'])
                && filled($segment['airlineCode'])
                && filled($segment['flightNumber']))
            ->values()
            ->all();
    }

    private static function reissueScopeOptions(FlightBooking $record): array
    {
        $wholeScopes = self::reissueWholeItineraryScopes($record);
        $snapshot = $record->flight_snapshot ?? [];
        $multiLegs = $snapshot['multiLegs'] ?? [];

        if (is_array($multiLegs) && $multiLegs !== []) {
            $options = collect($multiLegs)
                ->filter(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== [])
                ->mapWithKeys(fn (array $leg, int $index): array => [
                    'multi:' . $index => self::reissueScopeLabel($record, 'multi:' . $index),
                ])
                ->all();

            return count($wholeScopes) > 1
                ? ['entire' => 'Entire itinerary: replace every multi-city leg'] + $options
                : $options;
        }

        $options = [];
        $segments = $snapshot['segments'] ?? [];
        $returnSegments = $snapshot['returnSegments'] ?? [];

        if (is_array($segments) && $segments !== []) {
            $options['outbound'] = self::reissueScopeLabel($record, 'outbound');
        }

        if (is_array($returnSegments) && $returnSegments !== []) {
            $options['return'] = self::reissueScopeLabel($record, 'return');
        }

        if (count($wholeScopes) > 1) {
            $options = ['entire' => 'Entire itinerary: replace outbound and return'] + $options;
        }

        return $options !== [] ? $options : ['outbound' => 'Outbound flight'];
    }

    private static function defaultReissueScope(FlightBooking $record): string
    {
        $options = self::reissueScopeOptions($record);
        unset($options['entire']);

        return array_key_first($options) ?: 'outbound';
    }

    private static function reissueScopeLabel(FlightBooking $record, ?string $scope): string
    {
        if ($scope === 'entire') {
            return 'Entire itinerary';
        }

        $segments = self::reissueScopeSegments($record, $scope);
        $first = $segments[0] ?? [];
        $last = $segments === [] ? [] : $segments[array_key_last($segments)];
        $route = trim((string) self::segmentValue($first, ['from', 'airportOriginCode'], '') . ' -> ' . (string) self::segmentValue($last, ['to', 'airportDestinationCode'], ''));
        $date = self::dateFromSegment($first);

        $prefix = match (true) {
            str_starts_with((string) $scope, 'multi:') => 'Multi-city leg ' . (((int) substr((string) $scope, 6)) + 1),
            $scope === 'return' => 'Return flight',
            default => 'Outbound flight',
        };

        return trim($prefix . (filled($route) && $route !== '->' ? ': ' . $route : '') . (filled($date) ? ' - ' . $date : ''));
    }

    private static function reissueWholeItineraryScopes(FlightBooking $record): array
    {
        $snapshot = $record->flight_snapshot ?? [];
        $multiLegs = $snapshot['multiLegs'] ?? [];

        if (is_array($multiLegs) && $multiLegs !== []) {
            return collect($multiLegs)
                ->filter(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== [])
                ->mapWithKeys(fn (array $leg, int $index): array => [
                    'multi:' . $index => self::reissueScopeLabel($record, 'multi:' . $index),
                ])
                ->all();
        }

        $scopes = [];

        if (is_array($snapshot['segments'] ?? null) && ($snapshot['segments'] ?? []) !== []) {
            $scopes['outbound'] = self::reissueScopeLabel($record, 'outbound');
        }

        if (is_array($snapshot['returnSegments'] ?? null) && ($snapshot['returnSegments'] ?? []) !== []) {
            $scopes['return'] = self::reissueScopeLabel($record, 'return');
        }

        return $scopes !== [] ? $scopes : ['outbound' => 'Outbound flight'];
    }

    private static function reissueScopeFieldKey(string $scope): string
    {
        return str($scope)->replace([':', '-'], '_')->snake()->toString();
    }

    private static function syncRoundTripEntireReissueRoute(FlightBooking $record, Set $set, string $scope, string $direction, ?string $value): void
    {
        $scopes = array_keys(self::reissueWholeItineraryScopes($record));

        if ($scopes !== ['outbound', 'return'] || ! in_array($scope, ['outbound', 'return'], true) || ! in_array($direction, ['from', 'to'], true)) {
            return;
        }

        $targetScope = $scope === 'outbound' ? 'return' : 'outbound';
        $targetDirection = $direction === 'from' ? 'to' : 'from';
        $sourceKey = self::reissueScopeFieldKey($scope);
        $targetKey = self::reissueScopeFieldKey($targetScope);

        $set('replacement_entire_' . $sourceKey . '_flight_option', null);
        $set('replacement_entire_' . $targetKey . '_' . $targetDirection, $value);
        $set('replacement_entire_' . $targetKey . '_flight_option', null);
    }

    private static function reissueScopeContext(FlightBooking $record, ?string $scope): HtmlString
    {
        $segments = self::reissueScopeSegments($record, $scope);

        if ($segments === []) {
            return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">No itinerary segments were found for this selection.</div>');
        }

        $html = '<div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">';
        $html .= '<div class="mb-2 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e(self::reissueScopeLabel($record, $scope)) . '</div>';
        $html .= '<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="text-xs uppercase text-gray-500 dark:text-gray-400">';

        foreach (['From', 'To', 'Depart', 'Airline', 'Flight', 'Cabin'] as $heading) {
            $html .= '<th class="border-b border-gray-100 px-3 py-2 font-medium dark:border-white/10">' . e($heading) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($segments as $segment) {
            $html .= '<tr class="text-gray-950 dark:text-white">';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e((string) self::segmentValue($segment, ['from', 'airportOriginCode'], '-')) . '</td>';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e((string) self::segmentValue($segment, ['to', 'airportDestinationCode'], '-')) . '</td>';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e(self::watDateTime(self::segmentValue($segment, ['departDT', 'departureDate', 'departDate'], null), 'D, d M Y H:i')) . '</td>';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e((string) self::segmentValue($segment, ['airline', 'airlineCode'], '-')) . '</td>';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e((string) self::segmentValue($segment, ['flightNo', 'flightNumber'], '-')) . '</td>';
            $html .= '<td class="border-b border-gray-100 px-3 py-2 dark:border-white/10">' . e((string) self::segmentValue($segment, ['cabin', 'cabinCode'], '-')) . '</td>';
            $html .= '</tr>';
        }

        return new HtmlString($html . '</tbody></table></div></div>');
    }

    private static function reissueEntireScopeContext(FlightBooking $record): HtmlString
    {
        $html = '<div class="grid gap-3">';

        foreach (self::reissueWholeItineraryScopes($record) as $scope => $label) {
            $segments = self::reissueScopeSegments($record, $scope);

            if ($segments === []) {
                continue;
            }

            $first = $segments[0];
            $last = $segments[array_key_last($segments)];
            $flights = collect($segments)
                ->map(fn (array $segment): string => trim((string) self::segmentValue($segment, ['flightNo', 'flightNumber'], '')))
                ->filter()
                ->implode(' / ');

            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="flex flex-wrap items-start justify-between gap-3">';
            $html .= '<div>';
            $html .= '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e($label) . '</div>';
            $html .= '<div class="mt-1 text-base font-semibold text-gray-950 dark:text-white">' . e((string) self::segmentValue($first, ['from', 'airportOriginCode'], '-') . ' -> ' . (string) self::segmentValue($last, ['to', 'airportDestinationCode'], '-')) . '</div>';
            $html .= '</div>';
            $html .= '<div class="text-right text-sm text-gray-600 dark:text-gray-300">' . e(self::watDateTime(self::segmentValue($first, ['departDT', 'departureDate', 'departDate'], null), 'D, d M Y H:i')) . '<br>' . e($flights ?: '-') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
    }

    private static function reissueScopeSegments(FlightBooking $record, ?string $scope): array
    {
        $snapshot = $record->flight_snapshot ?? [];

        if (str_starts_with((string) $scope, 'multi:')) {
            $index = (int) substr((string) $scope, 6);
            $segments = $snapshot['multiLegs'][$index]['segments'] ?? [];

            return is_array($segments) ? array_values($segments) : [];
        }

        $segments = $scope === 'return'
            ? ($snapshot['returnSegments'] ?? [])
            : ($snapshot['segments'] ?? []);

        return is_array($segments) ? array_values($segments) : [];
    }

    private static function defaultReissueDate(FlightBooking $record, ?string $scope): ?string
    {
        $segment = self::reissueScopeSegments($record, $scope)[0] ?? [];

        return self::dateFromSegment(is_array($segment) ? $segment : []);
    }

    private static function dateFromSegment(array $segment): ?string
    {
        $value = self::segmentValue($segment, ['departureDate', 'departDT', 'departDate', 'DepartureDateTime'], null);

        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse(self::normalizeProviderDateTimeValue((string) $value))->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function reissueDateBoundary(FlightBooking $record, string $scope, string $boundary, ?Get $get = null): ?string
    {
        if ($scope === 'entire') {
            return null;
        }

        $scopes = array_keys(self::reissueWholeItineraryScopes($record));
        $index = array_search($scope, $scopes, true);

        if ($index === false) {
            return $boundary === 'min' ? now('Africa/Lagos')->toDateString() : null;
        }

        $targetScope = $boundary === 'min'
            ? ($scopes[$index - 1] ?? null)
            : ($scopes[$index + 1] ?? null);

        $date = filled($targetScope)
            ? self::reissueDateForBoundaryScope($record, (string) $targetScope, $get)
            : null;

        if ($boundary === 'min') {
            return self::laterDate(now('Africa/Lagos')->toDateString(), $date);
        }

        return $date;
    }

    private static function reissueDateForBoundaryScope(FlightBooking $record, string $scope, ?Get $get = null): ?string
    {
        if ($get !== null) {
            $key = self::reissueScopeFieldKey($scope);
            $value = $get('replacement_entire_' . $key . '_departure_date');

            if (filled($value)) {
                return self::parseDateString($value);
            }
        }

        return self::defaultReissueDate($record, $scope);
    }

    private static function laterDate(?string $first, ?string $second): ?string
    {
        $firstDate = self::parseDateString($first);
        $secondDate = self::parseDateString($second);

        if ($firstDate === null) {
            return $secondDate;
        }

        if ($secondDate === null) {
            return $firstDate;
        }

        try {
            return \Carbon\Carbon::parse($firstDate)->greaterThan(\Carbon\Carbon::parse($secondDate)) ? $firstDate : $secondDate;
        } catch (Throwable) {
            return $firstDate;
        }
    }

    private static function parseDateString(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse(self::normalizeProviderDateTimeValue((string) $value))->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function reissueItineraryDateValidationMessage(array $structure): ?string
    {
        $previousGroup = null;
        $previousDeparture = null;

        foreach (self::orderedReissueItineraryGroups($structure) as $group) {
            $label = $group['label'];
            $segments = $group['segments'];
            $groupDeparture = self::segmentDepartureCarbon($segments[0] ?? []);

            if ($groupDeparture === null) {
                return $label . ' is missing a valid departure date.';
            }

            if ($previousDeparture !== null && $groupDeparture->lt($previousDeparture)) {
                return $label . ' cannot depart before ' . $previousGroup . '.';
            }

            $previousSegmentDeparture = null;

            foreach ($segments as $index => $segment) {
                $segmentDeparture = self::segmentDepartureCarbon($segment);

                if ($segmentDeparture === null) {
                    return $label . ' segment ' . ($index + 1) . ' is missing a valid departure date.';
                }

                if ($previousSegmentDeparture !== null && $segmentDeparture->lt($previousSegmentDeparture)) {
                    return $label . ' has flight segments out of order.';
                }

                $previousSegmentDeparture = $segmentDeparture;
            }

            $previousGroup = $label;
            $previousDeparture = $groupDeparture;
        }

        return null;
    }

    private static function orderedReissueItineraryGroups(array $structure): array
    {
        $multiLegs = $structure['multiLegs'] ?? [];

        if (is_array($multiLegs) && $multiLegs !== []) {
            return collect($multiLegs)
                ->filter(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== [])
                ->map(fn (array $leg, int $index): array => [
                    'label' => $leg['label'] ?? 'Leg ' . ($index + 1),
                    'segments' => array_values($leg['segments']),
                ])
                ->values()
                ->all();
        }

        $groups = [];

        if (is_array($structure['segments'] ?? null) && ($structure['segments'] ?? []) !== []) {
            $groups[] = ['label' => 'Outbound flight', 'segments' => array_values($structure['segments'])];
        }

        if (is_array($structure['returnSegments'] ?? null) && ($structure['returnSegments'] ?? []) !== []) {
            $groups[] = ['label' => 'Return flight', 'segments' => array_values($structure['returnSegments'])];
        }

        return $groups;
    }

    private static function segmentDepartureCarbon(array $segment): ?\Carbon\Carbon
    {
        $value = self::segmentValue($segment, ['departDT', 'DepartureDateTime', 'departureDate', 'departDate'], null);

        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse(self::normalizeProviderDateTimeValue((string) $value));
        } catch (Throwable) {
            return null;
        }
    }

    private static function buildProposedReissueItinerary(FlightBooking $record, string $scope, array $replacementSegments): array
    {
        $snapshot = $record->flight_snapshot ?? [];
        $multiLegs = is_array($snapshot['multiLegs'] ?? null) ? array_values($snapshot['multiLegs']) : [];
        $returnSegments = is_array($snapshot['returnSegments'] ?? null) ? array_values($snapshot['returnSegments']) : [];
        $segments = is_array($snapshot['segments'] ?? null) ? array_values($snapshot['segments']) : [];
        $tripType = self::snapshotTripType($snapshot);

        if (str_starts_with($scope, 'multi:')) {
            $index = (int) substr($scope, 6);
            $multiLegs[$index] = array_merge(is_array($multiLegs[$index] ?? null) ? $multiLegs[$index] : [], [
                'label' => 'Leg ' . ($index + 1),
                'segments' => array_values($replacementSegments),
            ]);
            $tripType = 'multicity';
        } elseif ($scope === 'return') {
            $returnSegments = array_values($replacementSegments);
            $tripType = 'return';
        } else {
            $segments = array_values($replacementSegments);
        }

        return [
            'tripType' => $tripType,
            'directionInd' => $tripType === 'multicity' ? 'Circle' : ($tripType === 'return' ? 'Return' : 'OneWay'),
            'segments' => $segments,
            'returnSegments' => $returnSegments,
            'multiLegs' => $multiLegs,
        ];
    }

    private static function decodeEntireReissueReplacementMap(FlightBooking $record, array $data): array
    {
        $search = app(AdminReplacementFlightSearchService::class);
        $map = [];

        foreach (self::reissueWholeItineraryScopes($record) as $scope => $label) {
            $key = self::reissueScopeFieldKey($scope);
            $segments = $search->decodeOption($data['replacement_entire_' . $key . '_flight_option'] ?? null);

            if ($segments === []) {
                continue;
            }

            $map[$scope] = $segments;
        }

        return $map;
    }

    private static function buildEntireReissueItinerary(FlightBooking $record, array $replacementMap): array
    {
        $snapshot = $record->flight_snapshot ?? [];
        $multiLegs = is_array($snapshot['multiLegs'] ?? null) ? array_values($snapshot['multiLegs']) : [];

        if ($multiLegs !== []) {
            foreach ($multiLegs as $index => $leg) {
                $scope = 'multi:' . $index;
                $multiLegs[$index] = array_merge(is_array($leg) ? $leg : [], [
                    'label' => 'Leg ' . ($index + 1),
                    'segments' => array_values($replacementMap[$scope] ?? []),
                ]);
            }

            return [
                'tripType' => 'multicity',
                'directionInd' => 'Circle',
                'segments' => [],
                'returnSegments' => [],
                'multiLegs' => $multiLegs,
            ];
        }

        $returnSegments = is_array($snapshot['returnSegments'] ?? null) ? array_values($snapshot['returnSegments']) : [];
        $hasReturn = $returnSegments !== [] || isset($replacementMap['return']);

        return [
            'tripType' => $hasReturn ? 'return' : 'oneway',
            'directionInd' => $hasReturn ? 'Return' : 'OneWay',
            'segments' => array_values($replacementMap['outbound'] ?? []),
            'returnSegments' => array_values($replacementMap['return'] ?? []),
            'multiLegs' => [],
        ];
    }

    private static function snapshotTripType(array $snapshot): string
    {
        if (is_array($snapshot['multiLegs'] ?? null) && ($snapshot['multiLegs'] ?? []) !== []) {
            return 'multicity';
        }

        if (is_array($snapshot['returnSegments'] ?? null) && ($snapshot['returnSegments'] ?? []) !== []) {
            return 'return';
        }

        $tripType = strtolower((string) ($snapshot['tripType'] ?? $snapshot['directionInd'] ?? 'oneway'));

        return str_contains($tripType, 'circle') || str_contains($tripType, 'multi')
            ? 'multicity'
            : (str_contains($tripType, 'return') ? 'return' : 'oneway');
    }

    private static function flattenReissueItineraryGroups(array $structure): array
    {
        $multiLegs = $structure['multiLegs'] ?? [];

        if (is_array($multiLegs) && $multiLegs !== []) {
            return collect($multiLegs)
                ->filter(fn ($leg): bool => is_array($leg))
                ->flatMap(fn (array $leg): array => is_array($leg['segments'] ?? null) ? $leg['segments'] : [])
                ->filter(fn ($segment): bool => is_array($segment))
                ->values()
                ->all();
        }

        return collect(array_merge(
            is_array($structure['segments'] ?? null) ? $structure['segments'] : [],
            is_array($structure['returnSegments'] ?? null) ? $structure['returnSegments'] : [],
        ))
            ->filter(fn ($segment): bool => is_array($segment))
            ->values()
            ->all();
    }

    private static function segmentValue(array $segment, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (filled($segment[$key] ?? null)) {
                return $segment[$key];
            }
        }

        return $default;
    }

    private static function reissuedFlightSnapshot(FlightBooking $record, PostTicketingRequest $ptr): array
    {
        $quoteRequest = self::matchingReissueQuoteRequest($record, $ptr);
        $structure = $quoteRequest?->request_payload['_reissueItineraryStructure'] ?? null;

        if (! is_array($structure) || self::flattenReissueItineraryGroups($structure) === []) {
            $segments = $quoteRequest?->request_payload['_displayReplacementSegments']
                ?? $quoteRequest?->request_payload['OriginDestinationInfo']
                ?? [];

            if (! is_array($segments) || $segments === []) {
                return [];
            }

            $structure = [
                'tripType' => 'oneway',
                'directionInd' => 'OneWay',
                'segments' => $segments,
                'returnSegments' => [],
                'multiLegs' => [],
            ];
        }

        $mappedOutbound = self::mapReissueSegmentsForSnapshot($structure['segments'] ?? []);
        $mappedReturn = self::mapReissueSegmentsForSnapshot($structure['returnSegments'] ?? []);
        $mappedMultiLegs = collect($structure['multiLegs'] ?? [])
            ->filter(fn ($leg): bool => is_array($leg))
            ->map(function (array $leg, int $index): array {
                $segments = self::mapReissueSegmentsForSnapshot($leg['segments'] ?? []);
                $first = $segments[0] ?? null;
                $last = $segments === [] ? null : $segments[array_key_last($segments)];

                return array_merge($leg, [
                    'label' => $leg['label'] ?? 'Leg ' . ($index + 1),
                    'from' => $first['from'] ?? ($leg['from'] ?? null),
                    'to' => $last['to'] ?? ($leg['to'] ?? null),
                    'departureDate' => $first['departDT'] ?? ($leg['departureDate'] ?? null),
                    'segments' => $segments,
                ]);
            })
            ->filter(fn (array $leg): bool => ($leg['segments'] ?? []) !== [])
            ->values()
            ->all();

        if ($mappedOutbound === [] && $mappedReturn === [] && $mappedMultiLegs === []) {
            return [];
        }

        $displaySegments = $mappedMultiLegs !== []
            ? ($mappedMultiLegs[0]['segments'] ?? [])
            : $mappedOutbound;
        $allSegments = collect($mappedMultiLegs !== []
            ? collect($mappedMultiLegs)->flatMap(fn (array $leg): array => $leg['segments'] ?? [])->all()
            : array_merge($mappedOutbound, $mappedReturn))->values()->all();
        $first = $allSegments[0] ?? [];
        $last = $allSegments === [] ? [] : $allSegments[array_key_last($allSegments)];
        $existing = $record->flight_snapshot ?? [];
        $tripType = (string) ($structure['tripType'] ?? ($mappedMultiLegs !== [] ? 'multicity' : ($mappedReturn !== [] ? 'return' : 'oneway')));
        $directionInd = (string) ($structure['directionInd'] ?? ($tripType === 'multicity' ? 'Circle' : ($tripType === 'return' ? 'Return' : 'OneWay')));

        return array_merge($existing, [
            'airline' => $first['airline'] ?? ($existing['airline'] ?? ''),
            'airlineCode' => $first['airlineCode'] ?? ($existing['airlineCode'] ?? ''),
            'cabin' => $first['cabin'] ?? ($existing['cabin'] ?? ''),
            'cabinCode' => $first['cabinCode'] ?? ($existing['cabinCode'] ?? 'Y'),
            'segments' => $displaySegments,
            'returnSegments' => $mappedReturn,
            'multiLegs' => $mappedMultiLegs,
            'tripType' => $tripType,
            'directionInd' => $directionInd,
            'departDT' => $first['departDT'] ?? null,
            'arriveDT' => $last['arriveDT'] ?? null,
            'departTime' => $first['departTime'] ?? '',
            'arriveTime' => $last['arriveTime'] ?? '',
            'departDateLabel' => filled($first['departDT'] ?? null) ? self::watDateTime($first['departDT'], 'D, d M') : '',
            'stops' => max(0, count($displaySegments) - 1),
        ]);
    }

    private static function mapReissueSegmentsForSnapshot(array $segments): array
    {
        return collect($segments)
            ->filter(fn ($segment): bool => is_array($segment))
            ->map(function (array $segment): array {
                $departureDate = (string) self::segmentValue($segment, ['departureDate', 'departDT', 'DepartureDateTime', 'departDate'], '');
                $departDt = (string) self::segmentValue($segment, ['departDT', 'DepartureDateTime', 'departureDate', 'departDate'], $departureDate);
                $arriveDt = self::segmentValue($segment, ['arriveDT', 'ArrivalDateTime'], null);
                $airlineCode = strtoupper((string) self::segmentValue($segment, ['airlineCode', 'MarketingAirlineCode'], ''));
                $flightNumber = (string) self::segmentValue($segment, ['flightNumber', 'FlightNumber'], '');

                if ($flightNumber === '' && filled($segment['flightNo'] ?? null)) {
                    $flightNumber = preg_replace('/^[A-Z]{2}\s*/', '', trim((string) $segment['flightNo'])) ?: '';
                }

                $cabinCode = strtoupper((string) self::segmentValue($segment, ['cabinPreference', 'cabinCode', 'CabinClassCode'], 'Y'));

                return [
                    'from' => strtoupper((string) self::segmentValue($segment, ['airportOriginCode', 'from', 'DepartureAirportLocationCode'], '')),
                    'to' => strtoupper((string) self::segmentValue($segment, ['airportDestinationCode', 'to', 'ArrivalAirportLocationCode'], '')),
                    'departTime' => $segment['departTime'] ?? (filled($departDt) && strlen($departDt) > 10 ? self::watDateTime($departDt, 'H:i') : ''),
                    'arriveTime' => $segment['arriveTime'] ?? (filled($arriveDt) ? self::watDateTime($arriveDt, 'H:i') : ''),
                    'departDate' => filled($departDt) ? self::watDateTime($departDt, 'D, d M Y') : $departureDate,
                    'arriveDate' => filled($arriveDt) ? self::watDateTime($arriveDt, 'D, d M Y') : '',
                    'departDT' => $departDt,
                    'arriveDT' => $arriveDt,
                    'duration' => (int) ($segment['duration'] ?? 0),
                    'flightNo' => trim($airlineCode . $flightNumber),
                    'flightNumber' => $flightNumber,
                    'airline' => $segment['airline'] ?? $airlineCode,
                    'airlineCode' => $airlineCode,
                    'cabin' => $segment['cabin'] ?? self::cabinLabel($cabinCode),
                    'cabinCode' => $cabinCode,
                    'equipment' => $segment['equipment'] ?? '',
                    'eticket' => true,
                ];
            })
            ->filter(fn (array $segment): bool => filled($segment['from'] ?? null) && filled($segment['to'] ?? null))
            ->values()
            ->all();
    }

    private static function matchingReissueQuoteRequest(FlightBooking $record, PostTicketingRequest $ptr): ?PostTicketingRequest
    {
        $quotePtr = $ptr->request_payload['ptrUniqueID']
            ?? $ptr->ptr_unique_id
            ?? null;

        $query = $record->postTicketingRequests()
            ->where('operation_type', 'reissue_quote');

        if (filled($quotePtr)) {
            $match = (clone $query)->where('ptr_unique_id', $quotePtr)->latest()->first();

            if ($match) {
                return $match;
            }
        }

        return $query->latest()->first();
    }

    private static function routeFromSnapshot(array $snapshot): ?string
    {
        $multiLegs = $snapshot['multiLegs'] ?? [];

        if (is_array($multiLegs) && $multiLegs !== []) {
            $firstLeg = collect($multiLegs)->first(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== []);
            $lastLeg = collect($multiLegs)->reverse()->first(fn ($leg): bool => is_array($leg) && is_array($leg['segments'] ?? null) && ($leg['segments'] ?? []) !== []);
            $firstSegments = is_array($firstLeg) ? ($firstLeg['segments'] ?? []) : [];
            $lastSegments = is_array($lastLeg) ? ($lastLeg['segments'] ?? []) : [];

            if ($firstSegments !== [] && $lastSegments !== []) {
                $first = $firstSegments[0];
                $last = $lastSegments[array_key_last($lastSegments)];

                return trim(($first['from'] ?? '') . ' -> ' . ($last['to'] ?? ''));
            }
        }

        $segments = $snapshot['segments'] ?? [];

        if (! is_array($segments) || $segments === []) {
            return null;
        }

        $first = $segments[0];
        $last = $segments[array_key_last($segments)];

        return trim(($first['from'] ?? '') . ' -> ' . ($last['to'] ?? ''));
    }

    private static function cabinLabel(string $code): string
    {
        return match (strtoupper($code)) {
            'F' => 'First',
            'C' => 'Business',
            'S' => 'Premium Economy',
            default => 'Economy',
        };
    }

    private static function postTicketingPaxDetails(FlightBooking $record): array
    {
        $passengers = self::normalizePostTicketingPaxDetails($record->passengers_snapshot ?? []);

        try {
            $tripResult = app(AdminTicketingService::class)->tripDetails($record);
        } catch (Throwable) {
            return $passengers;
        }

        if (! ($tripResult['ok'] ?? false)) {
            return $passengers;
        }

        if ($passengers === []) {
            $passengers = self::normalizePostTicketingPaxDetails(self::tripDetailsCustomerInfos($tripResult['trip_details'] ?? [])->all());
        }

        return self::mergePostTicketingTickets($passengers, $tripResult['trip_details'] ?? []);
    }

    private static function normalizePostTicketingPaxDetails(array $passengers): array
    {
        if ($passengers === []) {
            return [];
        }

        if (array_keys($passengers) !== range(0, count($passengers) - 1)) {
            $passengers = [$passengers];
        }

        return collect($passengers)
            ->filter(fn ($passenger): bool => is_array($passenger))
            ->map(fn (array $passenger): array => [
                'type' => self::firstFilled($passenger, ['type', 'PassengerType', 'passengerType'], 'ADT'),
                'title' => self::firstFilled($passenger, ['title', 'Title', 'PassengerTitle']),
                'firstName' => self::firstFilled($passenger, ['firstName', 'first_name', 'FirstName', 'PassengerFirstName']),
                'lastName' => self::firstFilled($passenger, ['lastName', 'last_name', 'LastName', 'PassengerLastName']),
                'eTicket' => self::firstFilled($passenger, ['eTicket', 'eticket', 'eTicketNumber', 'ETicket', 'TicketNumber']),
            ])
            ->values()
            ->all();
    }

    private static function postTicketingPassengerValidationMessage(array $passengers): ?string
    {
        if ($passengers === []) {
            return 'At least one passenger with a valid e-ticket is required.';
        }

        foreach ($passengers as $index => $passenger) {
            $label = 'Passenger ' . ($index + 1);
            $missing = collect([
                'type' => $passenger['type'] ?? null,
                'title' => $passenger['title'] ?? null,
                'first name' => $passenger['firstName'] ?? null,
                'last name' => $passenger['lastName'] ?? null,
                'e-ticket' => $passenger['eTicket'] ?? null,
            ])
                ->filter(fn (mixed $value): bool => blank($value))
                ->keys()
                ->implode(', ');

            if (filled($missing)) {
                return $label . ' is missing ' . $missing . '. Fetch Trip Details, check PTR Status after reissue, or update the passenger ticket details before requesting a quote.';
            }
        }

        return null;
    }

    private static function mergePostTicketingTickets(array $passengers, array $tripDetails): array
    {
        $customerInfos = self::tripDetailsCustomerInfos($tripDetails);

        if ($customerInfos->isEmpty()) {
            return $passengers;
        }

        $ticketNumbers = $customerInfos
            ->map(fn (array $customer): ?string => self::firstFilled($customer, [
                'eTicketNumber',
                'ETicketNumber',
                'eTicket',
                'ETicket',
                'TicketNumber',
                'ticketNumber',
            ]))
            ->filter()
            ->values();

        return collect($passengers)
            ->map(function (array $passenger, int $index) use ($customerInfos, $ticketNumbers): array {
                $matchedCustomer = $customerInfos->first(function (array $customer) use ($passenger): bool {
                    $firstName = strtolower((string) self::firstFilled($customer, ['firstName', 'FirstName', 'PassengerFirstName', 'GivenName']));
                    $lastName = strtolower((string) self::firstFilled($customer, ['lastName', 'LastName', 'PassengerLastName', 'Surname']));

                    return filled($firstName)
                        && filled($lastName)
                        && $firstName === strtolower((string) ($passenger['firstName'] ?? ''))
                        && $lastName === strtolower((string) ($passenger['lastName'] ?? ''));
                });

                $newTicket = $matchedCustomer
                    ? self::firstFilled($matchedCustomer, ['eTicketNumber', 'ETicketNumber', 'eTicket', 'ETicket', 'TicketNumber', 'ticketNumber'])
                    : $ticketNumbers->get($index);

                if (filled($newTicket)) {
                    $passenger['eTicket'] = $newTicket;
                }

                return $passenger;
            })
            ->values()
            ->all();
    }

    private static function tripDetailsCustomerInfos(array $tripDetails): \Illuminate\Support\Collection
    {
        return collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->filter(fn ($customer): bool => is_array($customer))
            ->map(fn (array $customer): array => $customer['CustomerInfo'] ?? $customer)
            ->values();
    }

    private static function postTicketingPaxOptions(FlightBooking $record, ?string $quotePtrUniqueId = null): array
    {
        return collect(self::postTicketingPaxSource($record, $quotePtrUniqueId))
            ->mapWithKeys(function (array $passenger, int $index): array {
                $name = trim(($passenger['title'] ?? '') . ' ' . ($passenger['firstName'] ?? '') . ' ' . ($passenger['lastName'] ?? ''));
                $ticket = filled($passenger['eTicket'] ?? null) ? $passenger['eTicket'] : 'No e-ticket';
                $type = $passenger['type'] ?? 'PAX';

                return [(string) $index => trim($type . ' - ' . ($name ?: 'Passenger') . ' - ' . $ticket)];
            })
            ->all();
    }

    private static function selectedPostTicketingPaxDetails(FlightBooking $record, array $selectedIndexes, ?string $quotePtrUniqueId = null): array
    {
        $passengers = self::postTicketingPaxSource($record, $quotePtrUniqueId);
        $selected = collect($selectedIndexes)
            ->map(fn (mixed $index): int => (int) $index)
            ->unique()
            ->values();

        return $selected
            ->map(fn (int $index): ?array => $passengers[$index] ?? null)
            ->filter(fn ($passenger): bool => is_array($passenger))
            ->values()
            ->all();
    }

    private static function postTicketingPaxSource(FlightBooking $record, ?string $quotePtrUniqueId = null): array
    {
        if (filled($quotePtrUniqueId)) {
            $quote = $record->postTicketingRequests()
                ->where('ptr_unique_id', $quotePtrUniqueId)
                ->latest()
                ->first();

            $quotePassengers = self::normalizePostTicketingPaxDetails($quote?->request_payload['paxDetails'] ?? []);

            if ($quotePassengers !== []) {
                return $quotePassengers;
            }
        }

        return self::postTicketingPaxDetails($record);
    }

    private static function defaultReissueAirport(FlightBooking $record, string $direction, ?string $scope = null): ?string
    {
        $segments = self::reissueScopeSegments($record, $scope ?: self::defaultReissueScope($record));

        if ($segments === []) {
            return null;
        }

        if ($direction === 'from') {
            return strtoupper((string) self::segmentValue($segments[0], ['from', 'airportOriginCode'], ''));
        }

        $last = $segments[array_key_last($segments)] ?? [];

        return strtoupper((string) self::segmentValue($last, ['to', 'airportDestinationCode'], ''));
    }

    private static function defaultReissueCabin(FlightBooking $record): string
    {
        return strtoupper((string) (
            $record->flight_snapshot['segments'][0]['cabinCode']
            ?? $record->flight_snapshot['cabinCode']
            ?? 'Y'
        ));
    }

    private static function replacementFlightAirlineCode(FlightBooking $record): ?string
    {
        $snapshot = $record->flight_snapshot ?? [];
        $segments = self::flattenReissueItineraryGroups([
            'segments' => is_array($snapshot['segments'] ?? null) ? $snapshot['segments'] : [],
            'returnSegments' => is_array($snapshot['returnSegments'] ?? null) ? $snapshot['returnSegments'] : [],
            'multiLegs' => is_array($snapshot['multiLegs'] ?? null) ? $snapshot['multiLegs'] : [],
        ]);

        $code = collect($segments)
            ->map(fn (array $segment): string => strtoupper(trim((string) self::segmentValue($segment, ['airlineCode', 'MarketingAirlineCode'], ''))))
            ->first(fn (string $value): bool => preg_match('/^[A-Z0-9]{2}$/', $value) === 1);

        if (filled($code)) {
            return $code;
        }

        $snapshotCode = strtoupper(trim((string) ($snapshot['airlineCode'] ?? '')));

        return preg_match('/^[A-Z0-9]{2}$/', $snapshotCode) === 1 ? $snapshotCode : null;
    }

    private static function replacementFlightPreview(?string $encodedOption): HtmlString
    {
        $segments = app(AdminReplacementFlightSearchService::class)->decodeOption($encodedOption);

        if ($segments === []) {
            return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">Select a replacement flight to review the full itinerary.</div>');
        }

        $summary = $segments[0]['optionSummary'] ?? [];
        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $stops = (int) ($summary['stops'] ?? max(0, count($segments) - 1));
        $duration = (int) ($summary['duration'] ?? collect($segments)->sum(fn (array $segment): int => (int) ($segment['duration'] ?? 0)));
        $fare = filled($summary['totalFare'] ?? null)
            ? trim((string) ($summary['currency'] ?? '') . ' ' . number_format((float) $summary['totalFare'], 2))
            : '-';

        $html = '<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<div class="border-b border-gray-100 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">';
        $html .= '<div class="flex flex-wrap items-start justify-between gap-4">';
        $html .= '<div>';
        $html .= '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Selected replacement itinerary</div>';
        $html .= '<div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">' . e(($first['airportOriginCode'] ?? '-') . ' -> ' . ($last['airportDestinationCode'] ?? '-')) . '</div>';
        $html .= '<div class="mt-1 text-sm text-gray-600 dark:text-gray-300">' . e(self::formatDateTime($first['departDT'] ?? null) . ' to ' . self::formatDateTime($last['arriveDT'] ?? null)) . '</div>';
        $html .= '</div>';
        $html .= '<div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">';
        $html .= self::previewMetric('Flights', $summary['flightNumbers'] ?? collect($segments)->map(fn (array $segment): string => trim(($segment['airlineCode'] ?? '') . ($segment['flightNumber'] ?? '')))->filter()->implode(' / '));
        $html .= self::previewMetric('Stops', $stops === 0 ? 'Nonstop' : $stops . ' stop' . ($stops === 1 ? '' : 's'));
        $html .= self::previewMetric('Duration', self::durationLabel($duration));
        $html .= self::previewMetric('Fare', $fare);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="divide-y divide-gray-100 dark:divide-white/10">';

        foreach ($segments as $index => $segment) {
            $html .= '<div class="p-4">';
            $html .= '<div class="flex flex-wrap items-start justify-between gap-4">';
            $html .= '<div class="min-w-0">';
            $html .= '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Segment ' . e((string) ($index + 1)) . '</div>';
            $html .= '<div class="mt-1 text-base font-semibold text-gray-950 dark:text-white">' . e(($segment['airportOriginCode'] ?? '-') . ' -> ' . ($segment['airportDestinationCode'] ?? '-')) . '</div>';
            $html .= '<div class="mt-1 text-sm text-gray-600 dark:text-gray-300">' . e(trim(($segment['airline'] ?? $segment['airlineCode'] ?? '-') . ' ' . ($segment['airlineCode'] ?? '') . ' ' . ($segment['flightNumber'] ?? ''))) . '</div>';
            $html .= '</div>';
            $html .= '<div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">';
            $html .= self::previewMetric('Depart', self::formatDateTime($segment['departDT'] ?? null));
            $html .= self::previewMetric('Arrive', self::formatDateTime($segment['arriveDT'] ?? null));
            $html .= self::previewMetric('Cabin', trim(($segment['cabin'] ?? '-') . ' (' . ($segment['cabinPreference'] ?? '-') . ')'));
            $html .= self::previewMetric('Aircraft', $segment['equipment'] ?? '-');
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return new HtmlString($html . '</div></div>');
    }

    private static function previewMetric(string $label, mixed $value): string
    {
        return '<div><div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e($label) . '</div><div class="mt-1 break-words font-semibold text-gray-950 dark:text-white">' . e(filled($value) ? (string) $value : '-') . '</div></div>';
    }

    private static function formatDateTime(?string $value): string
    {
        return self::watDateTime($value, 'D, d M Y H:i');
    }

    private static function durationLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '-';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return trim(($hours > 0 ? $hours . 'h ' : '') . ($remainingMinutes > 0 ? $remainingMinutes . 'm' : ''));
    }

    private static function watDateTime(mixed $value, string $format = 'd M Y, H:i'): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            $formatted = \Carbon\Carbon::parse(self::normalizeProviderDateTimeValue((string) $value))->timezone('Africa/Lagos')->format($format);

            return $formatted;
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private static function normalizeProviderDateTimeValue(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^(\d{4}-\d{2}-\d{2}[T\s]\d{2}):(\d{2})(\d{2})$/', $value, $matches)) {
            return $matches[1] . ':' . $matches[2] . ':' . $matches[3];
        }

        return $value;
    }

    private static function firstFilled(array $payload, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (filled($payload[$key] ?? null)) {
                return $payload[$key];
            }
        }

        return $default;
    }

    private static function canRunCancelBooking(FlightBooking $record): bool
    {
        return filled($record->unique_id)
            && $record->booking_status !== 'cancelled'
            && $record->booking_status !== 'ticketed'
            && ! $record->ticket_ordered
            && ! self::hasActivePostTicketingRequest($record, 'cancel');
    }

    private static function cancelProviderStatusContext(FlightBooking $record): HtmlString
    {
        try {
            $tripResult = app(AdminTicketingService::class)->tripDetails($record);
        } catch (Throwable $exception) {
            return new HtmlString(
                '<div class="rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-200">' .
                    e('Trip Details check failed: ' . $exception->getMessage()) .
                '</div>'
            );
        }

        $tripDetails = $tripResult['trip_details'] ?? [];
        $items = [
            'Provider booking status' => data_get($tripDetails, 'BookingStatus'),
            'Provider ticket status' => data_get($tripDetails, 'TicketStatus'),
            'Airline PNR' => $tripResult['airline_pnr'] ?? null,
            'UniqueID' => $record->unique_id,
        ];

        $warning = 'Cancellation cannot be reversed. If this booking has already been ticketed, use Void or Refund instead.';

        return new HtmlString(
            '<div class="rounded-lg border border-warning-200 bg-warning-50 p-3 dark:border-warning-500/30 dark:bg-warning-500/10">' .
                '<div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">' . e($warning) . '</div>' .
                self::definitionGridForAction($items) .
            '</div>'
        );
    }

    private static function definitionGridForAction(array $items): string
    {
        $html = '<dl class="grid gap-3 sm:grid-cols-2">';

        foreach ($items as $label => $value) {
            $html .= '<div>';
            $html .= '<dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e((string) $label) . '</dt>';
            $html .= '<dd class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white">' . e(filled($value) ? (string) $value : '-') . '</dd>';
            $html .= '</div>';
        }

        return $html . '</dl>';
    }

    private static function canRunPostTicketing(FlightBooking $record, string $operationType, ?string $requiresQuoteType = null): bool
    {
        if (! filled($record->unique_id) || $record->booking_status !== 'ticketed') {
            return false;
        }

        if (self::hasActivePostTicketingRequest($record, $operationType)) {
            return false;
        }

        if ($requiresQuoteType) {
            return self::availablePostTicketingQuoteOptions($record, $requiresQuoteType) !== [];
        }

        return true;
    }

    private static function availablePostTicketingQuoteOptions(FlightBooking $record, string $quoteOperationType): array
    {
        $finalOperationType = self::finalOperationForQuote($quoteOperationType);

        if ($finalOperationType === null) {
            return [];
        }

        $consumedQuotePtrs = self::consumedPostTicketingQuotePtrs($record, $finalOperationType);

        return $record->postTicketingRequests()
            ->where('operation_type', $quoteOperationType)
            ->whereNotNull('ptr_unique_id')
            ->latest()
            ->get()
            ->reject(fn (PostTicketingRequest $quote): bool => in_array((string) $quote->ptr_unique_id, $consumedQuotePtrs, true))
            ->mapWithKeys(fn (PostTicketingRequest $quote): array => [
                (string) $quote->ptr_unique_id => trim(collect([
                    (string) $quote->ptr_unique_id,
                    str((string) $quote->status)->replace('_', ' ')->headline()->toString(),
                    $quote->created_at?->timezone('Africa/Lagos')->format('d M Y, H:i'),
                ])->filter()->implode(' / ')),
            ])
            ->all();
    }

    private static function isOpenPostTicketingQuote(FlightBooking $record, string $finalOperationType, string $ptrUniqueId): bool
    {
        $quoteOperationType = match ($finalOperationType) {
            'void' => 'void_quote',
            'refund' => 'refund_quote',
            'reissue' => 'reissue_quote',
            default => null,
        };

        if ($quoteOperationType === null) {
            return true;
        }

        return array_key_exists($ptrUniqueId, self::availablePostTicketingQuoteOptions($record, $quoteOperationType));
    }

    private static function finalOperationForQuote(string $quoteOperationType): ?string
    {
        return match ($quoteOperationType) {
            'void_quote' => 'void',
            'refund_quote' => 'refund',
            'reissue_quote' => 'reissue',
            default => null,
        };
    }

    private static function consumedPostTicketingQuotePtrs(FlightBooking $record, string $finalOperationType): array
    {
        return $record->postTicketingRequests()
            ->where('operation_type', $finalOperationType)
            ->where('status', '!=', 'failed')
            ->latest()
            ->get()
            ->map(function (PostTicketingRequest $request) use ($finalOperationType): ?string {
                $payload = $request->request_payload ?? [];

                $ptrUniqueId = $finalOperationType === 'reissue'
                    ? ($payload['ptrUniqueID'] ?? null)
                    : ($payload['_selectedQuotePtrUniqueID'] ?? $payload['ptrUniqueID'] ?? null);

                return filled($ptrUniqueId) ? (string) $ptrUniqueId : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function postTicketingDescription(string $operationType, FlightBooking $record): string
    {
        $operation = str($operationType)->replace('_', ' ')->headline()->toString();

        return match ($operationType) {
            'cancel' => 'Cancel this ticketed booking. If the cancellation is accepted, the booking will be marked cancelled.',
            'void', 'refund', 'reissue' => "{$operation} should only be processed after the matching quote has been reviewed and selected.",
            'void_quote', 'refund_quote', 'reissue_quote' => "Request a {$operation}. Review the returned quote before processing the final action.",
            default => "Start {$operation} for this booking.",
        };
    }

    private static function hasActivePostTicketingRequest(FlightBooking $record, string $operationType): bool
    {
        if ($operationType === 'ptr_status') {
            return false;
        }

        return $record->postTicketingRequests()
            ->where('operation_type', $operationType)
            ->whereIn('status', ['pending', 'submitted', 'in_process', 'inprocess'])
            ->exists();
    }
}

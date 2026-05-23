<?php

namespace App\Filament\Resources\FlightBookings\Tables;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Mail\PaymentReceiptMail;
use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\PostTicketingRequest;
use App\Models\TicketingRecord;
use App\Services\AdminPostTicketingService;
use App\Services\AdminTicketingService;
use App\Services\SeerbitPaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
                    ->searchable()
                    ->placeholder('-')
                    ->description(fn (FlightBooking $record): string => trim(collect([$record->trip_type, $record->cabin])->filter()->implode(' | ')) ?: '-')
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
                    ->description(fn (FlightBooking $record): string => optional($record->created_at)->format('d M Y, H:i') ?: '-')
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

                self::recordTicketing($record, [
                    'action' => $result['ok'] ? 'trip_details_fetched' : 'trip_details_failed',
                    'previous_booking_status' => $record->booking_status,
                    'new_booking_status' => $record->booking_status,
                    'ticket_status' => $result['ticket_status'] ?? null,
                    'airline_pnr' => $result['airline_pnr'] ?? null,
                    'unique_id' => $record->unique_id,
                    'message' => $result['message'] ?? null,
                    'request_payload' => $result['request'] ?? [],
                    'response_payload' => $result['response'] ?? [],
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
                    $tripDetails = $tripResult['trip_details'] ?? [];

                    if (($tripResult['ok'] ?? false) && $tripDetails !== []) {
                        app(AdminTicketingService::class)->sendETicket($record, $tripDetails);
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
                    'response_payload' => $tripResult['response'] ?? [],
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
        return self::postTicketingAction('cancel', 'Cancel booking', 'heroicon-o-x-circle', 'danger');
    }

    public static function voidQuoteAction(): Action
    {
        return self::postTicketingAction('void_quote', 'Get void quote', 'heroicon-o-document-magnifying-glass', 'warning', needsPaxDetails: true);
    }

    public static function voidTicketAction(): Action
    {
        return self::postTicketingAction('void', 'Void ticket', 'heroicon-o-no-symbol', 'danger', needsPaxDetails: true, requiresQuoteType: 'void_quote');
    }

    public static function refundQuoteAction(): Action
    {
        return self::postTicketingAction('refund_quote', 'Get refund quote', 'heroicon-o-document-currency-dollar', 'warning', needsPaxDetails: true);
    }

    public static function refundTicketAction(): Action
    {
        return self::postTicketingAction('refund', 'Process refund', 'heroicon-o-receipt-refund', 'danger', needsPaxDetails: true, requiresQuoteType: 'refund_quote');
    }

    public static function reissueQuoteAction(): Action
    {
        return self::postTicketingAction('reissue_quote', 'Get reissue quote', 'heroicon-o-document-plus', 'warning', needsPaxDetails: true, needsPreferences: true);
    }

    public static function reissueTicketAction(): Action
    {
        return self::postTicketingAction('reissue', 'Process reissue', 'heroicon-o-arrow-path', 'danger', needsPaxDetails: true, needsPreferences: true, requiresQuoteType: 'reissue_quote');
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
                    ->options($record->postTicketingRequests()->whereNotNull('ptr_unique_id')->latest()->pluck('ptr_unique_id', 'ptr_unique_id')->all())
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
        bool $needsPreferences = false,
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
            ->form(function (FlightBooking $record) use ($needsPaxDetails, $needsPreferences, $requiresQuoteType): array {
                $schema = [
                    Placeholder::make('booking_context')
                        ->hiddenLabel()
                        ->content(fn () => self::actionContext($record, 'Post-ticketing operation')),
                ];

                if ($requiresQuoteType) {
                    $schema[] = Select::make('ptr_unique_id')
                        ->label('Quote PTR reference')
                        ->options($record->postTicketingRequests()
                            ->where('operation_type', $requiresQuoteType)
                            ->whereNotNull('ptr_unique_id')
                            ->latest()
                            ->pluck('ptr_unique_id', 'ptr_unique_id')
                            ->all())
                        ->required();
                }

                if ($needsPaxDetails) {
                    $schema[] = Textarea::make('pax_details_json')
                        ->label('Passenger ticket details')
                        ->helperText('Confirm the passenger names and e-ticket numbers before submitting this request.')
                        ->default(json_encode(self::postTicketingPaxDetails($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->required()
                        ->rows(8);
                }

                if ($needsPreferences) {
                    $schema[] = Textarea::make('requested_preferences_json')
                        ->label('Reissue preferences')
                        ->helperText('Add the revised travel preferences when handling a reissue request.')
                        ->default('[]')
                        ->rows(6);
                }

                $schema[] = Textarea::make('remark')
                    ->label('Remark / reason')
                    ->helperText('Explain the customer request or operational reason for this action.')
                    ->rows(3)
                    ->maxLength(2000);

                return $schema;
            })
            ->action(function (FlightBooking $record, array $data) use ($operationType, $needsPaxDetails, $needsPreferences): void {
                $extraPayload = [];

                if (isset($data['ptr_unique_id'])) {
                    $extraPayload['ptrUniqueID'] = $data['ptr_unique_id'];
                }

                if ($needsPaxDetails) {
                    $decoded = json_decode($data['pax_details_json'] ?? '[]', true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Notification::make()->title('Invalid passenger ticket details')->body('Review the formatting and try again.')->danger()->send();
                        return;
                    }
                    $extraPayload['paxDetails'] = self::normalizePostTicketingPaxDetails($decoded);
                }

                if ($needsPreferences && filled($data['requested_preferences_json'] ?? null)) {
                    $decoded = json_decode($data['requested_preferences_json'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Notification::make()->title('Invalid reissue preferences')->body('Review the formatting and try again.')->danger()->send();
                        return;
                    }
                    $extraPayload['RequestedPreferences'] = $decoded;
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
            $record->update(['booking_status' => 'cancelled']);
        }

        Notification::make()
            ->title(($result['ok'] ?? false) ? 'Post-ticketing request stored' : 'Post-ticketing request failed')
            ->body(($result['message'] ?? 'Request completed.') . ' PTR: ' . ($ptr->ptr_unique_id ?: '-'))
            ->{($result['ok'] ?? false) ? 'success' : 'danger'}()
            ->send();
    }

    private static function postTicketingPaxDetails(FlightBooking $record): array
    {
        return self::normalizePostTicketingPaxDetails($record->passengers_snapshot ?? []);
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

    private static function firstFilled(array $payload, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (filled($payload[$key] ?? null)) {
                return $payload[$key];
            }
        }

        return $default;
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
            return $record->postTicketingRequests()
                ->where('operation_type', $requiresQuoteType)
                ->whereNotNull('ptr_unique_id')
                ->exists();
        }

        return true;
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

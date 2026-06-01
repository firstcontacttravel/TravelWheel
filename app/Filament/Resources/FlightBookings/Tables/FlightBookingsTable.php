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
                        ->options($record->postTicketingRequests()
                            ->where('operation_type', $requiresQuoteType)
                            ->whereNotNull('ptr_unique_id')
                            ->latest()
                            ->pluck('ptr_unique_id', 'ptr_unique_id')
                            ->all())
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
                        ->helperText('Confirm the passenger names and e-ticket numbers before submitting this request.')
                        ->default(json_encode(self::postTicketingPaxDetails($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
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
                    $schema[] = Select::make('replacement_from')
                        ->label('Replacement from')
                        ->helperText('Search by city, airport name, country, or IATA code. Defaults to the current first segment when available.')
                        ->default(fn () => self::defaultReissueAirport($record, 'from'))
                        ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                        ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                        ->searchable()
                        ->preload(false)
                        ->required()
                        ->live();

                    $schema[] = Select::make('replacement_to')
                        ->label('Replacement to')
                        ->helperText('Search by city, airport name, country, or IATA code. Defaults to the current last segment when available.')
                        ->default(fn () => self::defaultReissueAirport($record, 'to'))
                        ->getSearchResultsUsing(fn (?string $search): array => app(AdminReplacementFlightSearchService::class)->airportSearchOptions($search))
                        ->getOptionLabelUsing(fn (?string $value): ?string => app(AdminReplacementFlightSearchService::class)->airportLabel($value))
                        ->searchable()
                        ->preload(false)
                        ->required()
                        ->live();

                    $schema[] = Select::make('replacement_cabin')
                        ->label('Cabin')
                        ->options([
                            'Y' => 'Economy (Y)',
                            'S' => 'Premium Economy (S)',
                            'C' => 'Business (C)',
                            'F' => 'First (F)',
                        ])
                        ->default(fn () => self::defaultReissueCabin($record))
                        ->required()
                        ->live();

                    $schema[] = DatePicker::make('replacement_departure_date')
                        ->label('New departure date')
                        ->native(false)
                        ->required()
                        ->live();

                    $schema[] = Select::make('replacement_flight_option')
                        ->label('Replacement flight')
                        ->helperText('Options are loaded from the availability API. Select a flight, then review the full itinerary below before requesting the quote.')
                        ->options(fn (Get $get): array => app(AdminReplacementFlightSearchService::class)->options($record, [
                            'from' => $get('replacement_from'),
                            'to' => $get('replacement_to'),
                            'departure_date' => $get('replacement_departure_date'),
                            'cabin' => $get('replacement_cabin'),
                        ]))
                        ->searchable()
                        ->preload(false)
                        ->live()
                        ->required()
                        ->columnSpanFull();

                    $schema[] = Placeholder::make('replacement_flight_preview')
                        ->label('Selected flight details')
                        ->content(fn (Get $get): HtmlString => self::replacementFlightPreview($get('replacement_flight_option')))
                        ->visible(fn (Get $get): bool => filled($get('replacement_flight_option')))
                        ->columnSpanFull();
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

                    $extraPayload['paxDetails'] = $selectedPassengers;
                }

                if ($needsReissueSegments) {
                    $replacementSegments = app(AdminReplacementFlightSearchService::class)
                        ->decodeOption($data['replacement_flight_option'] ?? null);

                    if ($replacementSegments === []) {
                        Notification::make()->title('Invalid replacement flight')->body('Search again and select a replacement flight before requesting a quote.')->danger()->send();
                        return;
                    }

                    $extraPayload['OriginDestinationInfo'] = self::apiReissueOriginDestinationInfo($replacementSegments);
                    $extraPayload['_displayReplacementSegments'] = $replacementSegments;
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

        $ticketResponse = $record->ticket_api_response ?: [];
        $ticketResponse['latest_reissue'] = $result['response'] ?? [];
        $ticketResponse['latest_reissue_trip_details'] = $tripResult['response'] ?? [];
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
                'trip_details' => $tripResult['response'] ?? [],
            ],
        ]);

        if (! ($tripResult['ok'] ?? false) || blank($record->contact_email) || empty($tripResult['trip_details'] ?? [])) {
            return;
        }

        try {
            app(AdminTicketingService::class)->sendETicket($record->fresh(), $tripResult['trip_details']);

            self::recordTicketing($record->fresh(), [
                'action' => 'reissue_eticket_sent',
                'previous_booking_status' => 'ticketed',
                'new_booking_status' => 'ticketed',
                'ticket_status' => $tripResult['ticket_status'] ?? null,
                'airline_pnr' => $tripResult['airline_pnr'] ?? null,
                'unique_id' => $record->unique_id,
                'message' => 'Updated e-ticket sent to ' . $record->contact_email,
                'request_payload' => $tripResult['request'] ?? [],
                'response_payload' => $tripResult['response'] ?? [],
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

            if (($tripResult['ok'] ?? false) && filled($record->contact_email) && ! empty($tripResult['trip_details'] ?? [])) {
                app(AdminTicketingService::class)->sendETicket($record->fresh(), $tripResult['trip_details']);
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
                    'trip_details' => $tripResult['response'] ?? [],
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
            ->map(fn (array $segment): array => [
                'airportOriginCode' => strtoupper(trim((string) ($segment['airportOriginCode'] ?? ''))),
                'airportDestinationCode' => strtoupper(trim((string) ($segment['airportDestinationCode'] ?? ''))),
                'cabinPreference' => strtoupper(trim((string) ($segment['cabinPreference'] ?? 'Y'))),
                'departureDate' => (string) ($segment['departureDate'] ?? ''),
                'airlineCode' => strtoupper(trim((string) ($segment['airlineCode'] ?? ''))),
                'flightNumber' => trim((string) ($segment['flightNumber'] ?? '')),
            ])
            ->values()
            ->all();
    }

    private static function reissuedFlightSnapshot(FlightBooking $record, PostTicketingRequest $ptr): array
    {
        $quoteRequest = self::matchingReissueQuoteRequest($record, $ptr);
        $segments = $quoteRequest?->request_payload['_displayReplacementSegments']
            ?? $quoteRequest?->request_payload['OriginDestinationInfo']
            ?? [];

        if (! is_array($segments) || $segments === []) {
            return [];
        }

        $mappedSegments = collect($segments)
            ->filter(fn ($segment): bool => is_array($segment))
            ->map(function (array $segment): array {
                $departureDate = (string) ($segment['departureDate'] ?? '');
                $departDt = $segment['departDT'] ?? $departureDate;
                $arriveDt = $segment['arriveDT'] ?? null;
                $airlineCode = strtoupper((string) ($segment['airlineCode'] ?? ''));
                $flightNumber = (string) ($segment['flightNumber'] ?? '');

                return [
                    'from' => strtoupper((string) ($segment['airportOriginCode'] ?? '')),
                    'to' => strtoupper((string) ($segment['airportDestinationCode'] ?? '')),
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
                    'cabin' => $segment['cabin'] ?? self::cabinLabel($segment['cabinPreference'] ?? 'Y'),
                    'cabinCode' => strtoupper((string) ($segment['cabinPreference'] ?? 'Y')),
                    'equipment' => $segment['equipment'] ?? '',
                    'eticket' => true,
                ];
            })
            ->values()
            ->all();

        if ($mappedSegments === []) {
            return [];
        }

        $first = $mappedSegments[0];
        $last = $mappedSegments[array_key_last($mappedSegments)];
        $existing = $record->flight_snapshot ?? [];

        return array_merge($existing, [
            'airline' => $first['airline'] ?? ($existing['airline'] ?? ''),
            'airlineCode' => $first['airlineCode'] ?? ($existing['airlineCode'] ?? ''),
            'cabin' => $first['cabin'] ?? ($existing['cabin'] ?? ''),
            'cabinCode' => $first['cabinCode'] ?? ($existing['cabinCode'] ?? 'Y'),
            'segments' => $mappedSegments,
            'returnSegments' => [],
            'multiLegs' => [],
            'tripType' => 'oneway',
            'directionInd' => 'OneWay',
            'departDT' => $first['departDT'] ?? null,
            'arriveDT' => $last['arriveDT'] ?? null,
            'departTime' => $first['departTime'] ?? '',
            'arriveTime' => $last['arriveTime'] ?? '',
            'departDateLabel' => filled($first['departDT'] ?? null) ? self::watDateTime($first['departDT'], 'D, d M') : '',
            'stops' => max(0, count($mappedSegments) - 1),
        ]);
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

        if ($passengers === [] || collect($passengers)->every(fn (array $passenger): bool => filled($passenger['eTicket'] ?? null))) {
            return $passengers;
        }

        try {
            $tripResult = app(AdminTicketingService::class)->tripDetails($record);
        } catch (Throwable) {
            return $passengers;
        }

        if (! ($tripResult['ok'] ?? false)) {
            return $passengers;
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

    private static function mergePostTicketingTickets(array $passengers, array $tripDetails): array
    {
        $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->filter(fn ($customer): bool => is_array($customer))
            ->map(fn (array $customer): array => $customer['CustomerInfo'] ?? $customer)
            ->values();

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
                if (filled($passenger['eTicket'] ?? null)) {
                    return $passenger;
                }

                $matchedCustomer = $customerInfos->first(function (array $customer) use ($passenger): bool {
                    $firstName = strtolower((string) self::firstFilled($customer, ['firstName', 'FirstName', 'PassengerFirstName', 'GivenName']));
                    $lastName = strtolower((string) self::firstFilled($customer, ['lastName', 'LastName', 'PassengerLastName', 'Surname']));

                    return filled($firstName)
                        && filled($lastName)
                        && $firstName === strtolower((string) ($passenger['firstName'] ?? ''))
                        && $lastName === strtolower((string) ($passenger['lastName'] ?? ''));
                });

                $passenger['eTicket'] = $matchedCustomer
                    ? self::firstFilled($matchedCustomer, ['eTicketNumber', 'ETicketNumber', 'eTicket', 'ETicket', 'TicketNumber', 'ticketNumber'])
                    : $ticketNumbers->get($index);

                return $passenger;
            })
            ->values()
            ->all();
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

    private static function defaultReissueAirport(FlightBooking $record, string $direction): ?string
    {
        $segments = $record->flight_snapshot['segments'] ?? [];

        if (! is_array($segments) || $segments === []) {
            return null;
        }

        if ($direction === 'from') {
            return strtoupper((string) ($segments[0]['from'] ?? ''));
        }

        $last = $segments[array_key_last($segments)] ?? [];

        return strtoupper((string) ($last['to'] ?? ''));
    }

    private static function defaultReissueCabin(FlightBooking $record): string
    {
        return strtoupper((string) (
            $record->flight_snapshot['segments'][0]['cabinCode']
            ?? $record->flight_snapshot['cabinCode']
            ?? 'Y'
        ));
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

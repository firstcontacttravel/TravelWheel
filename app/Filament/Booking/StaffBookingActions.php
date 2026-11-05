<?php

namespace App\Filament\Booking;

use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\ActivityLog;
use App\Models\StaffBooking;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** What every product's booking form shares, and what can be done to a booking afterwards. */
class StaffBookingActions
{
    /** @return list<\Filament\Schemas\Components\Component> */
    public static function customerFields(): array
    {
        return [
            TextInput::make('full_name')->label('Customer name')->required()->maxLength(255),
            TextInput::make('email')->label('Customer email')->email()->required()->maxLength(255),
            TextInput::make('phone_number')->label('Customer phone')->tel()->required()->maxLength(20),
            TextInput::make('passengers')->numeric()->minValue(1)->required()->default(1),
            DatePicker::make('pickup_date')->required()->native(false)->minDate(today()),
            TimePicker::make('pickup_time')->required()->seconds(false),
        ];
    }

    public static function sendLinkToggle(): Toggle
    {
        return Toggle::make('send_link')
            ->label('Email the customer a payment link now')
            ->default(true)
            ->columnSpanFull();
    }

    /** Records who made the booking, and sends the link if asked. */
    public static function finish(Model $booking, array $data): void
    {
        $staffBooking = StaffBooking::record($booking, auth()->user());

        ActivityLog::record('staff_booking.created', "Booked {$booking->payment_reference} for {$booking->full_name}", $booking);

        if ($data['send_link'] ?? false) {
            self::sendLink($staffBooking);

            return;
        }

        Notification::make()->title('Booking created: '.$booking->payment_reference)->success()->send();
    }

    /** @return list<Action> */
    public static function all(): array
    {
        return [
            Action::make('sendPaymentLink')
                ->label(fn (StaffBooking $record): string => $record->link_sent_at ? 'Resend link' : 'Send link')
                ->icon('heroicon-o-envelope')
                ->visible(fn (StaffBooking $record): bool => $record->isAwaitingPayment())
                ->authorize(fn (): bool => auth()->user()?->canBookForCustomers() ?? false)
                ->requiresConfirmation()
                ->modalDescription(fn (StaffBooking $record): string => 'Emails a payment link to '.$record->bookable->email.'.')
                ->action(fn (StaffBooking $record) => self::sendLink($record)),
            Action::make('recordPayment')
                ->label('Record payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (StaffBooking $record): bool => $record->isAwaitingPayment())
                ->authorize(fn (): bool => auth()->user()?->canHandleMoney() ?? false)
                ->modalDescription('Only once the money has been received. This confirms the booking and emails the customer.')
                ->form([
                    Select::make('method')->options(StaffBooking::RECORDED_METHODS)->required(),
                    TextInput::make('reference')
                        ->label('Bank or POS reference, or receipt number')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (StaffBooking $record, array $data): void {
                    $record->recordPayment($data['method'], $data['reference'], auth()->user());

                    ActivityLog::record('staff_booking.payment_recorded', "Recorded {$data['method']} payment for {$record->bookable->payment_reference}", $record->bookable, ['reference' => $data['reference']]);

                    Notification::make()->title('Payment recorded and customer emailed')->success()->send();
                }),
            Action::make('cancelBooking')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (StaffBooking $record): bool => $record->isAwaitingPayment())
                ->authorize(fn (): bool => auth()->user()?->canBookForCustomers() ?? false)
                ->requiresConfirmation()
                ->modalDescription('The payment link stops working. This cannot be undone.')
                ->action(function (StaffBooking $record): void {
                    $record->cancel();

                    ActivityLog::record('staff_booking.cancelled', "Cancelled unpaid booking {$record->bookable->payment_reference}", $record->bookable);

                    Notification::make()->title('Booking cancelled')->success()->send();
                }),
        ];
    }

    private static function sendLink(StaffBooking $staffBooking): void
    {
        $booking = $staffBooking->bookable;

        // Sent straight away so staff know at once if it failed, and can copy the link instead
        try {
            Mail::to($booking->email)->send(new StaffBookingPaymentLinkMail($staffBooking));
            $staffBooking->update(['link_sent_at' => now()]);

            Notification::make()->title('Payment link sent to '.$booking->email)->success()->send();
        } catch (\Throwable $e) {
            Log::error('Staff booking payment link failed', ['reference' => $booking->payment_reference, 'error' => $e->getMessage()]);

            Notification::make()
                ->title('The email could not be sent')
                ->body('Send the customer this link another way: '.$staffBooking->payUrl())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}

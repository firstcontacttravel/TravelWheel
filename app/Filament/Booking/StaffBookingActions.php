<?php

namespace App\Filament\Booking;

use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\ActivityLog;
use App\Models\StaffBooking;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** What happens to a booking staff made: recording who made it, the payment link, recording a payment. */
class StaffBookingActions
{
    /** Records who made the booking, and sends the link if asked. */
    public static function finish(Model $booking, array $data): void
    {
        $staffBooking = StaffBooking::record($booking, auth()->user());

        ActivityLog::record('staff_booking.created', "Booked {$staffBooking->bookingReference()} for {$staffBooking->customerName()}", $booking);

        if ($data['send_link'] ?? false) {
            self::sendLink($staffBooking);

            return;
        }

        Notification::make()->title('Booking created: '.$staffBooking->bookingReference())->success()->send();
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
                ->modalDescription(fn (StaffBooking $record): string => ($record->isVisa() ? 'Emails a link to finish and pay for the application to ' : 'Emails a payment link to ').$record->customerEmail().'.')
                ->action(fn (StaffBooking $record) => self::sendLink($record)),
            Action::make('recordPayment')
                ->label('Record payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (StaffBooking $record): bool => $record->canRecordPayment())
                ->authorize(fn (): bool => auth()->user()?->canHandleMoney() ?? false)
                ->modalDescription(fn (StaffBooking $record): string => match (true) {
                    $record->isVisa() => 'Only once the money has been received. The amount due is ₦'.number_format((float) $record->total(), 2).', rechecked against today\'s exchange rates when you record it. This submits the application to the visa team and emails the customer.',
                    $record->isFlight() => 'Only once the money has been received. This marks the booking paid and emails the customer a receipt. The flight team then orders the ticket.',
                    default => 'Only once the money has been received. This confirms the booking and emails the customer.',
                })
                ->form([
                    Select::make('method')->options(StaffBooking::RECORDED_METHODS)->required(),
                    TextInput::make('reference')
                        ->label('Bank or POS reference, or receipt number')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (StaffBooking $record, array $data): void {
                    try {
                        $record->recordPayment($data['method'], $data['reference'], auth()->user());
                    } catch (ValidationException $e) {
                        Notification::make()->title('Payment not recorded')->body(collect($e->errors())->flatten()->first())->danger()->persistent()->send();

                        return;
                    }

                    ActivityLog::record('staff_booking.payment_recorded', "Recorded {$data['method']} payment for {$record->bookingReference()}", $record->bookable, ['reference' => $data['reference']]);

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

                    ActivityLog::record('staff_booking.cancelled', "Cancelled unpaid booking {$record->bookingReference()}", $record->bookable);

                    Notification::make()->title('Booking cancelled')->success()->send();
                }),
        ];
    }

    private static function sendLink(StaffBooking $staffBooking): void
    {
        $email = $staffBooking->customerEmail();

        // Sent straight away so staff know at once if it failed, and can copy the link instead
        try {
            Mail::to($email)->send(new StaffBookingPaymentLinkMail($staffBooking));
            $staffBooking->update(['link_sent_at' => now()]);

            Notification::make()->title('Link sent to '.$email)->success()->send();
        } catch (\Throwable $e) {
            Log::error('Staff booking payment link failed', ['reference' => $staffBooking->bookingReference(), 'error' => $e->getMessage()]);

            Notification::make()
                ->title('The email could not be sent')
                ->body('Send the customer this link another way: '.$staffBooking->payUrl())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}

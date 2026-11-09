<?php

namespace App\Services\Flights;

use App\Filament\Booking\StaffBookingActions;
use App\Http\Middleware\CorporateFlightSession;
use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\AdminPostTicketingService;
use App\Services\DurableMailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A member of staff booking a flight for a customer on the website's own
 * search and checkout. The fare is held with the airline instead of paid; the
 * customer then pays from an emailed link, or Finance records the payment and
 * the flight team issues the ticket.
 *
 * Only fares the airline can hold qualify: SkyLink and WebFare fares are paid
 * by card at booking, with nothing to hold.
 */
class StaffFlightBooking
{
    public const SESSION_KEY = 'flights.staff_booking';

    public function start(User $staff, bool $sendLink): void
    {
        // A fresh search: nothing from a checkout this browser had going carries over
        session()->forget(CorporateFlightSession::FLIGHT_SESSION_KEYS);
        session()->put(self::SESSION_KEY, ['user_id' => $staff->id, 'send_link' => $sendLink]);
    }

    /** The member of staff booking, while they are still signed in and allowed to. */
    public function staff(): ?User
    {
        $state = session(self::SESSION_KEY);
        $user = auth()->user();

        if (! is_array($state) || ! $user instanceof User || (int) $user->id !== (int) ($state['user_id'] ?? 0) || ! $user->canBookForCustomers()) {
            return null;
        }

        return $user;
    }

    public function active(): bool
    {
        return $this->staff() !== null;
    }

    public function stop(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function holdable(array $flight): bool
    {
        return ($flight['source'] ?? 'travelnext') !== 'skylink'
            && strtolower((string) ($flight['fareType'] ?? 'public')) !== 'webfare';
    }

    /** The fare is held: the booking joins the staff bookings, and the customer is emailed if staff asked. */
    public function finish(FlightBooking $booking): void
    {
        $sendLink = (bool) (session(self::SESSION_KEY)['send_link'] ?? false);
        $booking->update(['booked_by_user_id' => auth()->id()]);

        StaffBookingActions::finish($booking, ['send_link' => $sendLink]);

        $this->stop();
    }

    /**
     * Finance recording a transfer, POS or cash payment: the same as the
     * flight bookings' "Mark paid". The ticket is not ordered here; the flight
     * team orders it once they have checked the hold and the fare.
     */
    public function completePayment(FlightBooking $booking, ?string $method = null, ?string $reference = null): void
    {
        $paid = DB::transaction(function () use ($booking, $method, $reference): ?FlightBooking {
            $booking = FlightBooking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->payment_status === 'paid') {
                return null;
            }

            $previous = $booking->payment_status;
            $holdExpired = $booking->tkt_time_limit?->isPast();
            $currency = strtoupper((string) ($booking->currency ?: 'NGN'));

            $booking->update([
                'payment_status' => 'paid',
                'payment_method' => $method ?: 'bank_transfer',
                // payment_reference is unique to gateway payments; a receipt number goes here
                'bank_transfer_reference' => $reference,
                'payment_amount' => $booking->payment_amount ?: $booking->total_price,
                'payment_charged_amount' => $booking->total_price,
                'payment_currency' => $currency,
                'payment_verified_at' => now(),
                'booking_status' => $holdExpired ? 'hold_expired_review' : $booking->booking_status,
                'reconciliation_note' => $holdExpired
                    ? 'Payment recorded by staff after the airline hold expired; manual recovery required.'
                    : $booking->reconciliation_note,
            ]);

            PaymentVerificationRecord::create([
                'flight_booking_id' => $booking->id,
                'verified_by' => auth()->id(),
                'action' => 'staff_booking_payment_recorded',
                'previous_payment_status' => $previous,
                'new_payment_status' => 'paid',
                'payment_reference' => $reference,
                'amount_received' => $booking->total_price,
                'currency' => $currency,
                'verification_note' => 'Recorded from New Booking: '.(StaffBooking::RECORDED_METHODS[$method] ?? $method).'.',
            ]);

            return $booking;
        });

        if ($paid && filled($paid->contact_email)) {
            $sent = app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_RECEIPT, (string) $paid->contact_email, $paid, [], 'flight-payment-receipt:'.$paid->id);
            if ($sent) {
                $paid->update(['payment_receipt_sent' => true]);
            }
        }
    }

    /** Releases the seat at the airline where it can; a hold it won't release lapses at its time limit. */
    public function cancel(FlightBooking $booking): void
    {
        $released = false;

        if ($booking->usesTravelNextApi() && filled($booking->unique_id)) {
            try {
                $released = (bool) (app(AdminPostTicketingService::class)->call($booking, 'cancel')['ok'] ?? false);
            } catch (Throwable $e) {
                Log::warning('Staff flight booking: airline cancel failed', ['booking_ref' => $booking->booking_ref, 'error' => $e->getMessage()]);
            }
        }

        $booking->update([
            'booking_status' => 'cancelled',
            'payment_status' => 'cancelled',
            'reconciliation_note' => $released
                ? 'Cancelled by staff before payment; airline hold released.'
                : 'Cancelled by staff before payment; the airline did not confirm releasing the hold, so it lapses at its time limit.',
        ]);
    }
}

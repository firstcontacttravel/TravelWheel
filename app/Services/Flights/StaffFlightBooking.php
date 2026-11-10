<?php

namespace App\Services\Flights;

use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\StaffBooking;
use App\Services\AdminPostTicketingService;
use App\Services\DurableMailService;
use App\Services\TravelnextFlightService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A flight staff book for a customer from the admin, held instead of paid,
 * the way the website holds one for a customer who pays later:
 *
 * - With the airline (TravelNext): the seat is reserved until the airline's
 *   deadline. The customer pays from an emailed link, or Finance records the
 *   payment and the flight team orders the ticket.
 * - On our side ("hold and pay later", an API that can't hold, SkyLink): the
 *   booking is saved for a day with nothing at the airline. The customer
 *   pays by card from the link; the fare is then priced again and booked.
 *
 * A WebFare can be neither: the airline issues it only as it is paid.
 */
class StaffFlightBooking
{
    public const AIRLINE_HOLD = 'airline';

    public const PAY_LATER = 'pay_later';

    public const WEBFARE_REASON = "A WebFare is issued only when it's paid by card, so it can't be held or saved for the customer. They can book it themselves on the website.";

    public function __construct(
        private readonly FlightSupplierRegistry $suppliers,
        private readonly FlightSupplierControl $control,
        private readonly FlightBookingRecorder $recorder,
        private readonly FlightPlatformHold $platformHold,
    ) {}

    /**
     * Every switched-on flight API.
     *
     * @return array<string, \App\Contracts\FlightSupplier>
     */
    public function searchableSuppliers(): array
    {
        return array_filter($this->suppliers->all(), fn ($supplier, string $key): bool => $this->control->isEnabled($key), ARRAY_FILTER_USE_BOTH);
    }

    /** How this fare can be held for the customer: AIRLINE_HOLD, PAY_LATER, or null when it can't. */
    public function holdKind(array $flight): ?string
    {
        $supplier = (string) ($flight['source'] ?? '');

        if (! array_key_exists($supplier, $this->searchableSuppliers()) || strtolower((string) ($flight['fareType'] ?? 'public')) === 'webfare') {
            return null;
        }

        if ($this->suppliers->get($supplier)->supportsHold()) {
            return self::AIRLINE_HOLD;
        }

        return $this->platformHold->offersPayLater($flight) ? self::PAY_LATER : null;
    }

    public function isPayLater(FlightBooking $booking): bool
    {
        return $this->platformHold->isPayLater($booking);
    }

    /**
     * Holds the re-confirmed fare, with the airline or on our side, and
     * records the booking as the website does for a customer paying later.
     *
     * @throws ValidationException when the fare can't be held, or the airline refuses
     */
    public function hold(array $flight, array $criteria, string $flightSessionId, array $contact, array $passengers): FlightBooking
    {
        $kind = $this->holdKind($flight);

        if ($kind === null) {
            throw ValidationException::withMessages(['fare' => self::WEBFARE_REASON]);
        }

        $context = [
            'session_id' => $flightSessionId,
            'trip_type' => $flight['directionInd'] ?? strtoupper((string) ($criteria['trip'] ?? '')),
            'booked_by' => auth()->user(),
        ];

        // Nothing is sent to the airline: the booking is made when the customer pays
        if ($kind === self::PAY_LATER) {
            return $this->recorder->record($flight, $contact, $passengers, [], $context, [
                'unique_id' => '',
                'booking_status' => 'on_hold',
                'payment_status' => 'pending',
                'payment_method' => 'gateway',
                'hold_type' => FlightPlatformHold::PAY_LATER,
                'tkt_time_limit' => $this->platformHold->payLaterUntil()->toIso8601String(),
            ]);
        }

        $result = app(TravelnextFlightService::class)->book($flight, ['contact' => $contact, 'passengers' => $passengers], $flightSessionId);
        $bookResult = data_get($result, 'data.BookFlightResponse.BookFlightResult', []);
        $uniqueId = (string) ($bookResult['UniqueID'] ?? '');

        if (($result['outcome'] ?? null) === 'unclear') {
            Log::warning('Staff flight booking: hold outcome unclear', ['route' => $flight['segments'][0]['from'] ?? null, 'contact' => $contact['email'] ?? null]);

            throw ValidationException::withMessages(['fare' => "The airline didn't answer in time, so the seat may or may not be held. Ask the flight team to check before trying again."]);
        }

        if (($result['error'] ?? true) || ! filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN) || $uniqueId === '') {
            $reason = data_get($bookResult, 'Errors.0.Message') ?? data_get($bookResult, 'Errors.Error.ErrorMessage') ?? ($result['message'] ?? null);

            throw ValidationException::withMessages(['fare' => trim('The airline could not hold this fare. '.($reason ?: 'Search again for a current fare.'))]);
        }

        return $this->recorder->record($flight, $contact, $passengers, $result['data'], $context, [
            'unique_id' => $uniqueId,
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => ($bookResult['TktTimeLimit'] ?? null) ?: null,
        ]);
    }

    /**
     * Finance recording a transfer, POS or cash payment: the same as the
     * flight bookings' "Mark paid". The ticket is not ordered here; the flight
     * team orders it once they have checked the hold and the fare.
     */
    public function completePayment(FlightBooking $booking, ?string $method = null, ?string $reference = null): void
    {
        if ($this->isPayLater($booking)) {
            throw ValidationException::withMessages(['payment' => 'This flight is saved to pay later and can only be paid by card, from the link: the airline booking is made as the card payment goes through.']);
        }

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

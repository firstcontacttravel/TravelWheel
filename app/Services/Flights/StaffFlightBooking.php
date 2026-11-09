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
 * A flight staff book for a customer from the admin: the fare is held with the
 * airline instead of paid. The customer then pays from an emailed link, or
 * Finance records the payment and the flight team issues the ticket.
 */
class StaffFlightBooking
{
    public function __construct(
        private readonly FlightSupplierRegistry $suppliers,
        private readonly FlightSupplierControl $control,
        private readonly FlightBookingRecorder $recorder,
    ) {}

    /**
     * The switched-on flight APIs that can hold a fare before payment. SkyLink
     * is paid by card when booked, so it has nothing to hold.
     *
     * @return array<string, \App\Contracts\FlightSupplier>
     */
    public function holdingSuppliers(): array
    {
        return array_filter(
            $this->suppliers->all(),
            fn ($supplier, string $key): bool => $supplier->supportsHold() && $this->control->isEnabled($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** A WebFare is paid by card when booked even from an API that holds others. */
    public function holdable(array $flight): bool
    {
        $supplier = (string) ($flight['source'] ?? '');

        return array_key_exists($supplier, $this->holdingSuppliers())
            && strtolower((string) ($flight['fareType'] ?? 'public')) !== 'webfare';
    }

    /**
     * Holds the re-confirmed fare with the airline and records the booking, as
     * the website does when a customer chooses to pay later.
     *
     * @throws ValidationException when the airline refuses the hold
     */
    public function hold(array $flight, array $criteria, string $flightSessionId, array $contact, array $passengers): FlightBooking
    {
        if (! $this->holdable($flight)) {
            throw ValidationException::withMessages(['fare' => "This fare is paid by card when it is booked, so it can't be held. Choose another fare."]);
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

        return $this->recorder->record($flight, $contact, $passengers, $result['data'], [
            'session_id' => $flightSessionId,
            'trip_type' => $flight['directionInd'] ?? strtoupper((string) ($criteria['trip'] ?? '')),
            'booked_by' => auth()->user(),
        ], [
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

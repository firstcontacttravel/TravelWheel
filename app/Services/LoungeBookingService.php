<?php

namespace App\Services;

use App\Mail\LoungeBookingMail;
use App\Mail\LoungeReservationNotificationMail;
use App\Models\Lounge;
use App\Models\LoungeBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pricing and completing lounge bookings, whichever way they arrive: the public
 * booking page or staff booking from the admin. The price is always worked out
 * here from the lounge, never taken from the request.
 */
class LoungeBookingService
{
    public const VAT_PERCENT = 7.5;

    /**
     * @return array{adult_amount: float, child_amount: float, infant_amount: float, subtotal: float, vat: float, total: float}
     */
    public function quote(Lounge $lounge, int $adults, int $children, int $infants): array
    {
        $adultAmount = round($lounge->bookingPrice('A') * max(0, $adults), 2);
        $childAmount = round($lounge->bookingPrice('B') * max(0, $children), 2);
        $infantAmount = round($lounge->bookingPrice('C') * max(0, $infants), 2);
        $subtotal = $adultAmount + $childAmount + $infantAmount;
        $vat = round($subtotal * self::VAT_PERCENT / 100, 2);

        return [
            'adult_amount' => $adultAmount,
            'child_amount' => $childAmount,
            'infant_amount' => $infantAmount,
            'subtotal' => $subtotal,
            'vat' => $vat,
            'total' => $subtotal + $vat,
        ];
    }

    /** A booking made before payment, which staff do for a customer. */
    public function createPending(Lounge $lounge, array $data, string $paymentOption): LoungeBooking
    {
        $quote = $this->quote($lounge, $data['adults'], $data['children'], $data['infants']);
        $reference = strtoupper('LNG'.bin2hex(random_bytes(6)));

        return LoungeBooking::create([
            'lounge_id' => $lounge->id,
            'lounge_name' => $lounge->brand_name,
            'provider' => $lounge->provider,
            'provider_url' => $lounge->provider_url,
            'payment_option' => $paymentOption,
            'fullname' => $data['fullname'],
            'service' => 'Lounge Service',
            'email' => $data['email'],
            'phone_no' => $data['phone'],
            'nop' => $data['adults'] + $data['children'] + $data['infants'],
            'noa' => $data['adults'],
            'noc' => $data['children'],
            'noi' => $data['infants'],
            'travel_date' => $data['travel_date'],
            'ticket_no' => $data['ticket_no'] ?? '',
            'd_time' => $data['d_time'],
            'amount' => $quote['subtotal'],
            'amountA' => $quote['adult_amount'],
            'amountC' => $quote['child_amount'],
            'vat' => $quote['vat'],
            'status' => 'Pending',
            'trans_id' => $reference,
            'ref_id' => $reference,
        ]);
    }

    /** Marks a booking paid and sends its confirmations, once however many times it's called. */
    public function completePayment(LoungeBooking $booking, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($booking, $paymentOption): bool {
            $locked = LoungeBooking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($locked->status === 'Successful') {
                return false;
            }

            $locked->update(array_filter(['status' => 'Successful', 'payment_option' => $paymentOption]));

            return true;
        });

        $booking->refresh();

        if ($justPaid) {
            $this->sendConfirmations($booking, [
                'infantAmount' => (float) $booking->amount - (float) $booking->amountA - (float) $booking->amountC,
                'p_amount' => $booking->totalAmount(),
            ]);
        }
    }

    /** @param  array<string, mixed>  $form  the checkout form, for the fields the booking doesn't store */
    public function sendConfirmations(LoungeBooking $booking, array $form = []): void
    {
        // Reservations get the full booking form; sent first so a failing customer email can't stop it
        try {
            Mail::to(config('travelwheel.reservations_email'))->send(new LoungeReservationNotificationMail($booking, $form));
        } catch (\Throwable $e) {
            Log::error('Lounge reservation notification failed', ['reference' => $booking->trans_id, 'error' => $e->getMessage()]);
        }

        try {
            Mail::to($booking->email)->send(new LoungeBookingMail($booking->fullname, $booking->trans_id, $booking->provider === 'loungepair'));
        } catch (\Throwable $e) {
            Log::error('Lounge booking confirmation failed', ['reference' => $booking->trans_id, 'error' => $e->getMessage()]);
        }
    }
}

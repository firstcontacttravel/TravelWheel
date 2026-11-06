<?php

namespace App\Services;

use App\Mail\ProtocolBookingMail;
use App\Models\Protocol;
use App\Models\ProtocolBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pricing and completing Protocol bookings, whichever way they arrive: the
 * public booking pages or staff booking from the admin. The price is always
 * worked out here from the protocol, never taken from the request.
 */
class ProtocolBookingService
{
    public const VAT_PERCENT = 7.5;

    public const MAX_PASSENGERS = 20;

    /** The airport as the booking form names it => as protocols store it. */
    public const AIRPORTS = ['International Airport' => 'International', 'Local Airport' => 'Local'];

    public function find(?string $location, ?string $airport, ?string $service): ?Protocol
    {
        if (blank($location) || blank($service) || ! isset(self::AIRPORTS[$airport])) {
            return null;
        }

        return Protocol::where('location', $location)
            ->where('airport', self::AIRPORTS[$airport])
            ->where('service', $service)
            ->latest()
            ->first();
    }

    /**
     * The plans a customer can choose here, as the website offers them: Regular
     * only for international departures, and never a plan with no price.
     *
     * @return array<string, array{label: string, price: float}>
     */
    public function plans(Protocol $protocol): array
    {
        $plans = ['1' => ['label' => 'VIP', 'price' => $protocol->price1]];

        if ($protocol->airport === 'International' && $protocol->service === 'Departure') {
            $plans = ['2' => ['label' => 'Regular', 'price' => $protocol->price2]] + $plans;
        }

        return array_filter($plans, fn (array $plan) => $plan['price'] > 0);
    }

    /**
     * Nothing to pay (all zeros) when the plan isn't offered here.
     *
     * @return array{unit: float, subtotal: float, vat: float, total: float}
     */
    public function quote(Protocol $protocol, string $plan, int $passengers): array
    {
        $unit = $this->plans($protocol)[$plan]['price'] ?? 0.0;
        $passengers = $passengers >= 1 && $passengers <= self::MAX_PASSENGERS ? $passengers : 0;
        $subtotal = round($unit * $passengers, 2);
        $vat = round($subtotal * self::VAT_PERCENT / 100, 2);

        return ['unit' => $unit, 'subtotal' => $subtotal, 'vat' => $vat, 'total' => $subtotal + $vat];
    }

    /** A booking made before payment, which staff do for a customer. */
    public function createPending(Protocol $protocol, array $data, string $paymentOption): ProtocolBooking
    {
        $passengers = $data['passengers'];
        $quote = $this->quote($protocol, $data['plan'], count($passengers));
        $reference = strtoupper('PROT'.bin2hex(random_bytes(6)));

        return ProtocolBooking::create([
            'paymentoption' => $paymentOption,
            'fullname' => array_map(fn (array $p) => trim($p['last'].' '.$p['first']), $passengers),
            'package' => $data['plan'],
            'service' => 'Protocol Service',
            'passenger' => count($passengers),
            'email' => $data['email'],
            'phone' => $data['phone'],
            'travel_date' => $data['travel_date'],
            'state' => $protocol->location,
            'airline' => $data['airline'],
            'airport' => array_search($protocol->airport, self::AIRPORTS, true) ?: $protocol->airport,
            'd_time' => $data['d_time'],
            'service_type' => $protocol->service,
            'status' => 'Pending',
            'amount' => $quote['subtotal'],
            'vat' => $quote['vat'],
            'optional_request' => $data['optional_request'] ?? 'None',
            'optionalRequestOption' => $data['optional_request_option'] ?? null,
            'optionalRequestAddress' => $data['optional_request_address'] ?? null,
            'reservationCode' => array_column($passengers, 'pnr'),
            'eTicketNo' => array_column($passengers, 'ticket'),
            'noOfBags' => array_map('strval', array_column($passengers, 'bags')),
            'nextOfKin_fullname' => $data['next_of_kin_name'],
            'nextOfKin_phone' => $data['next_of_kin_phone'],
            'means_id' => $data['means_id'],
            'trans_id' => $reference,
            'ref_id' => $reference,
        ]);
    }

    /** Marks a booking paid and sends its confirmation, once however many times it's called. */
    public function completePayment(ProtocolBooking $booking, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($booking, $paymentOption): bool {
            $locked = ProtocolBooking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($locked->status === 'Successful') {
                return false;
            }

            $locked->update(array_filter(['status' => 'Successful', 'paymentoption' => $paymentOption]));

            return true;
        });

        $booking->refresh();

        if ($justPaid) {
            $this->sendConfirmation($booking);
        }
    }

    public function sendConfirmation(ProtocolBooking $booking): void
    {
        try {
            Mail::to($booking->email)->send(new ProtocolBookingMail((string) ($booking->fullname[0] ?? ''), (float) $booking->amount, $booking->trans_id));
        } catch (\Throwable $e) {
            Log::error('Protocol booking confirmation failed', ['reference' => $booking->trans_id, 'error' => $e->getMessage()]);
        }
    }
}

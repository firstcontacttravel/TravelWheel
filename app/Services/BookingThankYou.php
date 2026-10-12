<?php

namespace App\Services;

use App\Mail\BookingThankYouMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a customer one thank-you email per product booked, suggesting our
 * other products. A booking can send more than one confirmation (a flight
 * sends a receipt and an e-ticket), so a customer gets at most one
 * thank-you per product a day.
 */
class BookingThankYou
{
    private const ONCE_PER_HOURS = 24;

    public function send(?string $email, ?string $name, string $product): void
    {
        $email = strtolower(trim((string) $email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! array_key_exists($product, config('travelwheel.products', []))
            || strcasecmp($email, (string) config('travelwheel.reservations_email')) === 0) {
            return;
        }

        $key = 'booking-thank-you:'.$product.':'.sha1($email);
        if (! Cache::add($key, true, now()->addHours(self::ONCE_PER_HOURS))) {
            return;
        }

        // The booking is already confirmed; a mail problem here must never undo it
        try {
            Mail::to($email, $name ?: null)->send(new BookingThankYouMail($name, $product));
        } catch (\Throwable $e) {
            Cache::forget($key);
            Log::error('Booking thank-you email failed', ['product' => $product, 'error' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Services\BookingThankYou;
use Illuminate\Mail\Events\MessageSent;

/**
 * Once a customer's booking confirmation has gone out (any product listed
 * in config('travelwheel.thank_you_triggers')), sends them a thank-you from
 * the reservations inbox. Hooks the send itself, so every code path that
 * confirms a booking is covered without changing each product.
 */
class SendBookingThankYou
{
    public function __construct(private BookingThankYou $thankYou) {}

    public function handle(MessageSent $event): void
    {
        $mailable = $event->data['__laravel_mailable'] ?? null;
        $trigger = config('travelwheel.thank_you_triggers', [])[$mailable] ?? null;

        if ($trigger === null) {
            return;
        }

        [$product, $subjectPrefix] = is_array($trigger) ? $trigger : [$trigger, null];

        $message = $event->sent->getOriginalMessage();
        if ($subjectPrefix !== null && ! str_starts_with((string) $message->getSubject(), $subjectPrefix)) {
            return;
        }

        // The customer is the "To" address; the reservations copy is a BCC on the same email
        foreach ($message->getTo() as $address) {
            $this->thankYou->send($address->getAddress(), $address->getName(), $product);
        }
    }
}

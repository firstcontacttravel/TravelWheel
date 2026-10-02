<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

/**
 * BCCs the reservations inbox on every booking email listed in
 * config('travelwheel.reservation_mailables'), whichever product or code
 * path sends it, so the reservations team sees every booking in one place.
 */
class CopyReservationEmails
{
    public function handle(MessageSending $event): void
    {
        $inbox = config('travelwheel.reservations_email');
        $mailable = $event->data['__laravel_mailable'] ?? null;
        $mailables = config('travelwheel.reservation_mailables', []);

        if (blank($inbox) || ! $mailable || ! array_key_exists($mailable, $mailables)) {
            return;
        }

        $subjectPrefix = $mailables[$mailable];
        if ($subjectPrefix !== null && ! str_starts_with((string) $event->message->getSubject(), $subjectPrefix)) {
            return;
        }

        $alreadyIncluded = collect([
            ...$event->message->getTo(),
            ...$event->message->getCc(),
            ...$event->message->getBcc(),
        ])->contains(fn (Address $address): bool => strcasecmp($address->getAddress(), $inbox) === 0);

        if (! $alreadyIncluded) {
            $event->message->addBcc($inbox);
        }
    }
}

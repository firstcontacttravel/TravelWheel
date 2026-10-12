<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent from the reservations inbox after a customer's booking confirmation:
 * thanks them, and suggests the products they didn't just book. Sent by
 * App\Services\BookingThankYou.
 */
class BookingThankYouMail extends Mailable
{
    public function __construct(public ?string $customerName, public string $product) {}

    public function envelope(): Envelope
    {
        $reservations = config('travelwheel.reservations_email');

        return new Envelope(
            from: new Address($reservations, 'TravelWheel Reservations'),
            replyTo: [new Address($reservations, 'TravelWheel Reservations')],
            subject: 'Thank you for booking with TravelWheel',
        );
    }

    public function content(): Content
    {
        $products = config('travelwheel.products', []);

        $suggestions = collect($products)
            ->except($this->product)
            ->map(fn (array $item) => [
                'name' => $item['name'],
                'pitch' => $item['pitch'],
                'url' => isset($item['route']) ? route($item['route']) : url($item['url'] ?? '/'),
                'icon' => isset($item['icon']) ? asset($item['icon']) : null,
            ])
            ->values()
            ->all();

        return new Content(view: 'emails.booking-thank-you', with: [
            'name' => $this->firstName(),
            'bookedProduct' => $products[$this->product]['name'] ?? 'your booking',
            'suggestions' => $suggestions,
        ]);
    }

    /** "Ada" from "Ada Obi"; null when we don't know the name. */
    private function firstName(): ?string
    {
        $name = trim((string) $this->customerName);

        return $name === '' ? null : strtok($name, ' ');
    }
}

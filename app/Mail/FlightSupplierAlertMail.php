<?php

namespace App\Mail;

use App\Filament\Resources\FlightSuppliers\FlightSupplierResource;
use App\Models\FlightSupplierEvent;
use App\Services\Flights\FlightSupplierRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal: a flight API was paused, or resumed, by the automatic cut-off.
 */
class FlightSupplierAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FlightSupplierEvent $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->paused()
                ? 'ALERT: '.$this->label().' paused automatically — searches failing'
                : $this->label().' resumed — searches working again',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.flight-supplier-alert',
            with: [
                'event' => $this->event,
                'label' => $this->label(),
                'paused' => $this->paused(),
                'adminUrl' => FlightSupplierResource::getUrl('index', panel: 'admin'),
            ],
        );
    }

    private function paused(): bool
    {
        return $this->event->action === 'auto_paused';
    }

    private function label(): string
    {
        $registry = app(FlightSupplierRegistry::class);
        $key = $this->event->supplier_key;

        return $registry->has($key) ? $registry->get($key)->label() : $key;
    }
}

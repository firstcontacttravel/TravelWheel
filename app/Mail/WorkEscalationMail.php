<?php

namespace App\Mail;

use App\Models\Escalation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal: a booking was escalated to you or your department, or an
 * escalation you raised was resolved or declined.
 *
 * Carries the booking reference and the staff-written reason only. No
 * passenger, passport or payment details; those stay behind the admin login.
 */
class WorkEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Escalation $escalation, public string $event) {}

    public function envelope(): Envelope
    {
        $reference = $this->reference();

        return new Envelope(
            subject: match ($this->event) {
                'resolved' => "Resolved: {$reference}",
                'declined' => "Declined: {$reference}",
                default => ($this->escalation->mode === Escalation::MODE_HANDOFF ? 'Hand-off' : 'Help needed')
                    .($this->escalation->priority === 'urgent' ? ' (urgent)' : '')
                    .": {$reference}",
            },
        );
    }

    public function content(): Content
    {
        $item = $this->escalation->workItem;

        return new Content(
            view: 'emails.work-escalation',
            with: [
                'escalation' => $this->escalation,
                'event' => $this->event,
                'reference' => $this->reference(),
                'service' => $item->workflow()->label(),
                'stage' => $item->stageLabel(),
                'adminUrl' => $item->workflow()->url($item->subject),
            ],
        );
    }

    private function reference(): string
    {
        $item = $this->escalation->workItem;

        return $item->workflow()->reference($item->subject);
    }
}

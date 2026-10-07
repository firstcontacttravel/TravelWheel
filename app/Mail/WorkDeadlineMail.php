<?php

namespace App\Mail;

use App\Models\WorkItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal: a booking missed its deadline ("missed", to its owner and
 * department), or is still overdue well after ("ceo", to the CEO).
 *
 * Booking reference and step only; customer details stay behind the login.
 */
class WorkDeadlineMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WorkItem $item, public string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->event === 'ceo' ? 'Still overdue: ' : 'Overdue: ').$this->reference().' · '.$this->item->stageLabel(),
        );
    }

    public function content(): Content
    {
        $workflow = $this->item->workflow();

        return new Content(
            view: 'emails.work-deadline',
            with: [
                'item' => $this->item,
                'event' => $this->event,
                'reference' => $this->reference(),
                'service' => $workflow->label(),
                'stage' => $this->item->stageLabel(),
                'owner' => $this->item->owner?->name,
                'queue' => $this->item->department?->name,
                'due' => $this->item->due_at?->copy()->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i'),
                'adminUrl' => $workflow->url($this->item->subject),
            ],
        );
    }

    private function reference(): string
    {
        return $this->item->workflow()->reference($this->item->subject);
    }
}

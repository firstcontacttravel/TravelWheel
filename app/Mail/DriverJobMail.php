<?php

namespace App\Mail;

use App\Models\DriverAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the driver when admin assigns them to a Car Hire or Transfer
 * booking: who to pick up, where, when, and which car to bring.
 */
class DriverJobMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DriverAssignment $assignment)
    {
    }

    public function build()
    {
        $booking = $this->assignment->booking();
        $isTransfer = $this->assignment->booking_type === 'transfer';

        return $this->subject('New Trip Assigned — '.($booking?->payment_reference ?? 'TravelWheel'))
            ->view('emails.driver-job')
            ->with([
                'assignment' => $this->assignment,
                'driver' => $this->assignment->driver,
                'booking' => $booking,
                'isTransfer' => $isTransfer,
            ]);
    }
}

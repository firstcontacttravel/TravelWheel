<?php

namespace App\Mail;

use App\Models\DriverAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DriverAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DriverAssignment $assignment;

    /**
     * Create a new message instance.
     */
    public function __construct(DriverAssignment $assignment)
    {
        $this->assignment = $assignment;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $booking = $this->assignment->booking();

        $subject = $booking
            ? 'Your Driver Has Been Assigned — ' . ($booking->payment_reference ?? 'TravelWheel')
            : 'Your Driver Has Been Assigned — TravelWheel';

        return $this->subject($subject)
            ->view('emails.driver-assigned')
            ->with([
                'assignment' => $this->assignment,
                'driver'     => $this->assignment->driver,
                'booking'    => $booking,
            ]);
    }
}
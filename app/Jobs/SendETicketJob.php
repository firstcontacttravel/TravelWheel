<?php

namespace App\Jobs;

use App\Mail\ETicketMail;
use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendETicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public readonly FlightBooking $booking,
        public readonly array   $tripDetails = [],
    ) {}

    public function handle(): void
    {
        $email = $this->booking->contact_email ?: null;

        if (!$email) {
            Log::warning('[ETicket] No contact email for booking', [
                'ref' => $this->booking->booking_ref,
            ]);
            return;
        }

        Mail::to($email)->send(
            new ETicketMail($this->booking, $this->tripDetails)
        );

        Log::info('[ETicket] E-ticket sent', [
            'ref'   => $this->booking->booking_ref,
            'email' => $email,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[ETicket] Failed to send e-ticket', [
            'ref'   => $this->booking->booking_ref,
            'error' => $e->getMessage(),
        ]);
    }
}

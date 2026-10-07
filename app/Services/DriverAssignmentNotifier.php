<?php

namespace App\Services;

use App\Mail\DriverAssignedMail;
use App\Mail\DriverJobMail;
use App\Models\DriverAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails everyone when admin assigns a driver to a Car Hire or Transfer
 * booking: the customer (driver + car details; reservation@ gets a BCC of
 * it via CopyReservationEmails) and the driver (the job details).
 */
class DriverAssignmentNotifier
{
    /**
     * @return array{sent: list<string>, problems: list<string>}
     */
    public function notify(DriverAssignment $assignment): array
    {
        $booking = $assignment->booking();
        $driver = $assignment->driver;
        $sent = [];
        $problems = [];

        if (filled($booking?->email)) {
            try {
                Mail::to($booking->email)->send(new DriverAssignedMail($assignment));
                $assignment->update(['email_sent_at' => now()]);
                $sent[] = 'customer ('.$booking->email.')';
            } catch (Throwable $e) {
                Log::error('Driver assigned: customer email failed', ['reference' => $booking->payment_reference, 'error' => $e->getMessage()]);
                $problems[] = 'customer email failed: '.$e->getMessage();
            }
        } else {
            $problems[] = 'the booking has no customer email';
        }

        if (filled($driver?->email)) {
            try {
                Mail::to($driver->email, $driver->name)->send(new DriverJobMail($assignment));
                $sent[] = 'driver ('.$driver->email.')';
            } catch (Throwable $e) {
                Log::error('Driver assigned: driver email failed', ['reference' => $booking?->payment_reference, 'error' => $e->getMessage()]);
                $problems[] = 'driver email failed: '.$e->getMessage();
            }
        } else {
            $problems[] = ($driver?->name ?? 'The driver').' has no email address — add one under Drivers, or call them on '.($driver?->phone ?? 'their phone');
        }

        return ['sent' => $sent, 'problems' => $problems];
    }
}

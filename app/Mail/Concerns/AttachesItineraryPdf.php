<?php

namespace App\Mail\Concerns;

use App\Models\FlightBooking;
use App\Services\ItineraryPdfService;
use Illuminate\Mail\Mailables\Attachment;

trait AttachesItineraryPdf
{
    protected function itineraryAttachment(
        FlightBooking $booking,
        array $tripDetails = [],
        string $state = 'auto',
        string $audience = 'customer',
    ): Attachment {
        $reference = $booking->booking_ref ?: $booking->unique_id ?: 'booking';
        $prefix = $state === 'ticketed' ? 'eticket-itinerary-' : 'booking-itinerary-';

        return Attachment::fromData(
            fn () => app(ItineraryPdfService::class)->generate($booking, $tripDetails, $state, $audience),
            $prefix . $reference . '.pdf',
        )->withMime('application/pdf');
    }
}

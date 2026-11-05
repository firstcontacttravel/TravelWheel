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
        $service = app(ItineraryPdfService::class);

        return Attachment::fromData(
            fn () => $service->generate($booking, $tripDetails, $state, $audience),
            $service->filename($booking, $tripDetails, $state),
        )->withMime('application/pdf');
    }
}

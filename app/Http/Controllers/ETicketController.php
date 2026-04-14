<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\ETicketPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ETicketController extends Controller
{
    public function __construct(private ETicketPdfService $pdfService) {}

    /**
     * Force-download the PDF.
     * GET /bookings/{bookingRef}/eticket/download
     */
    public function download(string $bookingRef): Response
    {
        $booking     = Booking::where('booking_ref', $bookingRef)->firstOrFail();
        $tripDetails = $this->fetchTripDetails($booking);
        $pdf         = $this->pdfService->generate($booking, $tripDetails);
        $filename    = 'eticket-' . $booking->booking_ref . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Stream/preview the PDF inline in the browser.
     * GET /bookings/{bookingRef}/eticket/view
     */
    public function view(string $bookingRef): Response
    {
        $booking     = Booking::where('booking_ref', $bookingRef)->firstOrFail();
        $tripDetails = $this->fetchTripDetails($booking);
        $pdf         = $this->pdfService->generate($booking, $tripDetails);
        $filename    = 'eticket-' . $booking->booking_ref . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Trigger a re-send of the e-ticket email.
     * POST /bookings/{bookingRef}/eticket/resend
     */
    public function resend(string $bookingRef): \Illuminate\Http\JsonResponse
    {
        $booking     = Booking::where('booking_ref', $bookingRef)->firstOrFail();
        $tripDetails = $this->fetchTripDetails($booking);

        \App\Jobs\SendETicketJob::dispatch($booking, $tripDetails);

        return response()->json([
            'message' => 'E-ticket queued for delivery.',
            'ref'     => $bookingRef,
        ]);
    }

    // ── Private ────────────────────────────────────────────────────────────

    /**
     * Pull live trip details from the API (or cache them from the booking record).
     * Swap this out for your actual API call.
     */
    private function fetchTripDetails(Booking $booking): array
    {
        // If you persist tripDetails on the booking, return them directly:
        if (!empty($booking->trip_details)) {
            return $booking->trip_details;
        }

        // Otherwise call your API service, e.g.:
        // return app(FlightApiService::class)->getTripDetails($booking->api_ref);

        return [];
    }
}

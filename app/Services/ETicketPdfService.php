<?php

namespace App\Services;

use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ETicketPdfService
{
    /**
     * Generate the e-ticket PDF for a booking and return the raw PDF bytes.
     *
    * @param  FlightBooking  $booking    Eloquent model (must have relations loaded)
     * @param  array    $tripDetails  Live data from the trip_details API call
     * @return string   Raw PDF binary string
     */
    public function generate(FlightBooking $booking, array $tripDetails = [], string $view = 'pdf.eticket'): string
    {
        Log::info('[ETicketPdfService] generate start', [
            'booking_id'       => $booking->id,
            'booking_ref'      => $booking->booking_ref,
            'trip_details_key' => array_keys($tripDetails),
            'view'             => $view,
        ]);

        $data = $this->buildViewData($booking, $tripDetails);

        Log::info('[ETicketPdfService] view data built', [
            'booking_ref'    => $booking->booking_ref,
            'passengers'     => count($data['passengers'] ?? []),
            'outbound_count' => count($data['outboundSegments'] ?? []),
            'return_count'   => count($data['returnSegments'] ?? []),
            'multi_legs'     => count($data['multiLegs'] ?? []),
        ]);

        

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true, // important if using logos/images
                'dpi' => 150,
                'enable_php' => false, // security
                'enable_javascript' => false, // usually not needed
            ]);

        $bytes = $pdf->output();

        Log::info('[ETicketPdfService] generate complete', [
            'booking_ref' => $booking->booking_ref,
            'size_bytes'  => strlen($bytes),
        ]);

        return $bytes;
    }

    /**
     * Generate the PDF and store it to disk, returning the storage path.
     *
    * @param  FlightBooking  $booking
     * @param  array    $tripDetails
     * @param  string   $disk   Storage disk name (default: 'local')
     * @return string   Storage path, e.g. "etickets/TW-2025-84721.pdf"
     */
    public function store(FlightBooking $booking, array $tripDetails = [], string $disk = 'local'): string
    {
        $bytes = $this->generate($booking, $tripDetails);
        $path  = 'etickets/' . $booking->booking_ref . '.pdf';

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    /**
     * Build the view-data array that the Blade template expects.
     */
    public function buildViewData(FlightBooking $booking, array $tripDetails): array
    {
        // ── Session / booking fields ──────────────────────────────────────
        $mf          = $booking->flight_snapshot ?? [];
        $passengers  = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
        $contact     = [
            'email' => $booking->contact_email,
            'phone' => $booking->contact_phone,
        ];
        $breakdown   = $mf['fareBreakdown'] ?? $mf['fare_breakdown'] ?? [];
        $extraServicesSnapshot = $booking->extra_services_snapshot ?? [];
        $extrasTotal = (float) ($extraServicesSnapshot['total_amount'] ?? 0);
        $currency    = $mf['currency'] ?? 'NGN';
        $sym         = match ($currency) {
            'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency . ' '
        };

        // ── Trip details (live from API) ──────────────────────────────────
        $ticketStatus  = strtoupper($tripDetails['TicketStatus']  ?? '');
        $bookingStatus = strtoupper($tripDetails['BookingStatus'] ?? 'CONFIRMED');
        $isTicketed    = $ticketStatus === 'TICKETED';

        // Build e-ticket map from CustomerInfos
        $customerInfos = collect(
            data_get($tripDetails, 'ItineraryInfo.CustomerInfos', [])
        )->map(fn($c) => $c['CustomerInfo'] ?? $c);

        // Enrich passenger records with eticket numbers
        $passengers = collect($passengers)->map(function ($pax, $i) use ($customerInfos) {
            $info = $customerInfos->get($i, []);
            return array_merge($pax, [
                'eticket' => $info['eTicketNumber'] ?? null,
            ]);
        })->all();

        // ── Segments ──────────────────────────────────────────────────────
        $outboundSegments = $mf['segments']        ?? [];
        $returnSegments   = $mf['returnSegments']  ?? [];
        $multiLegs        = $mf['multiLegs']       ?? [];

        $isReturn = count($returnSegments) > 0;
        $isMulti  = count($multiLegs) > 0;
        $tripLabel = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-City' : 'One Way');
        $ticketPNR = $tripDetails['ItineraryInfo']['ReservationItems'][0]['ReservationItem']['AirlinePNR'];  


        return [
            // Meta
            'bookingRef'       => $booking->booking_ref,
            'uniqueId'         => $booking->unique_id ?? '',
            'tripLabel'        => $tripLabel,
            'airline'          => $mf['airline'] ?? '',
            'cabin'            => \App\Support\FlightDisplay::cabin($mf, $booking),
            'isTicketed'       => $isTicketed,
            'bookingStatus'    => $bookingStatus,
            'ticketStatus'     => $ticketStatus,
            'ticketPNR'        => $ticketPNR ?? 'PNRQWX',

            // Segments
            'outboundSegments' => $outboundSegments,
            'returnSegments'   => $returnSegments,
            'multiLegs'        => $multiLegs,

            // People
            'passengers'       => $passengers,

            // Contact
            'contactEmail'     => $contact['email'] ?? $booking->contact_email ?? '',
            'contactPhone'     => $contact['phone'] ?? $booking->contact_phone ?? '',

            // Fare
            'fareBreakdown'    => $breakdown,
            'extraServicesSnapshot' => $extraServicesSnapshot,
            'extrasTotal'      => $extrasTotal,
            'totalAmount'      => (float) ($booking->total_price ?? (($mf['price'] ?? 0) + $extrasTotal)),
            'currencySymbol'   => $sym,
            'paymentMethod'    => $booking->payment_method ?? 'gateway',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ETicketPdfService
{
    public function generate(FlightBooking $booking, array $tripDetails = [], string $view = 'pdf.eticket'): string
    {
        Log::info('[ETicketPdfService] generate start', [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'trip_details_key' => array_keys($tripDetails),
            'view' => $view,
        ]);

        $data = $this->buildViewData($booking, $tripDetails);

        Log::info('[ETicketPdfService] view data built', [
            'booking_ref' => $booking->booking_ref,
            'passengers' => count($data['passengers'] ?? []),
            'outbound_count' => count($data['outboundSegments'] ?? []),
            'return_count' => count($data['returnSegments'] ?? []),
            'multi_legs' => count($data['multiLegs'] ?? []),
        ]);

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_php' => false,
                'enable_javascript' => false,
            ]);

        $bytes = $pdf->output();

        Log::info('[ETicketPdfService] generate complete', [
            'booking_ref' => $booking->booking_ref,
            'size_bytes' => strlen($bytes),
        ]);

        return $bytes;
    }

    public function store(FlightBooking $booking, array $tripDetails = [], string $disk = 'local'): string
    {
        $bytes = $this->generate($booking, $tripDetails);
        $path = 'etickets/' . $booking->booking_ref . '.pdf';

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    public function buildViewData(FlightBooking $booking, array $tripDetails): array
    {
        $flight = $booking->flight_snapshot ?? [];
        $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
        $breakdown = $flight['fareBreakdown'] ?? $flight['fare_breakdown'] ?? [];
        $extraServicesSnapshot = $booking->extra_services_snapshot ?? [];
        $extrasTotal = (float) ($extraServicesSnapshot['total_amount'] ?? 0);
        $currency = $flight['currency'] ?? $booking->currency ?? 'NGN';
        $currencySymbol = match ($currency) {
            'NGN' => 'NGN ',
            'USD' => '$',
            'GBP' => 'GBP ',
            'EUR' => 'EUR ',
            default => $currency . ' ',
        };

        $ticketStatus = strtoupper($tripDetails['TicketStatus'] ?? '');
        $bookingStatus = strtoupper($tripDetails['BookingStatus'] ?? 'CONFIRMED');
        $isTicketed = $ticketStatus === 'TICKETED'
            || $booking->isTicketed()
            || $booking->ticket_ordered;

        $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->map(fn ($customer) => $customer['CustomerInfo'] ?? $customer);

        $passengers = collect($passengers)->map(function (array $passenger, int $index) use ($customerInfos): array {
            $info = $customerInfos->get($index, []);

            return array_merge($passenger, [
                'eticket' => $this->firstFilled($info, [
                    'eTicketNumber',
                    'ETicketNumber',
                    'eTicket',
                    'ETicket',
                    'TicketNumber',
                    'ticketNumber',
                ]) ?: $this->firstFilled($passenger, [
                    'eticket',
                    'eTicket',
                    'eTicketNumber',
                    'ETicket',
                    'TicketNumber',
                    'ticketNumber',
                ]),
            ]);
        })->all();

        $outboundSegments = $flight['segments'] ?? [];
        $returnSegments = $flight['returnSegments'] ?? [];
        $multiLegs = $flight['multiLegs'] ?? [];
        $tripLabel = count($returnSegments) > 0
            ? 'Round Trip'
            : (count($multiLegs) > 0 ? 'Multi-City' : 'One Way');

        $ticketPNR = data_get($tripDetails, 'ItineraryInfo.ReservationItems.0.ReservationItem.AirlinePNR')
            ?? data_get($tripDetails, 'ItineraryInfo.ReservationItems.ReservationItem.AirlinePNR')
            ?? data_get($tripDetails, 'ItineraryInfo.AirlinePNR')
            ?? data_get($tripDetails, 'AirlinePNR')
            ?? '';

        return [
            'bookingRef' => $booking->booking_ref,
            'uniqueId' => $booking->unique_id ?? '',
            'tripLabel' => $tripLabel,
            'airline' => $flight['airline'] ?? $booking->airline ?? '',
            'cabin' => \App\Support\FlightDisplay::cabin($flight, $booking),
            'isTicketed' => $isTicketed,
            'bookingStatus' => $bookingStatus,
            'ticketStatus' => $ticketStatus,
            'ticketPNR' => $ticketPNR,
            'outboundSegments' => $outboundSegments,
            'returnSegments' => $returnSegments,
            'multiLegs' => $multiLegs,
            'passengers' => $passengers,
            'contactEmail' => $booking->contact_email ?? '',
            'contactPhone' => $booking->contact_phone ?? '',
            'fareBreakdown' => $breakdown,
            'extraServicesSnapshot' => $extraServicesSnapshot,
            'extrasTotal' => $extrasTotal,
            'totalAmount' => (float) ($booking->total_price ?? (($flight['price'] ?? 0) + $extrasTotal)),
            'currencySymbol' => $currencySymbol,
            'paymentMethod' => $booking->payment_method ?? 'gateway',
        ];
    }

    private function firstFilled(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }
}

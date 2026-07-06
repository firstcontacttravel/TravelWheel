<?php

namespace App\Services;

use App\Mail\ETicketMail;
use App\Mail\UnTicketedConfirmationAlert;
use App\Models\FlightBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminTicketingService
{
    public function ticketOrder(FlightBooking $booking): array
    {
        $payload = $this->travelNextPayload($booking->unique_id);

        try {
            $response = Http::timeout(60)
                ->post('https://travelnext.works/api/aeroVE5/ticket_order', $payload);
        } catch (\Throwable $exception) {
            Log::error('Admin ticket order request failed', [
                'booking_id' => $booking->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Ticketing provider is temporarily unavailable.',
                'request' => $this->redactPayload($payload),
                'response' => [],
            ];
        }

        $data = $response->json() ?: [];

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'Ticket order request failed.',
                'request' => $this->redactPayload($payload),
                'response' => $data,
            ];
        }

        $result = data_get($data, 'AirOrderTicketRS.TicketOrderResult', []);
        $ok = filter_var(data_get($result, 'Success'), FILTER_VALIDATE_BOOLEAN);

        return [
            'ok' => $ok,
            'message' => $ok ? 'Ticket order completed.' : $this->extractApiErrorMessage($result, 'Ticket order failed.'),
            'request' => $this->redactPayload($payload),
            'response' => $data,
            'unique_id' => data_get($result, 'UniqueID', $booking->unique_id),
        ];
    }

    public function tripDetails(FlightBooking $booking): array
    {
        $payload = $this->travelNextPayload($booking->unique_id);

        try {
            $response = Http::timeout(30)
                ->post('https://travelnext.works/api/aeroVE5/trip_details', $payload);
        } catch (\Throwable $exception) {
            Log::error('Admin trip details request failed', [
                'booking_id' => $booking->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Trip details provider is temporarily unavailable.',
                'request' => $this->redactPayload($payload),
                'response' => [],
            ];
        }

        $data = $response->json() ?: [];

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'Trip details request failed.',
                'request' => $this->redactPayload($payload),
                'response' => $data,
            ];
        }

        $result = data_get($data, 'TripDetailsResponse.TripDetailsResult', []);
        $ok = filter_var(data_get($result, 'Success'), FILTER_VALIDATE_BOOLEAN);
        $tripData = data_get($result, 'TravelItinerary', []);

        if ($ok && is_array($tripData) && $tripData !== []) {
            $booking->update(['itinerary_snapshot' => $tripData]);
        }

        return [
            'ok' => $ok,
            'message' => $ok ? 'Trip details fetched.' : $this->extractApiErrorMessage($result, 'Trip details failed.'),
            'request' => $this->redactPayload($payload),
            'response' => $data,
            'trip_details' => is_array($tripData) ? $tripData : [],
            'ticket_status' => data_get($tripData, 'TicketStatus'),
            'booking_status' => data_get($tripData, 'BookingStatus'),
            'airline_pnr' => $this->extractAirlinePnr(is_array($tripData) ? $tripData : []),
        ];
    }

    public function sendETicket(FlightBooking $booking, array $tripDetails): void
    {
        Mail::to($booking->contact_email)->send(new ETicketMail($booking, $tripDetails));
    }

    public function sendFailureAlert(FlightBooking $booking, string $message, array $ticketResponse = []): void
    {
        $flight = $booking->flight_snapshot ?? [];
        $segments = $flight['segments'] ?? [];
        $firstSegment = $segments[0] ?? [];
        $lastSegment = ! empty($segments) ? end($segments) : [];

        Mail::to(config('mail.support_address', config('mail.from.address')))
            ->send(new UnTicketedConfirmationAlert([
                'uniqueId' => $booking->unique_id,
                'bookingStatus' => 'PAID_UNTICKETED',
                'ticketStatus' => 'TICKETING_FAILED',
                'origin' => $firstSegment['from'] ?? '',
                'destination' => $lastSegment['to'] ?? '',
                'fareType' => $booking->fare_type ?? '',
                'passengers' => $booking->passengers_snapshot ?? [],
                'flights' => [],
                'pricing' => [
                    'booking_ref' => $booking->booking_ref,
                    'amount_paid' => $booking->payment_charged_amount ?? $booking->payment_amount,
                    'currency' => $booking->payment_currency,
                    'payment_reference' => $booking->payment_reference,
                    'ticket_error' => $message,
                    'ticket_response' => $ticketResponse,
                ],
                'timestamp' => now(),
            ]));
    }

    public function extractAirlinePnr(array $tripDetails): ?string
    {
        $reservationItems = data_get($tripDetails, 'ItineraryInfo.ReservationItems', []);

        foreach ($reservationItems as $item) {
            $pnr = data_get($item, 'ReservationItem.AirlinePNR') ?? data_get($item, 'AirlinePNR');

            if (filled($pnr)) {
                return (string) $pnr;
            }
        }

        return null;
    }

    private function travelNextPayload(?string $uniqueId): array
    {
        return [
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),
            'UniqueID' => $uniqueId,
        ];
    }

    private function redactPayload(array $payload): array
    {
        $payload['user_password'] = '[redacted]';

        return $payload;
    }

    private function extractApiErrorMessage(array $payload, string $fallback): string
    {
        foreach ([
            'Errors.0.Errors.ErrorMessage',
            'Errors.Errors.ErrorMessage',
            'Errors.Error.ErrorMessage',
            'Errors.ErrorMessage',
            'Error.ErrorMessage',
            'ErrorMessage',
            'AirOrderTicketRS.TicketOrderResult.Errors.Error.ErrorMessage',
            'AirOrderTicketRS.TicketOrderResult.Errors.ErrorMessage',
        ] as $path) {
            $message = data_get($payload, $path);

            if (is_scalar($message) && trim((string) $message) !== '') {
                return trim((string) $message);
            }
        }

        return $this->findFirstApiErrorMessage($payload) ?? $fallback;
    }

    private function findFirstApiErrorMessage(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        foreach ($value as $key => $child) {
            if ($key === 'ErrorMessage' && is_scalar($child) && trim((string) $child) !== '') {
                return trim((string) $child);
            }

            $message = $this->findFirstApiErrorMessage($child);

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }
}

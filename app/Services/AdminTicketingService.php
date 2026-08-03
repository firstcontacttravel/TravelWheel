<?php

namespace App\Services;

use App\Models\FlightBooking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminTicketingService
{
    public function ticketOrder(FlightBooking $booking): array
    {
        $lock = Cache::lock('flight-ticketing:'.$booking->id, 300);

        if (! $lock->get()) {
            return [
                'ok' => false,
                'busy' => true,
                'message' => 'Ticketing is already in progress for this booking.',
                'request' => [],
                'response' => [],
            ];
        }

        try {
            $booking = $booking->fresh();

            if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
                return [
                    'ok' => true,
                    'already_ticketed' => true,
                    'message' => 'This booking is already ticketed.',
                    'request' => [],
                    'response' => $booking->ticket_api_response ?? [],
                    'unique_id' => $booking->unique_id,
                ];
            }

            if ($booking->tkt_time_limit && $booking->tkt_time_limit->isPast()) {
                $booking->update([
                    'booking_status' => 'hold_expired_review',
                    'reconciliation_note' => 'Ticketing stopped because the airline hold expired.',
                    'last_reconciled_at' => now(),
                ]);

                return [
                    'ok' => false,
                    'hold_expired' => true,
                    'message' => 'The airline hold expired before ticketing could begin.',
                    'request' => [],
                    'response' => [],
                ];
            }

            $booking->update([
                'booking_status' => 'ticketing_in_progress',
                'ticketing_started_at' => now(),
            ]);

            $result = $this->performTicketOrder($booking);
            $booking->update([
                'booking_status' => $result['ok'] ? 'ticketed' : 'ticketing_failed',
                'ticket_ordered' => (bool) $result['ok'],
                'ticket_ordered_at' => $result['ok'] ? now() : null,
                'ticketing_started_at' => null,
                'ticket_api_response' => $result['response'] ?? [],
                'unique_id' => $result['unique_id'] ?? $booking->unique_id,
            ]);

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function performTicketOrder(FlightBooking $booking): array
    {
        $payload = $this->travelNextPayload($booking->unique_id);

        try {
            $response = Http::connectTimeout(10)->timeout(60)
                ->post(config('services.travelnext.base_url').'ticket_order', $payload);
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
            $response = Http::connectTimeout(10)->timeout(30)
                ->post(config('services.travelnext.base_url').'trip_details', $payload);
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
        $sent = app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::FLIGHT_ETICKET,
            (string) $booking->contact_email,
            $booking,
            ['trip_details' => $tripDetails],
            'flight-eticket:'.$booking->id,
        );

        if (! $sent) {
            throw new RuntimeException('The e-ticket was saved for automatic retry.');
        }
    }

    public function sendFailureAlert(FlightBooking $booking, string $message, array $ticketResponse = []): void
    {
        $sent = app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::TICKETING_ALERT,
            (string) config('mail.support_address', config('mail.from.address')),
            $booking,
            ['message' => $message],
            'ticketing-alert:'.$booking->id.':'.sha1($message),
        );

        if (! $sent) {
            throw new RuntimeException('The support alert was saved for automatic retry.');
        }
    }

    public function failureAlertData(FlightBooking $booking, string $message): array
    {
        $flight = $booking->flight_snapshot ?? [];
        $segments = $flight['segments'] ?? [];
        $firstSegment = $segments[0] ?? [];
        $lastSegment = ! empty($segments) ? end($segments) : [];

        return [
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
            ],
            'timestamp' => now(),
        ];
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
        foreach (['user_id', 'user_password', 'access', 'ip_address'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

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

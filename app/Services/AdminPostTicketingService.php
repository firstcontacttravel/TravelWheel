<?php

namespace App\Services;

use App\Models\FlightBooking;
use Illuminate\Support\Facades\Http;

class AdminPostTicketingService
{
    private const ENDPOINTS = [
        'cancel' => 'cancel',
        'void_quote' => 'void_ticket_quote',
        'void' => 'void_ticket',
        'refund_quote' => 'refund_quote',
        'refund' => 'refund',
        'reissue_quote' => 'reissue_ticket_quote',
        'reissue' => 'reissue_ticket',
        'ptr_status' => 'search_post_ticket_status',
    ];

    public function call(FlightBooking $booking, string $operationType, array $extraPayload = []): array
    {
        $tripDetails = app(AdminTicketingService::class)->tripDetails($booking);

        if (! ($tripDetails['ok'] ?? false)) {
            return [
                'ok' => false,
                'status' => 'failed',
                'message' => $tripDetails['message'] ?? 'Trip details preflight failed.',
                'request' => [],
                'response' => [],
                'preflight_trip_details' => $tripDetails['response'] ?? [],
            ];
        }

        if (isset($extraPayload['paxDetails']) && is_array($extraPayload['paxDetails'])) {
            $extraPayload['paxDetails'] = $this->normalizePaxDetails(
                $extraPayload['paxDetails'],
                $tripDetails['trip_details'] ?? [],
            );
        }

        $payload = array_merge($this->basePayload($booking), $extraPayload);
        $apiPayload = collect($payload)
            ->reject(fn ($value, string $key): bool => str_starts_with($key, '_'))
            ->all();
        $endpoint = self::ENDPOINTS[$operationType] ?? null;

        if (! $endpoint) {
            return [
                'ok' => false,
                'status' => 'failed',
                'message' => 'Unsupported post-ticketing operation.',
                'request' => $this->redactPayload($payload),
                'response' => [],
                'preflight_trip_details' => $tripDetails['response'] ?? [],
            ];
        }

        $response = Http::timeout(60)
            ->post('https://travelnext.works/api/aeroVE5/' . $endpoint, $apiPayload);

        $data = $response->json() ?: [];
        $ok = ! $response->failed() && $this->extractSuccess($data);
        $ptrUniqueId = $this->extractFirst($data, ['ptrUniqueID', 'PtrUniqueID']);

        return [
            'ok' => $ok,
            'status' => $ok ? $this->normalizeStatus($this->extractFirst($data, ['Status', 'PtrStatus']) ?: ($operationType === 'cancel' ? 'completed' : 'submitted')) : 'failed',
            'message' => $ok ? ($operationType === 'cancel' ? 'Booking cancelled.' : 'Post-ticketing request completed.') : $this->extractErrorMessage($data, 'Post-ticketing request failed.'),
            'ptr_unique_id' => $ptrUniqueId === null ? null : (string) $ptrUniqueId,
            'request' => $this->redactPayload($payload),
            'response' => $data,
            'preflight_trip_details' => $tripDetails['response'] ?? [],
        ];
    }

    private function basePayload(FlightBooking $booking): array
    {
        return [
            'user_id' => config('services.travelnext.user_id'),
            'user_password' => config('services.travelnext.password'),
            'access' => config('services.travelnext.access'),
            'ip_address' => config('services.travelnext.ip'),
            'UniqueID' => $booking->unique_id,
        ];
    }

    private function redactPayload(array $payload): array
    {
        $payload['user_password'] = '[redacted]';

        return $payload;
    }

    private function normalizePaxDetails(array $passengers, array $tripDetails): array
    {
        if ($passengers === []) {
            return [];
        }

        if (array_keys($passengers) !== range(0, count($passengers) - 1)) {
            $passengers = [$passengers];
        }

        $ticketNumbers = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
            ->filter(fn ($customer): bool => is_array($customer))
            ->map(fn (array $customer): array => $customer['CustomerInfo'] ?? $customer)
            ->pluck('eTicketNumber')
            ->filter()
            ->values();

        return collect($passengers)
            ->filter(fn ($passenger): bool => is_array($passenger))
            ->map(function (array $passenger, int $index) use ($ticketNumbers): array {
                return [
                    'type' => $this->firstFilled($passenger, ['type', 'PassengerType', 'passengerType'], 'ADT'),
                    'title' => $this->firstFilled($passenger, ['title', 'Title', 'PassengerTitle']),
                    'firstName' => $this->firstFilled($passenger, ['firstName', 'first_name', 'FirstName', 'PassengerFirstName']),
                    'lastName' => $this->firstFilled($passenger, ['lastName', 'last_name', 'LastName', 'PassengerLastName']),
                    'eTicket' => $this->firstFilled($passenger, ['eTicket', 'eticket', 'eTicketNumber', 'ETicket', 'TicketNumber'])
                        ?: $ticketNumbers->get($index),
                ];
            })
            ->values()
            ->all();
    }

    private function firstFilled(array $payload, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (filled($payload[$key] ?? null)) {
                return $payload[$key];
            }
        }

        return $default;
    }

    private function extractSuccess(array $payload): bool
    {
        foreach ($this->flatten($payload) as $key => $value) {
            if (strtolower((string) str($key)->afterLast('.')) === 'success') {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return false;
    }

    private function extractFirst(array $payload, array $keys): mixed
    {
        foreach ($this->flatten($payload) as $key => $value) {
            $last = (string) str($key)->afterLast('.');

            if (in_array($last, $keys, true) && filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function extractErrorMessage(array $payload, string $fallback): string
    {
        foreach ($this->flatten($payload) as $key => $value) {
            if (strtolower((string) str($key)->afterLast('.')) === 'errormessage' && is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $fallback;
    }

    private function normalizeStatus(string $status): string
    {
        return str($status)->lower()->replace([' ', '-'], '_')->toString();
    }

    private function flatten(array $payload, string $prefix = ''): array
    {
        $flat = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $path);
                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }
}

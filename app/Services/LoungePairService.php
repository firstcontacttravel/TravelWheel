<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LoungePairService
{
    private const TOKEN_CACHE_KEY = 'loungepair.access_token';

    /**
     * Exchange client credentials for a bearer token and cache it just short
     * of its one-hour lifetime. The secret is never persisted in the database.
     */
    public function accessToken(): string
    {
        $clientId = (string) config('services.loungepair.client_id');
        $clientSecret = (string) config('services.loungepair.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('LoungePair credentials are not configured. Set LOUNGEPAIR_CLIENT_ID and LOUNGEPAIR_CLIENT_SECRET.');
        }

        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(55), function () use ($clientId, $clientSecret): string {
            $response = $this->client()->post('/api/v1/auth/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                // LoungePair rejects 'lounges:read' as an invalid scope for this
                // tenant — 'airports:read' alone is sufficient, since lounge data
                // is returned as part of the airport payload (GET /api/v1/at/{IATA}).
                'scope' => 'airports:read',
            ]);

            try {
                $response->throw();
            } catch (RequestException $exception) {
                throw new RuntimeException('LoungePair authentication failed: '.$exception->getMessage(), previous: $exception);
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('LoungePair authentication response did not include an access_token.');
            }

            return $token;
        });
    }

    /**
     * Fetch an airport and its LoungePair lounges using GET /api/v1/at/{IATA}.
     * The response's airport context is added to every lounge for local sync.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loungesForAirport(string $iata): array
    {
        $iata = strtoupper(trim($iata));

        if (! preg_match('/^[A-Z]{3}$/', $iata)) {
            throw new RuntimeException('A valid three-letter IATA airport code is required.');
        }

        $query = [];
        $currency = strtoupper(trim((string) config('services.loungepair.currency')));
        if ($currency !== '') {
            $query['currency'] = $currency;
        }

        $path = rtrim((string) config('services.loungepair.airport_path'), '/').'/'.$iata;
        $payload = $this->getPayload($path, $query);
        $airport = is_array($payload['airport'] ?? null) ? $payload['airport'] : ['iata' => $iata];
        $lounges = is_array($payload['lounges'] ?? null) ? $payload['lounges'] : [];

        return collect($lounges)
            ->filter(fn ($lounge) => is_array($lounge))
            ->map(function (array $lounge) use ($airport, $iata): array {
                $lounge['airport'] = $lounge['airport'] ?? $airport;
                $lounge['airport_iata'] = $lounge['airport_iata'] ?? $iata;

                return $lounge;
            })
            ->values()
            ->all();
    }

    /** @param array<string, scalar|null> $query
     *  @return array<int, array<string, mixed>>
     */
    private function getPayload(string $path, array $query = []): array
    {
        $response = $this->authorizedClient()->get($path, array_filter($query, fn ($value) => $value !== null && $value !== ''));

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('LoungePair catalogue request failed: '.$exception->getMessage(), previous: $exception);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('LoungePair catalogue response was not valid JSON.');
        }

        return $payload;
    }

    private function authorizedClient(): PendingRequest
    {
        return $this->client()->withToken($this->accessToken());
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.loungepair.base_url'), '/'))
            ->acceptJson()
            ->timeout(30)
            ->connectTimeout(10);
    }
}

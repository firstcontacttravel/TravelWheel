<?php

namespace Tests\Feature;

use App\Services\LoungePairService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoungePairServiceTest extends TestCase
{
    public function test_it_authenticates_once_and_requests_the_lounge_catalogue_with_a_bearer_token(): void
    {
        config()->set('cache.default', 'array');
        config()->set('services.loungepair.base_url', 'https://loungepair.test');
        config()->set('services.loungepair.client_id', 'client-id');
        config()->set('services.loungepair.client_secret', 'client-secret');
        Cache::forget('loungepair.access_token');

        Http::fake([
            'https://loungepair.test/api/v1/auth/token' => Http::response(['access_token' => 'test-token']),
            'https://loungepair.test/api/v1/at/SYD*' => Http::response([
                'airport' => ['iata' => 'SYD', 'city' => 'Sydney'],
                'lounges' => [['slug' => 'example-lounge', 'name' => 'Example Lounge', 'fromPrice' => 55]],
            ]),
        ]);

        $lounges = app(LoungePairService::class)->loungesForAirport('syd');

        $this->assertSame('example-lounge', $lounges[0]['slug']);
        $this->assertSame('SYD', $lounges[0]['airport_iata']);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://loungepair.test/api/v1/auth/token'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['scope'] === 'airports:read';
        });
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer test-token')
            && str_contains($request->url(), '/api/v1/at/SYD'));
    }
}

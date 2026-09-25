<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SkylinkAuthService
{
    private const TOKEN_CACHE_KEY = 'skylink.access_token';

    /**
     * Exchange the SkyLink service account credentials for a JWT access
     * token and cache it just short of its 15-minute lifetime. The password
     * is never persisted anywhere but the .env-sourced config.
     */
    public function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(13), function (): string {
            return $this->login();
        });
    }

    /**
     * Force a fresh login, bypassing the cache. Used to recover from a 401
     * mid-flow (expired/invalidated token) without waiting for the cache TTL.
     */
    public function refreshToken(): string
    {
        Cache::forget(self::TOKEN_CACHE_KEY);

        return $this->accessToken();
    }

    private function login(): string
    {
        $email = (string) config('services.skylink.email');
        $password = (string) config('services.skylink.password');

        if ($email === '' || $password === '') {
            throw new RuntimeException('SkyLink credentials are not configured. Set SKYLINK_EMAIL and SKYLINK_PASSWORD.');
        }

        $response = $this->client()->post('login', [
            'email' => $email,
            'password' => $password,
        ]);

        // SkyLink's /api/login always answers HTTP 200, even for invalid
        // credentials — failure is only visible in the body's "status"/"code"
        // fields, so $response->throw() can never catch a login failure here.
        $decoded = self::decodeJson($response);
        $token = data_get($decoded, 'data.access_token');

        if (! is_string($token) || $token === '') {
            $message = data_get($decoded, 'message')
                ?: data_get($decoded, 'code')
                ?: 'unexpected response (HTTP '.$response->status().')';

            throw new RuntimeException('SkyLink authentication failed: '.$message);
        }

        return $token;
    }

    /**
     * SkyLink prefixes every response body with a UTF-8 BOM, which makes
     * PHP's json_decode() — and therefore Response::json() — silently
     * return null even for a perfectly valid body. Strip it before decoding.
     */
    public static function decodeJson(Response $response): array
    {
        $decoded = json_decode(ltrim($response->body(), "\xEF\xBB\xBF"), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * True when a usable token is already cached, so callers can tell a warm
     * request (one round trip) from a cold one (login, then the real call)
     * without forcing a login themselves.
     */
    public function hasCachedToken(): bool
    {
        return Cache::has(self::TOKEN_CACHE_KEY);
    }

    public function authorizedClient(?int $timeout = null): PendingRequest
    {
        return $this->client($timeout)->withToken($this->accessToken());
    }

    /**
     * $timeout is the read timeout in seconds. It is per-endpoint rather than
     * one global value because search, pricing and reserve have very different
     * costs when cut short — see config/services.php's skylink block. The
     * default is the short auth timeout, since the only caller that doesn't
     * pass one explicitly is login() itself.
     */
    public function client(?int $timeout = null): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.skylink.base_url'), '/'))
            ->acceptJson()
            ->timeout($timeout ?? (int) config('services.skylink.auth_timeout', 8))
            ->connectTimeout(5);
    }
}

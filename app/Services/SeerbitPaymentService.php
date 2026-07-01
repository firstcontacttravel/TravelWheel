<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SeerbitPaymentService
{
    private string $baseUrl;

    private ?string $publicKey;

    private ?string $secretKey;

    private string $country;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.seerbit.base_url', 'https://seerbitapi.com'), '/');
        $this->publicKey = config('services.seerbit.public_key');
        $this->secretKey = config('services.seerbit.secret_key');
        $this->country = (string) config('services.seerbit.country', 'NG');
    }

    public function initializePayment(array $payload): array
    {
        $response = Http::timeout(30)
            ->withToken($this->encryptedKey())
            ->acceptJson()
            ->post($this->baseUrl.'/api/v2/payments', array_merge([
                'publicKey' => $this->publicKey,
                'country' => $this->country,
            ], $payload));

        $data = $response->json() ?: [];

        if ($response->failed()) {
            Log::error('SeerBit initialize payment failed', [
                'status' => $response->status(),
            ]);
            throw new RuntimeException('Unable to initialize payment. Please try again.');
        }

        $redirectLink = data_get($data, 'data.payments.redirectLink')
            ?? data_get($data, 'data.redirectLink')
            ?? data_get($data, 'payments.redirectLink');

        if (! $redirectLink) {
            Log::error('SeerBit initialize payment returned no redirect link');
            throw new RuntimeException('Payment gateway did not return a checkout link.');
        }

        return [
            'raw' => $data,
            'redirect_link' => $redirectLink,
        ];
    }

    public function verifyPayment(string $paymentReference): array
    {
        $response = Http::timeout(30)
            ->withToken($this->encryptedKey())
            ->acceptJson()
            ->get($this->baseUrl.'/api/v3/payments/query/'.urlencode($paymentReference));

        $data = $response->json() ?: [];

        if ($response->failed()) {
            Log::error('SeerBit verify payment failed', [
                'payment_reference' => $paymentReference,
                'status' => $response->status(),
            ]);

            return ['ok' => false, 'raw' => $data, 'message' => 'Payment verification failed.'];
        }

        $gatewayCode = (string) (
            data_get($data, 'data.payments.gatewayCode')
            ?? data_get($data, 'data.gatewayCode')
            ?? data_get($data, 'gatewayCode')
            ?? ''
        );
        $gatewayMessage = (string) (
            data_get($data, 'data.payments.gatewayMessage')
            ?? data_get($data, 'data.gatewayMessage')
            ?? data_get($data, 'gatewayMessage')
            ?? ''
        );

        $amount = data_get($data, 'data.payments.amount')
            ?? data_get($data, 'data.amount')
            ?? data_get($data, 'amount');
        $currency = data_get($data, 'data.payments.currency')
            ?? data_get($data, 'data.currency')
            ?? data_get($data, 'currency');
        $fee = data_get($data, 'data.payments.fee')
            ?? data_get($data, 'data.customers.fee')
            ?? data_get($data, 'data.fee')
            ?? data_get($data, 'fee');

        return [
            'ok' => $gatewayCode === '00' || strcasecmp($gatewayMessage, 'Successful') === 0,
            'gateway_code' => $gatewayCode,
            'gateway_message' => $gatewayMessage,
            'amount' => $amount === null ? null : (float) str_replace(',', '', (string) $amount),
            'fee' => $fee === null ? null : (float) str_replace(',', '', (string) $fee),
            'currency' => $currency,
            'raw' => $data,
            'message' => $gatewayMessage ?: 'Payment verification completed.',
        ];
    }

    private function encryptedKey(): string
    {
        if (! $this->publicKey || ! $this->secretKey) {
            throw new RuntimeException('SeerBit keys are not configured.');
        }

        $response = Http::timeout(20)
            ->acceptJson()
            ->post($this->baseUrl.'/api/v2/encrypt/keys', [
                'key' => $this->secretKey.'.'.$this->publicKey,
            ]);

        $data = $response->json() ?: [];
        $encryptedKey = data_get($data, 'data.EncryptedSecKey.encryptedKey')
            ?? data_get($data, 'data.EncryptedSecKey')
            ?? data_get($data, 'data.encryptedKey')
            ?? data_get($data, 'EncryptedSecKey.encryptedKey')
            ?? data_get($data, 'EncryptedSecKey')
            ?? data_get($data, 'encryptedKey');

        if ($response->failed() || ! $encryptedKey) {
            Log::error('SeerBit key encryption failed', [
                'status' => $response->status(),
            ]);
            throw new RuntimeException('Unable to authenticate with payment gateway.');
        }

        return (string) $encryptedKey;
    }
}

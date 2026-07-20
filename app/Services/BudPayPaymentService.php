<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BudPayPaymentService
{
    private ?string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.budpay.secret_key');
    }

    public function publicKey(): ?string
    {
        return config('services.budpay.public_key');
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::timeout(30)
            ->withToken($this->secretKey)
            ->get("https://api.budpay.com/api/v2/transaction/verify/{$reference}");

        if ($response->failed()) {
            Log::error('BudPay verify transaction failed', ['reference' => $reference]);

            return ['ok' => false, 'message' => 'Payment gateway error. Please contact support.'];
        }

        $data = $response->json() ?: [];
        $status = data_get($data, 'data.transaction_status') ?? data_get($data, 'data.status');

        return [
            'ok' => in_array($status, ['success', 'completed'], true),
            'status' => $status,
            'raw' => $data,
        ];
    }
}

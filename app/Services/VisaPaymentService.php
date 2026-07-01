<?php

namespace App\Services;

use App\Models\VisaPayment;
use App\Models\VisaPaymentEvent;
use App\Models\VisaQuote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisaPaymentService
{
    public function __construct(private readonly SeerbitPaymentService $seerbit, private readonly VisaCommunicationService $communications, private readonly VisaFunnelService $funnel) {}

    public function initialize(VisaQuote $quote): VisaPayment
    {
        $quote->load('application.travelers');
        if ($quote->status !== 'active' || $quote->expires_at->isPast()) {
            if ($quote->status === 'active') {
                $quote->update(['status' => 'expired']);
            }
            throw ValidationException::withMessages(['payment' => 'This quote has expired. Generate a new quote before paying.']);
        }

        $idempotency = hash('sha256', 'seerbit|'.$quote->id.'|'.$quote->pricing_fingerprint);
        $payment = VisaPayment::query()->firstOrCreate(['idempotency_key' => $idempotency], [
            'reference' => 'VWV-'.Str::upper((string) Str::ulid()), 'visa_application_id' => $quote->visa_application_id,
            'visa_quote_id' => $quote->id, 'provider' => 'seerbit', 'status' => 'initialized',
            'expected_amount' => $quote->payable_total, 'expected_currency' => $quote->checkout_currency,
        ]);
        if ($payment->status === 'paid' || ($payment->checkout_url && in_array($payment->status, ['initialized', 'pending'], true))) {
            return $payment;
        }

        $lead = $quote->application->travelers->first();
        $checkout = $this->seerbit->initializePayment([
            'amount' => number_format((float) $payment->expected_amount, 2, '.', ''), 'currency' => $payment->expected_currency,
            'paymentReference' => $payment->reference, 'email' => $quote->application->contact_email,
            'fullName' => trim(($lead?->first_name ?? '').' '.($lead?->last_name ?? '')) ?: 'TravelWheel Customer',
            'mobileNumber' => $lead?->phone, 'callbackUrl' => route('visa.payments.callback', ['paymentReference' => $payment->reference]),
            'productDescription' => 'Visa application '.$quote->application->reference,
            'productId' => 'VISA-'.$quote->application->reference,
        ]);
        $payment->update(['status' => 'pending', 'checkout_url' => $checkout['redirect_link'], 'initialization_response' => $checkout['raw'], 'initiated_at' => now()]);

        return $payment->fresh();
    }

    public function verify(string $reference, array $payload, string $eventType): VisaPayment
    {
        $payment = VisaPayment::query()->where('reference', $reference)->firstOrFail();
        $eventHash = hash('sha256', 'seerbit|'.$eventType.'|'.$reference.'|'.json_encode($payload));
        VisaPaymentEvent::query()->firstOrCreate(['event_hash' => $eventHash], ['visa_payment_id' => $payment->id, 'provider' => 'seerbit', 'event_type' => $eventType, 'payload' => $payload]);
        if ($payment->status === 'paid') {
            return $payment;
        }

        $verification = $this->seerbit->verifyPayment($reference);
        $verifiedAmount = $verification['amount'] === null ? null : round((float) $verification['amount'], 2);
        $verifiedFee = $verification['fee'] === null ? null : round((float) $verification['fee'], 2);
        $expectedAmount = round((float) $payment->expected_amount, 2);
        $verifiedCurrency = strtoupper((string) ($verification['currency'] ?? ''));
        $amountMatches = $verifiedAmount !== null && (
            abs($verifiedAmount - $expectedAmount) < 0.01
            || ($verifiedFee !== null && $verifiedFee >= 0 && abs(($verifiedAmount - $verifiedFee) - $expectedAmount) < 0.01)
        );
        $valid = $verification['ok'] && $amountMatches && $verifiedCurrency === strtoupper($payment->expected_currency);

        $payment = DB::transaction(function () use ($payment, $verification, $verifiedAmount, $verifiedCurrency, $valid, $eventHash): VisaPayment {
            $payment = VisaPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'paid') {
                return $payment;
            }
            $event = VisaPaymentEvent::query()->where('event_hash', $eventHash)->first();
            if (! $valid) {
                $payment->update(['status' => 'failed', 'verified_amount' => $verifiedAmount, 'verified_currency' => $verifiedCurrency ?: null, 'verification_response' => $verification['raw'] ?? [], 'failure_code' => $verification['gateway_code'] ?? 'verification_mismatch', 'failure_message' => 'Payment status, amount, or currency did not match the quote.', 'failed_at' => now()]);
                $event?->update(['processing_status' => 'rejected', 'processing_message' => $payment->failure_message, 'processed_at' => now()]);

                return $payment->fresh();
            }
            $payment->update(['status' => 'paid', 'verified_amount' => $verifiedAmount, 'verified_currency' => $verifiedCurrency, 'verification_response' => $verification['raw'] ?? [], 'verified_at' => now(), 'failure_code' => null, 'failure_message' => null]);
            $payment->quote()->update(['status' => 'consumed', 'accepted_at' => now()]);
            $application = $payment->application;
            if ($application->status !== 'submitted') {
                $from = $application->status;
                $application->update(['status' => 'submitted']);
                $application->statusHistory()->create(['from_status' => $from, 'to_status' => 'submitted', 'actor_type' => 'system', 'reason' => 'SeerBit payment verified', 'metadata' => ['payment_reference' => $payment->reference]]);
            }
            $event?->update(['processing_status' => 'processed', 'processing_message' => 'Payment verified.', 'processed_at' => now()]);

            return $payment->fresh();
        });

        if ($payment->status === 'paid') {
            $this->funnel->record('payment_verified', ['payment_reference' => $payment->reference, 'amount' => $payment->verified_amount, 'currency' => $payment->verified_currency], $payment->application, 'payment_verified|'.$payment->id);
            $this->funnel->record('application_submitted', [], $payment->application, 'application_submitted|'.$payment->application->id);
            $this->communications->sendOnce(
                $payment->application,
                'payment_confirmed',
                'Visa payment confirmed — '.$payment->application->reference,
                'Payment confirmed',
                'Your payment has been verified and your visa application is now submitted. You can track progress from the secure customer portal.'
            );
        }

        return $payment;
    }
}

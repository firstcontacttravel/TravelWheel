<?php

namespace App\Services;

use App\Models\InsuranceQuote;

/**
 * Pricing travel insurance. The price is Sanlam Allianz's quote, kept when the
 * quote was made, plus VAT: never an amount the browser sent.
 */
class InsuranceBookingService
{
    public const VAT_PERCENT = 7.5;

    /** The quote a booking form is for, by the quote request id it carries. */
    public function quote(?string $quoteRequestId): ?InsuranceQuote
    {
        if (blank($quoteRequestId)) {
            return null;
        }

        $quote = InsuranceQuote::where('quoteRequestId', $quoteRequestId)->latest('id')->first();

        return $quote && (float) $quote->amount > 0 ? $quote : null;
    }

    /** @return array{amount: float, vat: float, total: float} */
    public function price(InsuranceQuote $quote): array
    {
        $amount = round((float) $quote->amount, 2);
        $vat = round($amount * self::VAT_PERCENT / 100, 2);

        return ['amount' => $amount, 'vat' => $vat, 'total' => round($amount + $vat, 2)];
    }
}

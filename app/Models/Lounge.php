<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lounge extends Model
{
    protected $table = 'lounges';

    protected $fillable = [
        'lounge_id',
        'provider',
        'provider_lounge_id',
        'provider_airport_iata',
        'brand_name',
        'email',
        'phone_no',
        'location',
        'airport',
        'service',
        'terminal',
        'description',
        'facilities1',
        'facilities2',
        'facilities3',
        'facilities4',
        'facilities5',
        'given_PriceA',
        'given_PriceB',
        'given_PriceC',
        'markup_price',
        'provider_currency',
        'provider_url',
        'provider_images',
        'provider_payload',
        'provider_synced_at',
        'pics1',
        'pics2',
        'pics3',
        'pics4',
        'pics5',
    ];

    /**
     * priceA/B/C are not stored — they're the public-facing total, always
     * derived from the vendor price (given_Price*) plus the shared markup.
     */
    protected $appends = [
        'priceA',
        'priceB',
        'priceC',
    ];

    protected function casts(): array
    {
        return [
            'provider_images' => 'array',
            'provider_payload' => 'array',
            'provider_synced_at' => 'datetime',
        ];
    }

    /** Return a LoungePair image URL when supplied, otherwise the local asset. */
    public function imageUrl(int $position = 0): string
    {
        $remoteImage = $this->provider_images[$position] ?? null;

        if (is_string($remoteImage) && $remoteImage !== '') {
            return $remoteImage;
        }

        $localImage = $this->{'pics'.($position + 1)} ?? '';

        return str_starts_with($localImage, 'http') ? $localImage : asset('assets/lounge/'.$localImage);
    }

    /**
     * Convert a LoungePair price (given_Price* is stored in provider_currency,
     * not NGN) using the project's own exchange rate — the same table flight
     * pricing reads from. $field is one of given_PriceA/B/C. Returns null when
     * there's nothing to convert; we never charge our own markup on the
     * conversion itself since it just mirrors LoungePair's own price.
     */
    public function priceInNgn(string $field = 'given_PriceA'): ?float
    {
        $amount = (float) ($this->{$field} ?? 0);

        if ($this->provider !== 'loungepair' || $amount <= 0) {
            return null;
        }

        $currency = $this->provider_currency ?: 'USD';

        return round($amount * ExchangeRate::rateFor($currency), 2);
    }

    /**
     * The amount actually charged for a booking, in NGN, regardless of
     * whether this is a locally managed lounge (priceA/B/C, vendor price +
     * markup) or a LoungePair lounge (converted from provider_currency, no
     * markup — that booking happens on LoungePair's site, we just record it).
     */
    public function bookingPrice(string $tier = 'A'): float
    {
        $tier = strtoupper($tier);

        return $this->priceInNgn('given_Price'.$tier) ?? (float) $this->{'price'.$tier};
    }

    public function getPriceAAttribute(): float
    {
        return self::totalPrice($this->given_PriceA, $this->markup_price);
    }

    public function getPriceBAttribute(): float
    {
        return self::totalPrice($this->given_PriceB, $this->markup_price);
    }

    public function getPriceCAttribute(): float
    {
        return self::totalPrice($this->given_PriceC, $this->markup_price);
    }

    /**
     * A tier with no vendor price (e.g. infants often ride free) stays ₦0 —
     * markup only applies once the vendor is actually charging for that tier.
     */
    public static function totalPrice(mixed $vendorPrice, mixed $markupPrice): float
    {
        $vendorPrice = (float) $vendorPrice;

        if ($vendorPrice <= 0) {
            return 0.0;
        }

        return round($vendorPrice + ((float) $markupPrice), 2);
    }
}

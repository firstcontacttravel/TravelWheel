<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lounge extends Model
{
    protected $table = 'lounges';

    protected $fillable = [
        'lounge_id',
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

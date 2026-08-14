<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    protected $table = 'protocols';

    protected $fillable = [
        'location',
        'airport',
        'service',
        'Given_Price1',
        'Given_Price2',
        'markup_price',
    ];

    /**
     * price1/price2 are not stored — they're the public-facing total, always
     * derived from the vendor price (Given_Price*) plus the shared markup.
     */
    protected $appends = [
        'price1',
        'price2',
    ];

    public function getPrice1Attribute(): float
    {
        return self::totalPrice($this->Given_Price1, $this->markup_price);
    }

    public function getPrice2Attribute(): float
    {
        return self::totalPrice($this->Given_Price2, $this->markup_price);
    }

    /**
     * A plan with no vendor price (e.g. Regular service not offered at that
     * location) stays ₦0 — markup only applies once the vendor is actually
     * charging for that plan.
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

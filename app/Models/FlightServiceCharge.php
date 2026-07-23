<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightServiceCharge extends Model
{
    protected $fillable = [
        'route_category',
        'cabin',
        'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public static function allKeyed(): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(fn (self $charge): array => [
                $charge->route_category.'.'.$charge->cabin => (float) $charge->amount,
            ])
            ->all();
    }
}

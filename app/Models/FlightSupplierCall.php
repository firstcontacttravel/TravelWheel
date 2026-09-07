<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlightSupplierCall extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'supplier',
        'call_type',
        'route',
        'cabin',
        'trip_type',
        'currency',
        'price',
        'passenger_counts',
        'response_time_ms',
        'success',
        'http_status',
        'error_message',
        'search_id',
        'created_at',
    ];

    protected $casts = [
        'passenger_counts' => 'array',
        'success' => 'boolean',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Fire-and-forget instrumentation write — a logging failure must never
     * break a search/pricing/reserve call, so every error is swallowed here.
     */
    public static function record(array $attributes): void
    {
        try {
            static::create(array_merge(['created_at' => now()], $attributes));
        } catch (Throwable $exception) {
            Log::warning('Failed to record flight supplier call', [
                'error' => $exception->getMessage(),
                'attributes' => $attributes,
            ]);
        }
    }
}

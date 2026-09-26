<?php

namespace App\Models;

use App\Services\Flights\FlightSupplierControl;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Whether one flight API is switched on, and where it sits in the search
 * order. Change these through FlightSupplierControl, which records who did
 * what in flight_supplier_events; saving a row directly still refreshes the
 * cached state, but leaves no history.
 */
class FlightSupplierSetting extends Model
{
    protected $fillable = [
        'key',
        'enabled',
        'priority',
        'disabled_reason',
        'disabled_by',
        'disabled_at',
        're_enable_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
        'disabled_at' => 'datetime',
        're_enable_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        $forget = fn () => FlightSupplierControl::forgetCachedState();

        static::saved($forget);
        static::deleted($forget);
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    /**
     * Switched on, or switched off with a turn-back-on time that has passed —
     * true from that moment, not from whenever the scheduled job next runs.
     */
    public function isAvailable(?CarbonInterface $at = null): bool
    {
        return $this->enabled
            || ($this->re_enable_at !== null && $this->re_enable_at->lte($at ?? now()));
    }
}

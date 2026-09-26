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
        'auto_cutoff',
        'breaker_state',
        'breaker_opened_at',
        'breaker_retry_at',
        'breaker_reason',
        'breaker_trial_successes',
        'cutoff_min_calls',
        'cutoff_failure_percent',
        'cutoff_window_minutes',
        'cutoff_pause_minutes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
        'disabled_at' => 'datetime',
        're_enable_at' => 'datetime',
        'auto_cutoff' => 'boolean',
        'breaker_opened_at' => 'datetime',
        'breaker_retry_at' => 'datetime',
        'breaker_trial_successes' => 'integer',
        'cutoff_min_calls' => 'integer',
        'cutoff_failure_percent' => 'integer',
        'cutoff_window_minutes' => 'integer',
        'cutoff_pause_minutes' => 'integer',
    ];

    public const BREAKER_CLOSED = 'closed';

    public const BREAKER_OPEN = 'open';

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

    /** Paused by the automatic cut-off and still inside the pause. */
    public function isAutoPaused(?CarbonInterface $at = null): bool
    {
        return $this->breaker_state === self::BREAKER_OPEN
            && $this->breaker_retry_at !== null
            && $this->breaker_retry_at->gt($at ?? now());
    }

    /** The pause has run out; calls are going through on trial. */
    public function isOnTrial(?CarbonInterface $at = null): bool
    {
        return $this->breaker_state === self::BREAKER_OPEN && ! $this->isAutoPaused($at);
    }

    /**
     * This API's cut-off thresholds, its own where set, else the defaults.
     *
     * @return array{min_calls:int, failure_percent:int, window_minutes:int, pause_minutes:int}
     */
    public function cutoffThresholds(): array
    {
        $defaults = (array) config('flights.cutoff', []);

        return [
            'min_calls' => max(1, (int) ($this->cutoff_min_calls ?? $defaults['min_calls'] ?? 10)),
            'failure_percent' => min(100, max(1, (int) ($this->cutoff_failure_percent ?? $defaults['failure_percent'] ?? 50))),
            'window_minutes' => max(1, (int) ($this->cutoff_window_minutes ?? $defaults['window_minutes'] ?? 5)),
            'pause_minutes' => max(1, (int) ($this->cutoff_pause_minutes ?? $defaults['pause_minutes'] ?? 10)),
        ];
    }
}

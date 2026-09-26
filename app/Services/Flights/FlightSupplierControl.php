<?php

namespace App\Services\Flights;

use App\Contracts\FlightSupplier;
use App\Models\FlightSupplierEvent;
use App\Models\FlightSupplierSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Which flight APIs customers can search, and in what order.
 *
 * The switches live in flight_supplier_settings and are changed by admins on
 * the Flight APIs screen. Every search reads them, so they are cached — and
 * the cache is dropped whenever a setting is saved, which makes a switch take
 * effect on the very next search.
 *
 * Only suppliers registered in config('flights.suppliers') are ever returned,
 * and a registered supplier with no settings row counts as switched off: a
 * newly added API stays dark until an admin turns it on.
 */
class FlightSupplierControl
{
    private const CACHE_KEY = 'flight-suppliers.state';

    private const CACHE_SECONDS = 300;

    public function __construct(private readonly FlightSupplierRegistry $registry) {}

    public static function forgetCachedState(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // =========================================================================
    //  Reading — used on every search
    // =========================================================================

    /**
     * Keys of the suppliers customers can use right now, first-tried first.
     *
     * @return list<string>
     */
    public function enabledKeys(): array
    {
        $now = now();

        return collect($this->state())
            ->filter(fn (array $row, string $key): bool => $this->registry->has($key)
                && ($row['enabled'] || ($row['re_enable_at'] !== null && Carbon::parse($row['re_enable_at'])->lte($now)))
                // Paused by the automatic cut-off, and the pause hasn't run out.
                && ! (($row['breaker_state'] ?? 'closed') === FlightSupplierSetting::BREAKER_OPEN
                    && $row['breaker_retry_at'] !== null
                    && Carbon::parse($row['breaker_retry_at'])->gt($now)))
            ->sortBy('priority')
            ->keys()
            ->values()
            ->all();
    }

    /** @return list<FlightSupplier> */
    public function enabled(): array
    {
        return array_map(fn (string $key): FlightSupplier => $this->registry->get($key), $this->enabledKeys());
    }

    public function isEnabled(string $key): bool
    {
        return in_array($key, $this->enabledKeys(), true);
    }

    /** Paused or on trial — the cut-off has something to watch. Cached. */
    public function breakerOpen(string $key): bool
    {
        return ($this->state()[$key]['breaker_state'] ?? FlightSupplierSetting::BREAKER_CLOSED) === FlightSupplierSetting::BREAKER_OPEN;
    }

    /**
     * key => [enabled, priority, re_enable_at], cached.
     *
     * If the settings can't be read at all — a deploy whose migration hasn't
     * run yet, a database blip — searches fall back to the first registered
     * supplier alone rather than failing outright. That is TravelNext, which
     * was the only supplier on before these switches existed; the fallback is
     * never cached, so the real settings take over as soon as they're readable.
     */
    private function state(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => FlightSupplierSetting::query()
                ->get(['key', 'enabled', 'priority', 're_enable_at', 'breaker_state', 'breaker_retry_at'])
                ->mapWithKeys(fn (FlightSupplierSetting $setting): array => [$setting->key => [
                    'enabled' => $setting->enabled,
                    'priority' => $setting->priority,
                    're_enable_at' => $setting->re_enable_at?->toIso8601String(),
                    'breaker_state' => $setting->breaker_state ?? FlightSupplierSetting::BREAKER_CLOSED,
                    'breaker_retry_at' => $setting->breaker_retry_at?->toIso8601String(),
                ]])
                ->all());
        } catch (Throwable $exception) {
            Log::error('Flight supplier settings unreadable — searching the first registered supplier only', [
                'error' => $exception->getMessage(),
            ]);

            $first = $this->registry->keys()[0] ?? null;

            return $first === null ? [] : [$first => [
                'enabled' => true,
                'priority' => 1,
                're_enable_at' => null,
                'breaker_state' => FlightSupplierSetting::BREAKER_CLOSED,
                'breaker_retry_at' => null,
            ]];
        }
    }

    // =========================================================================
    //  Admin — every change is recorded in flight_supplier_events
    // =========================================================================

    /**
     * Every registered supplier's settings row, in search order, creating
     * (switched off, last in line) any that a newly registered API lacks.
     *
     * @return Collection<int, FlightSupplierSetting>
     */
    public function settings(): Collection
    {
        $this->ensureRows();

        return FlightSupplierSetting::query()
            ->whereIn('key', $this->registry->keys())
            ->orderBy('priority')
            ->get();
    }

    public function ensureRows(): void
    {
        $existing = FlightSupplierSetting::query()->pluck('key')->all();

        foreach (array_diff($this->registry->keys(), $existing) as $key) {
            DB::transaction(function () use ($key): void {
                $priority = (int) FlightSupplierSetting::query()->max('priority') + 1;

                FlightSupplierSetting::query()->create([
                    'key' => $key,
                    'enabled' => false,
                    'priority' => $priority,
                ]);

                $this->record($key, 'registered', 'New API added. Switched off until an admin turns it on.', null, [
                    'priority' => $priority,
                ]);
            });
        }
    }

    /**
     * Any reason is accepted — a failing API, funding, maintenance, a contract
     * question. $reEnableAt, when given, switches it back on automatically.
     */
    public function disable(string $key, string $reason, ?User $by, ?CarbonInterface $reEnableAt = null): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required to switch a flight API off.');
        }

        if ($reEnableAt !== null && $reEnableAt->lte(now())) {
            throw new InvalidArgumentException('The switch-on time must be in the future.');
        }

        DB::transaction(function () use ($key, $reason, $by, $reEnableAt): void {
            $this->setting($key)->update([
                'enabled' => false,
                'disabled_reason' => $reason,
                'disabled_by' => $by?->id,
                'disabled_at' => now(),
                're_enable_at' => $reEnableAt,
            ]);

            $this->record($key, 'disabled', $reason, $by, [
                're_enable_at' => $reEnableAt?->toIso8601String(),
            ]);
        });
    }

    public function enable(string $key, ?User $by): void
    {
        DB::transaction(function () use ($key, $by): void {
            $setting = $this->setting($key);
            $wasOffFor = $setting->disabled_reason;

            $this->switchOn($setting);
            $this->record($key, 'enabled', null, $by, [
                'was_off_for' => $wasOffFor,
            ]);
        });
    }

    /**
     * Moves one place earlier ($direction -1) or later (+1) in the search
     * order. Priorities are renumbered 1..n every time, so gaps and ties left
     * by rows edited by hand can never make the order ambiguous.
     */
    public function move(string $key, int $direction, ?User $by): void
    {
        DB::transaction(function () use ($key, $direction, $by): void {
            $ordered = $this->settings()->values();
            $from = $ordered->search(fn (FlightSupplierSetting $setting): bool => $setting->key === $key);

            if ($from === false) {
                throw new InvalidArgumentException("Unknown flight supplier [{$key}].");
            }

            $to = $from + ($direction < 0 ? -1 : 1);

            if ($to < 0 || $to >= $ordered->count()) {
                return;
            }

            $keys = $ordered->pluck('key')->all();
            [$keys[$from], $keys[$to]] = [$keys[$to], $keys[$from]];

            foreach ($keys as $index => $orderedKey) {
                FlightSupplierSetting::query()->where('key', $orderedKey)->update(['priority' => $index + 1]);
            }

            // update() on the query builder skips model events.
            self::forgetCachedState();

            $this->record($key, 'moved', null, $by, [
                'from' => $from + 1,
                'to' => $to + 1,
                'order' => $keys,
            ]);
        });
    }

    /**
     * Ends an automatic pause now rather than waiting it out — for an admin
     * who knows the supplier has recovered.
     */
    public function resumeNow(string $key, ?User $by): void
    {
        DB::transaction(function () use ($key, $by): void {
            $setting = $this->setting($key);

            if ($setting->breaker_state !== FlightSupplierSetting::BREAKER_OPEN) {
                return;
            }

            $wasPausedFor = $setting->breaker_reason;
            $setting->update(self::BREAKER_CLEARED);

            $this->record($key, 'cutoff_cleared', null, $by, ['was_paused_for' => $wasPausedFor]);
        });
    }

    /**
     * $settings: auto_cutoff, and min_calls / failure_percent /
     * window_minutes / pause_minutes where null means "use the default".
     */
    public function updateCutoff(string $key, array $settings, ?User $by): void
    {
        $values = [
            'auto_cutoff' => (bool) ($settings['auto_cutoff'] ?? true),
            'cutoff_min_calls' => self::positiveOrNull($settings['min_calls'] ?? null),
            'cutoff_failure_percent' => self::positiveOrNull($settings['failure_percent'] ?? null, 100),
            'cutoff_window_minutes' => self::positiveOrNull($settings['window_minutes'] ?? null),
            'cutoff_pause_minutes' => self::positiveOrNull($settings['pause_minutes'] ?? null),
        ];

        DB::transaction(function () use ($key, $values, $by): void {
            $setting = $this->setting($key);
            $before = $setting->only(array_keys($values));

            // Turning the cut-off off also ends any pause it had in force.
            $setting->update($values + ($values['auto_cutoff'] ? [] : self::BREAKER_CLEARED));

            $this->record($key, 'cutoff_settings_changed', null, $by, [
                'before' => $before,
                'after' => $values,
            ]);
        });
    }

    private static function positiveOrNull(mixed $value, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = max(1, (int) $value);

        return $max === null ? $number : min($max, $number);
    }

    /**
     * Persists every scheduled switch-on that has come due. Customers already
     * saw those APIs as on from the due time (see enabledKeys()); this makes
     * the setting and its history say so too.
     */
    public function reEnableDue(): int
    {
        $due = FlightSupplierSetting::query()
            ->where('enabled', false)
            ->whereNotNull('re_enable_at')
            ->where('re_enable_at', '<=', now())
            ->get();

        foreach ($due as $setting) {
            DB::transaction(function () use ($setting): void {
                $reason = $setting->disabled_reason;
                $scheduledFor = $setting->re_enable_at;

                $this->switchOn($setting);
                $this->record($setting->key, 're_enabled_on_schedule', null, null, [
                    'was_off_for' => $reason,
                    'scheduled_for' => $scheduledFor?->toIso8601String(),
                ]);
            });
        }

        return $due->count();
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================

    private function setting(string $key): FlightSupplierSetting
    {
        if (! $this->registry->has($key)) {
            throw new InvalidArgumentException("Unknown flight supplier [{$key}].");
        }

        $this->ensureRows();

        return FlightSupplierSetting::query()->where('key', $key)->firstOrFail();
    }

    /**
     * Switching on by hand also clears any automatic pause: the admin has
     * decided it should run, and the cut-off starts watching it afresh.
     */
    private function switchOn(FlightSupplierSetting $setting): void
    {
        $setting->update([
            'enabled' => true,
            'disabled_reason' => null,
            'disabled_by' => null,
            'disabled_at' => null,
            're_enable_at' => null,
        ] + self::BREAKER_CLEARED);
    }

    private const BREAKER_CLEARED = [
        'breaker_state' => FlightSupplierSetting::BREAKER_CLOSED,
        'breaker_opened_at' => null,
        'breaker_retry_at' => null,
        'breaker_reason' => null,
        'breaker_trial_successes' => 0,
    ];

    private function record(string $key, string $action, ?string $reason, ?User $by, array $details = []): void
    {
        FlightSupplierEvent::query()->create([
            'supplier_key' => $key,
            'action' => $action,
            'reason' => $reason,
            'user_id' => $by?->id,
            'details' => array_filter($details, fn ($value): bool => $value !== null),
            'created_at' => now(),
        ]);
    }
}

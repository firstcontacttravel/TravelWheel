<?php

namespace App\Services\Flights;

use App\Models\FlightSupplierCall;
use App\Models\FlightSupplierEvent;
use App\Models\FlightSupplierSetting;
use App\Services\DurableMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The automatic cut-off: pauses a flight API that keeps failing, and lets it
 * back in once it works again. Thresholds are in config/flights.php
 * ('cutoff'), overridable per API on the Flight APIs screen.
 *
 * It watches every search and price check as it is recorded in
 * flight_supplier_calls:
 *
 *   closed   — normal. A failure checks the recent window; once enough calls
 *              have been made and enough of them failed, the API is paused.
 *   paused   — breaker 'open' with breaker_retry_at ahead: FlightSupplierControl
 *              leaves the API out of every search.
 *   on trial — the pause has run out ('open', retry time passed): calls flow
 *              again. Enough successes in a row resume it; one failure pauses
 *              it again.
 *
 * The manual switch always wins: this only ever acts on an API an admin has
 * switched on, and switching one on by hand clears any pause.
 *
 * Nothing here may break a search, so every entry point swallows its own
 * errors.
 */
class FlightSupplierBreaker
{
    /** Only these calls say whether an API is up. Bookings and ticketing don't. */
    public const WATCHED_CALLS = ['search', 'pricing'];

    public function observe(FlightSupplierCall $call): void
    {
        try {
            if (! in_array($call->call_type, self::WATCHED_CALLS, true)) {
                return;
            }

            // The common case — a working API answering normally — costs one
            // cached read and nothing else.
            if ($call->success && ! $this->control()->breakerOpen($call->supplier)) {
                return;
            }

            $setting = FlightSupplierSetting::query()->where('key', $call->supplier)->first();

            if ($setting === null || ! $setting->isAvailable() || ! $setting->auto_cutoff) {
                return;
            }

            if ($setting->breaker_state === FlightSupplierSetting::BREAKER_OPEN) {
                $this->judgeTrial($setting, $call);

                return;
            }

            if (! $call->success) {
                $this->checkWindow($setting, $call);
            }
        } catch (Throwable $exception) {
            Log::warning('Flight supplier cut-off check failed', [
                'supplier' => $call->supplier,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Failure and total counts for this API's watched calls in its window.
     *
     * @return array{calls:int, failed:int}
     */
    public function recentStats(FlightSupplierSetting $setting): array
    {
        $calls = FlightSupplierCall::query()
            ->where('supplier', $setting->key)
            ->whereIn('call_type', self::WATCHED_CALLS)
            ->where('created_at', '>=', now()->subMinutes($setting->cutoffThresholds()['window_minutes']))
            ->selectRaw('COUNT(*) AS calls, SUM(CASE WHEN success THEN 0 ELSE 1 END) AS failed')
            ->first();

        return ['calls' => (int) ($calls->calls ?? 0), 'failed' => (int) ($calls->failed ?? 0)];
    }

    // =========================================================================
    //  Transitions
    // =========================================================================

    private function checkWindow(FlightSupplierSetting $setting, FlightSupplierCall $call): void
    {
        $limits = $setting->cutoffThresholds();
        $stats = $this->recentStats($setting);

        if ($stats['calls'] < $limits['min_calls']
            || $stats['failed'] * 100 < $limits['failure_percent'] * $stats['calls']) {
            return;
        }

        $reason = sprintf(
            '%d of the last %d searches and price checks failed within %d minutes. Last error: %s',
            $stats['failed'],
            $stats['calls'],
            $limits['window_minutes'],
            $this->lastError($call),
        );

        // Conditional on still being closed, so when several failing calls
        // land at once only one of them pauses the API and sends the alert.
        $paused = FlightSupplierSetting::query()
            ->whereKey($setting->id)
            ->where('breaker_state', FlightSupplierSetting::BREAKER_CLOSED)
            ->update([
                'breaker_state' => FlightSupplierSetting::BREAKER_OPEN,
                'breaker_opened_at' => now(),
                'breaker_retry_at' => now()->addMinutes($limits['pause_minutes']),
                'breaker_reason' => $reason,
                'breaker_trial_successes' => 0,
                'updated_at' => now(),
            ]);

        if ($paused === 0) {
            return;
        }

        FlightSupplierControl::forgetCachedState();

        $event = $this->record($setting->key, 'auto_paused', $reason, [
            'calls' => $stats['calls'],
            'failed' => $stats['failed'],
            'pause_minutes' => $limits['pause_minutes'],
        ]);

        $this->alert($event);
    }

    private function judgeTrial(FlightSupplierSetting $setting, FlightSupplierCall $call): void
    {
        // Still inside the pause: a call that was already under way when the
        // API was paused. It says nothing new.
        if ($setting->isAutoPaused()) {
            return;
        }

        $limits = $setting->cutoffThresholds();

        if (! $call->success) {
            $reason = 'Still failing after the pause. Last error: '.$this->lastError($call);

            $repaused = FlightSupplierSetting::query()
                ->whereKey($setting->id)
                ->where('breaker_state', FlightSupplierSetting::BREAKER_OPEN)
                ->where('breaker_retry_at', '<=', now())
                ->update([
                    'breaker_retry_at' => now()->addMinutes($limits['pause_minutes']),
                    'breaker_reason' => $reason,
                    'breaker_trial_successes' => 0,
                    'updated_at' => now(),
                ]);

            if ($repaused > 0) {
                FlightSupplierControl::forgetCachedState();
                $this->record($setting->key, 'auto_paused_again', $reason, ['pause_minutes' => $limits['pause_minutes']]);
            }

            return;
        }

        $needed = max(1, (int) config('flights.cutoff.trial_successes', 3));
        $successes = $setting->breaker_trial_successes + 1;

        if ($successes < $needed) {
            FlightSupplierSetting::query()->whereKey($setting->id)->update(['breaker_trial_successes' => $successes]);

            return;
        }

        $resumed = FlightSupplierSetting::query()
            ->whereKey($setting->id)
            ->where('breaker_state', FlightSupplierSetting::BREAKER_OPEN)
            ->update([
                'breaker_state' => FlightSupplierSetting::BREAKER_CLOSED,
                'breaker_opened_at' => null,
                'breaker_retry_at' => null,
                'breaker_reason' => null,
                'breaker_trial_successes' => 0,
                'updated_at' => now(),
            ]);

        if ($resumed > 0) {
            FlightSupplierControl::forgetCachedState();

            $event = $this->record($setting->key, 'auto_resumed', null, [
                'paused_since' => $setting->breaker_opened_at?->toIso8601String(),
                'was_paused_for' => $setting->breaker_reason,
            ]);

            $this->alert($event);
        }
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================

    private function lastError(FlightSupplierCall $call): string
    {
        return Str::limit(trim((string) $call->error_message) ?: 'no details', 300);
    }

    private function record(string $key, string $action, ?string $reason, array $details): FlightSupplierEvent
    {
        return FlightSupplierEvent::query()->create([
            'supplier_key' => $key,
            'action' => $action,
            'reason' => $reason,
            'user_id' => null,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    /**
     * Emails support. Through the durable outbox, so a mail server hiccup
     * retries rather than losing the alert — and never delays a search.
     */
    private function alert(FlightSupplierEvent $event): void
    {
        try {
            app(DurableMailService::class)->sendNowOrStore(
                DurableMailService::FLIGHT_SUPPLIER_ALERT,
                (string) config('mail.support_address', config('mail.from.address')),
                $event,
                [],
                'flight-supplier-alert:'.$event->id,
            );
        } catch (Throwable $exception) {
            Log::warning('Flight supplier cut-off alert could not be sent', [
                'event_id' => $event->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function control(): FlightSupplierControl
    {
        return app(FlightSupplierControl::class);
    }
}

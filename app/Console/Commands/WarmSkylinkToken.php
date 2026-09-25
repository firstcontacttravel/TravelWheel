<?php

namespace App\Console\Commands;

use App\Services\SkylinkAuthService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Keeps a SkyLink JWT hot in the cache.
 *
 * The token is cached for 13 minutes against a 15-minute lifetime. On a miss,
 * the customer's own search pays for the login round trip before the search
 * can even start — serial latency added to a call that already averages ~7s.
 * Running this every 10 minutes means the cache is refreshed before it can
 * lapse, so a real search is a warm one-round-trip call essentially always.
 */
class WarmSkylinkToken extends Command
{
    protected $signature = 'skylink:warm-token';

    protected $description = 'Refresh the cached SkyLink access token so customer searches never pay for a login';

    public function handle(SkylinkAuthService $auth): int
    {
        if (! config('services.skylink.enabled')) {
            $this->comment('SkyLink is disabled; nothing to warm.');

            return self::SUCCESS;
        }

        try {
            // accessToken() is a cache-or-login: a warm cache costs nothing,
            // and a cold one is refilled here instead of in a customer request.
            $auth->accessToken();
        } catch (Throwable $exception) {
            // A warm-up failure is not an outage — searches still work, they
            // just log in themselves. Report it without failing the schedule.
            $this->warn('Could not warm the SkyLink token: '.$exception->getMessage());

            return self::SUCCESS;
        }

        $this->info('SkyLink token is warm.');

        return self::SUCCESS;
    }
}

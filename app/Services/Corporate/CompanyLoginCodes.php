<?php

namespace App\Services\Corporate;

use App\Mail\CompanyLoginCodeMail;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The one-time code emailed at sign-in. Codes are hashed, short-lived, work
 * once and allow a few tries.
 *
 * Sent directly, never queued: the staging scheduler has been broken before,
 * and a stuck queue here would lock every company Admin out.
 */
class CompanyLoginCodes
{
    /** @return bool whether the email was accepted for delivery */
    public function send(CompanyUser $user): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'login_code_hash' => Hash::make($code),
            'login_code_expires_at' => now()->addMinutes((int) config('corporate.login_code.minutes')),
            'login_code_attempts' => 0,
            'login_code_sent_at' => now(),
        ])->save();

        try {
            Mail::to($user->email)->send(new CompanyLoginCodeMail($user, $code));

            return true;
        } catch (Throwable $exception) {
            Log::error('Corporate login code email failed', ['company_user_id' => $user->id, 'error' => $exception->getMessage()]);
            $this->clear($user);

            return false;
        }
    }

    public function canResend(CompanyUser $user): bool
    {
        return ! $user->login_code_sent_at
            || $user->login_code_sent_at->lte(now()->subSeconds((int) config('corporate.login_code.resend_after_seconds')));
    }

    public function verify(CompanyUser $user, string $code): bool
    {
        if (! $user->login_code_hash || ! $user->login_code_expires_at?->isFuture()) {
            return false;
        }

        // Counted in the database before checking, so parallel guesses can't
        // get past the limit.
        $counted = CompanyUser::whereKey($user->getKey())
            ->whereNotNull('login_code_hash')
            ->where('login_code_attempts', '<', (int) config('corporate.login_code.max_attempts'))
            ->increment('login_code_attempts');

        if (! $counted || ! Hash::check($code, $user->login_code_hash)) {
            return false;
        }

        $this->clear($user);

        return true;
    }

    private function clear(CompanyUser $user): void
    {
        $user->forceFill([
            'login_code_hash' => null,
            'login_code_expires_at' => null,
            'login_code_attempts' => 0,
        ])->save();
    }
}

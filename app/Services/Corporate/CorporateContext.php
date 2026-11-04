<?php

namespace App\Services\Corporate;

use App\Http\Middleware\EnsureCompanyUser;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

/**
 * Who is booking right now, as far as the server knows: a company user with a
 * session that is still good, or nobody. Never read from anything the browser
 * sends, and never true for a suspended company or a changed password.
 *
 * Work with no browser session behind it (a SeerBit webhook, a scheduled job, a
 * member of staff in the admin) has nobody here. It takes the company from the
 * booking instead.
 */
class CorporateContext
{
    public function user(): ?CompanyUser
    {
        $request = request();

        if (! $request->hasSession()) {
            return null;
        }

        $user = Auth::guard('corporate')->user();

        if (! $user instanceof CompanyUser) {
            return null;
        }

        $user->loadMissing('company');

        return $user->canSignIn() && $this->sessionMatches($user) ? $user : null;
    }

    public function company(): ?Company
    {
        return $this->user()?->company;
    }

    public function sessionMatches(CompanyUser $user): bool
    {
        return hash_equals(
            (string) request()->session()->get(EnsureCompanyUser::FINGERPRINT_KEY),
            $user->sessionFingerprint(),
        );
    }
}

<?php

namespace App\Services\Corporate;

use App\Http\Middleware\EnsureCompanyUser;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Who is booking right now, as far as the server knows: a company user with a
 * session that is still good, a member of staff booking for a company, or
 * nobody. Never read from anything the browser sends, and never true for a
 * suspended company or a changed password.
 *
 * Work with no browser session behind it (a SeerBit webhook, a scheduled job, a
 * member of staff in the admin) has nobody here. It takes the company from the
 * booking instead.
 */
class CorporateContext
{
    public const ACTING_KEY = 'business.acting_company_id';

    /**
     * Staff booking for a company act through its stand-in, so rates, the wallet
     * and every check work as they do for the company's own people. Checked
     * afresh on each request, so losing the permission ends it at once.
     */
    public function user(): ?CompanyUser
    {
        $request = request();

        if (! $request->hasSession()) {
            return null;
        }

        if ($this->actingStaff() !== null) {
            return $this->actingCompany()?->systemUser();
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

    /** The member of staff booking for a company, if they still may. */
    public function actingStaff(): ?User
    {
        if (! request()->hasSession() || ! request()->session()->has(self::ACTING_KEY)) {
            return null;
        }

        $staff = Auth::guard('web')->user();
        $company = $this->actingCompany();

        return $staff instanceof User && $company?->canBeBookedForBy($staff) ? $staff : null;
    }

    /** The session says staff are booking for a company, whether or not they still may. */
    public function hasActingSession(): bool
    {
        return request()->hasSession() && request()->session()->has(self::ACTING_KEY);
    }

    public function actingCompany(): ?Company
    {
        return Company::find(request()->session()->get(self::ACTING_KEY));
    }

    /**
     * Uses session() rather than the request's, because this starts from a
     * Filament action. Any company login in this browser ends, so one browser
     * is never both.
     */
    public function startActing(Company $company, User $staff): void
    {
        abort_unless($company->canBeBookedForBy($staff), 403);

        Auth::guard('corporate')->logout();
        session()->forget(EnsureCompanyUser::FINGERPRINT_KEY);
        session()->put(self::ACTING_KEY, $company->id);
    }

    public function stopActing(): void
    {
        session()->forget(self::ACTING_KEY);
    }
}

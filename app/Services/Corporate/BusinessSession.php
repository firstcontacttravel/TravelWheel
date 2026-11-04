<?php

namespace App\Services\Corporate;

use App\Http\Controllers\Business\AuthController;
use App\Http\Middleware\EnsureCompanyUser;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Starting and ending a corporate session. */
class BusinessSession
{
    public function signIn(Request $request, CompanyUser $user): void
    {
        Auth::guard('corporate')->login($user);

        $request->session()->regenerate();
        $request->session()->put(EnsureCompanyUser::FINGERPRINT_KEY, $user->sessionFingerprint());

        $user->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * Ends the corporate login only. A member of staff signed in to the same
     * browser stays signed in, so the whole session isn't destroyed.
     */
    public function signOut(Request $request): void
    {
        Auth::guard('corporate')->logout();

        $request->session()->forget([EnsureCompanyUser::FINGERPRINT_KEY, AuthController::PENDING_CODE_KEY]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }
}

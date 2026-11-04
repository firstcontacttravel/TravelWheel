<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureCompanyUser;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** A person's own details, password and sign-in code choice. */
class AccountController extends Controller
{
    public function show(): View
    {
        return view('business.account');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('companyUser');

        $data = $request->validate([
            'current_password' => ['required', 'current_password:corporate'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user->update(['password' => $data['password']]);

        // Keeps this session and signs out every other one.
        $request->session()->put(EnsureCompanyUser::FINGERPRINT_KEY, $user->sessionFingerprint());

        ActivityLog::recordForCompanyUser($user, 'company_password_changed', 'Changed their password', $user);

        return back()->with('status', 'Your password has been changed.');
    }

    public function updateLoginCode(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('companyUser');

        if ($user->isCompanyAdmin()) {
            return back()->with('status', 'Company Admins always use a sign-in code.');
        }

        $enabled = $request->boolean('login_code_enabled');
        $user->update(['login_code_enabled' => $enabled]);

        ActivityLog::recordForCompanyUser($user, 'company_login_code_changed', ($enabled ? 'Turned on' : 'Turned off').' their sign-in code', $user);

        return back()->with('status', $enabled ? 'You\'ll get an email code when you sign in.' : 'Sign-in codes are off.');
    }
}

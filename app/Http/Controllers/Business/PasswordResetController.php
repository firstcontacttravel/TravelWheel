<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CompanyUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('business.auth.forgot-password');
    }

    /** The same answer whether or not the email has an account. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            Password::broker('company_users')->sendResetLink(['email' => strtolower(trim($data['email']))]);
        } catch (Throwable $exception) {
            Log::error('Corporate password reset email failed', ['error' => $exception->getMessage()]);
        }

        return back()->with('status', 'If that email has an account, we\'ve sent a link to reset the password.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('business.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::broker('company_users')->reset(
            [
                'email' => strtolower(trim($data['email'])),
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password'],
            ],
            function (CompanyUser $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                ActivityLog::recordForCompanyUser($user, 'company_password_reset', 'Reset their password', $user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please ask for a new one.']);
        }

        // Sent back to sign in rather than signed in here, so an Admin still
        // has to pass the email code.
        return redirect()->route('business.login')->with('status', 'Your password has been changed. Please sign in.');
    }
}

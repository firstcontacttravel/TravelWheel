<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Services\Corporate\BusinessSession;
use App\Services\Corporate\CompanyLoginCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Signing in at /business: password, then an email code if the person needs one. */
class AuthController extends Controller
{
    public const PENDING_CODE_KEY = 'business.pending_login_user_id';

    private const MAX_PASSWORD_ATTEMPTS = 5;

    public function create(): View|RedirectResponse
    {
        return Auth::guard('corporate')->check()
            ? redirect()->route('business.dashboard')
            : view('business.auth.login');
    }

    public function store(Request $request, CompanyLoginCodes $codes, BusinessSession $session): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($credentials['email']));
        $limiterKey = 'business-login:'.Str::transliterate($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_PASSWORD_ATTEMPTS)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($limiterKey) / 60));

            return back()->onlyInput('email')->withErrors(['email' => "Too many attempts. Please try again in {$minutes} minute(s)."]);
        }

        $guard = Auth::guard('corporate');

        if (! $guard->validate(['email' => $email, 'password' => $credentials['password']])) {
            RateLimiter::hit($limiterKey, 300);

            return back()->onlyInput('email')->withErrors(['email' => 'That email and password don\'t match.']);
        }

        RateLimiter::clear($limiterKey);

        $user = CompanyUser::with('company')->where('email', $email)->firstOrFail();

        // Only reached with the right password, so this reveals nothing to a stranger.
        if (! $user->canSignIn()) {
            return back()->onlyInput('email')->withErrors(['email' => 'This account is not available. Please contact TravelWheel.']);
        }

        if (! $user->needsLoginCode()) {
            $session->signIn($request, $user);

            return redirect()->route('business.dashboard');
        }

        if (! $codes->send($user)) {
            return back()->onlyInput('email')->withErrors(['email' => 'We couldn\'t email your sign-in code. Please try again shortly.']);
        }

        $request->session()->regenerate();
        $request->session()->put(self::PENDING_CODE_KEY, $user->getKey());

        return redirect()->route('business.login.code');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        return $user
            ? view('business.auth.code', ['email' => $user->email])
            : redirect()->route('business.login');
    }

    public function verifyCode(Request $request, CompanyLoginCodes $codes, BusinessSession $session): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('business.login');
        }

        $data = $request->validate(['code' => ['required', 'string']]);

        if (! $codes->verify($user, preg_replace('/\s+/', '', $data['code']))) {
            return back()->withErrors(['code' => 'That code is wrong or has expired. Check the latest email, or send a new code.']);
        }

        $request->session()->forget(self::PENDING_CODE_KEY);
        $session->signIn($request, $user);

        return redirect()->route('business.dashboard');
    }

    public function resendCode(Request $request, CompanyLoginCodes $codes): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('business.login');
        }

        if (! $codes->canResend($user)) {
            return back()->withErrors(['code' => 'Please wait a minute before asking for another code.']);
        }

        return $codes->send($user)
            ? back()->with('status', 'We\'ve sent a new code.')
            : back()->withErrors(['code' => 'We couldn\'t email a new code. Please try again shortly.']);
    }

    public function destroy(Request $request, BusinessSession $session): RedirectResponse
    {
        $session->signOut($request);

        return redirect()->route('business.login')->with('status', 'You\'ve signed out.');
    }

    /** Whoever passed the password step, if they may still sign in. */
    private function pendingUser(Request $request): ?CompanyUser
    {
        $id = $request->session()->get(self::PENDING_CODE_KEY);
        $user = $id ? CompanyUser::with('company')->find($id) : null;

        if ($user && ! $user->canSignIn()) {
            $request->session()->forget(self::PENDING_CODE_KEY);

            return null;
        }

        return $user;
    }
}

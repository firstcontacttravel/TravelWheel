<?php

namespace App\Http\Middleware;

use App\Services\Corporate\CorporateContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for every signed-in /business page.
 *
 * Checked on every request, so suspending a company, deactivating a person or
 * changing a password takes effect at once rather than at their next sign-in.
 *
 * Reads the corporate guard by name and never makes it the default: elsewhere
 * in the app auth()->user() means a member of staff, and has to stay that way.
 */
class EnsureCompanyUser
{
    public const FINGERPRINT_KEY = 'business.session_fingerprint';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('corporate');
        $user = $guard->user();

        if (! $user) {
            return redirect()->route('business.login');
        }

        $user->loadMissing('company');

        $passwordUnchanged = app(CorporateContext::class)->sessionMatches($user);

        if (! $user->canSignIn() || ! $passwordUnchanged) {
            $guard->logout();
            $request->session()->forget(self::FINGERPRINT_KEY);
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->withErrors(['email' => $passwordUnchanged
                    ? 'This account is not available. Please contact TravelWheel.'
                    : 'Your password was changed. Please sign in again.']);
        }

        $request->attributes->set('companyUser', $user);
        view()->share('companyUser', $user);

        $response = $next($request);

        // After signing out, the back button must not show company pages.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}

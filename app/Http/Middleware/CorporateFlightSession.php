<?php

namespace App\Http\Middleware;

use App\Services\Corporate\BusinessSession;
use App\Services\Corporate\CorporateContext;
use App\Services\Flights\FlightSearchStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps one browser's flight search and checkout tied to one set of prices.
 *
 * Runs on every web request, because the public flight pages are shared. The
 * session remembers which company its search and checkout were priced for. If
 * that stops being true, through signing in, signing out, suspension or
 * deactivation, they are thrown away. Corporate fares can't carry over into
 * public use, or public fares into a company's checkout.
 */
class CorporateFlightSession
{
    public const COMPANY_KEY = 'flights.company_id';

    private const FLIGHT_SESSION_KEYS = [
        // Search
        'pendingFlightSearch',
        'pendingFlightSearchStartedAt',
        'pendingFlightSearchLogId',
        'flightResultsStore',
        'supplementResultsStore',
        'skylinkResultsStore',
        'supplierSearchMeta',
        'searchSupplementSuppliers',
        'searchSessionId',
        'searchParamsStore',
        FlightSearchStore::SESSION_ID,
        FlightSearchStore::SESSION_MODE,
        // Checkout
        'bookingFlight',
        'bookingSearchParams',
        'bookingSessionId',
        'bookingContact',
        'bookingPassengers',
        'bookingUniqueId',
        'bookingRef',
        'bookingStatus',
        'bookingConfirmation',
        'bookingTktTimeLimit',
        'bookingIntent',
        'flightBookingDbId',
        'selectedExtras',
        'extraBaggage',
        'extraMeal',
        'extraServices',
        'fareRules',
        'tripType',
        'paymentMethod',
        'seerbitPaymentReference',
        'seerbitPaymentFlow',
        'ticketOrderResult',
        'ticketSuccess',
        'travelFlexPlan',
        'travelFlexApplicationId',
        'travelFlexApplicant',
        'travelFlexDocPaths',
        'corporatePriceChange',
    ];

    public function __construct(
        private readonly CorporateContext $context,
        private readonly BusinessSession $session,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // The parallel search route has no session, and takes its company from
        // the search. A customer coming back from paying has already paid, so
        // that checkout must carry on whatever has changed.
        if (! $request->hasSession() || $request->routeIs('payments.seerbit.*')) {
            return $next($request);
        }

        $user = $this->context->user();
        $cutOff = $user === null && Auth::guard('corporate')->check();

        $companyId = $user ? (int) $user->company_id : null;
        $session = $request->session();

        if ($session->get(self::COMPANY_KEY) !== $companyId) {
            $session->forget(self::FLIGHT_SESSION_KEYS);
            $companyId === null ? $session->forget(self::COMPANY_KEY) : $session->put(self::COMPANY_KEY, $companyId);
        }

        // Portal pages do this themselves and say why.
        if ($cutOff && ! $request->routeIs('business.*')) {
            $this->session->signOut($request);

            if ($request->routeIs('flights.*', 'air.flight-s')) {
                return redirect()->route('business.login')
                    ->withErrors(['email' => 'This account is not available. Please contact TravelWheel.']);
            }
        }

        if ($user !== null && $request->routeIs('flights.travelflex*')) {
            return redirect()->route('air')
                ->withErrors(['error' => 'TravelFlex is not available on corporate accounts.']);
        }

        view()->share('corporateCompany', $user?->company);

        return $next($request);
    }
}

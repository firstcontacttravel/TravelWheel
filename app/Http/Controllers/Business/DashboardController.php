<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Support\FlightDisplay;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    /** How many recent bookings are looked through for upcoming trips. */
    private const UPCOMING_WINDOW = 200;

    public function __invoke(Request $request): View
    {
        $user = $request->attributes->get('companyUser');

        return view('business.dashboard', [
            'company' => $user->company->loadMissing('accountManager'),
            'upcoming' => $this->upcoming($user),
            'recent' => $this->visibleBookings($user)->latest('id')->limit(5)->get(),
        ]);
    }

    /** The departure date is inside the fare snapshot, so it's read here rather than queried. */
    private function upcoming(CompanyUser $user): Collection
    {
        return $this->visibleBookings($user)
            ->where('payment_status', 'paid')
            ->whereIn('booking_status', ['confirmed', 'ticketed'])
            ->latest('id')
            ->limit(self::UPCOMING_WINDOW)
            ->get()
            ->map(fn (FlightBooking $booking): array => ['booking' => $booking, 'departs' => $this->departure($booking)])
            ->filter(fn (array $trip): bool => $trip['departs'] !== null && $trip['departs']->isFuture())
            ->sortBy(fn (array $trip) => $trip['departs']->timestamp)
            ->take(5)
            ->values();
    }

    private function departure(FlightBooking $booking): ?Carbon
    {
        $flight = (array) $booking->flight_snapshot;
        $value = data_get($flight, 'segments.0.departDT') ?? data_get($flight, 'multiLegs.0.segments.0.departDT');

        try {
            return $value ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function visibleBookings(CompanyUser $user): Builder
    {
        return FlightBooking::query()
            ->where('company_id', $user->company_id)
            ->when(! $user->isCompanyAdmin(), fn (Builder $query) => $query->where('company_user_id', $user->id));
    }
}

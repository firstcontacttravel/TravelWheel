<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Services\ItineraryPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * A company's flight bookings. Admins see them all and Bookers see their own.
 * Every lookup goes through visibleTo(), so anything else answers 404.
 */
class BookingController extends Controller
{
    public const STATUSES = [
        'on_hold' => 'On hold',
        'pending_payment' => 'Awaiting payment',
        'confirmed' => 'Confirmed',
        'ticketed' => 'Ticketed',
        'ticketing_failed' => 'Being resolved',
        'failed' => 'Being resolved',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request): View
    {
        $user = $request->attributes->get('companyUser');
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $bookings = $this->visibleTo($user)
            ->with('companyUser')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('booking_ref', 'like', '%'.$search.'%')
                ->orWhere('route', 'like', '%'.$search.'%')
                ->orWhere('airline', 'like', '%'.$search.'%')
                ->orWhere('cost_code', 'like', '%'.$search.'%')))
            ->when(array_key_exists($status, self::STATUSES), fn (Builder $query) => $query->where('booking_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('business.bookings', [
            'bookings' => $bookings,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function ticket(Request $request, string $reference, ItineraryPdfService $documents): Response
    {
        $booking = $this->visibleTo($request->attributes->get('companyUser'))
            ->where('booking_ref', $reference)
            ->firstOrFail();

        abort_unless(self::hasTicket($booking), 404);

        return response($documents->generate($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$documents->filename($booking).'"',
        ]);
    }

    public static function hasTicket(FlightBooking $booking): bool
    {
        return $booking->payment_status === 'paid'
            && in_array($booking->booking_status, ['confirmed', 'ticketed'], true);
    }

    private function visibleTo(CompanyUser $user): Builder
    {
        return FlightBooking::query()
            ->where('company_id', $user->company_id)
            ->when(! $user->isCompanyAdmin(), fn (Builder $query) => $query->where('company_user_id', $user->id));
    }
}

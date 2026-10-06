<?php

namespace App\Workflow;

use App\Models\FlightBooking;
use App\Models\PostTicketingRequest;
use App\Models\VisaApplication;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Re-syncs a booking's work item whenever the booking (or something its stage
 * depends on) is saved, wherever that save came from: an admin action, a
 * payment webhook, the reconcile job, the customer's checkout.
 *
 * A failure here is reported and swallowed. Work tracking must never be the
 * reason a payment is not recorded or a ticket is not issued.
 */
class SyncsWorkItems
{
    public static function register(): void
    {
        FlightBooking::saved(fn (FlightBooking $booking) => self::sync($booking));
        VisaApplication::saved(fn (VisaApplication $application) => self::sync($application));

        // An open refund, void or reissue moves a ticketed booking into
        // "Change with supplier" and back out when it completes.
        PostTicketingRequest::saved(function (PostTicketingRequest $request): void {
            if ($request->booking) {
                self::sync($request->booking);
            }
        });
    }

    public static function sync(Model $subject): void
    {
        try {
            app(WorkItemService::class)->sync($subject);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

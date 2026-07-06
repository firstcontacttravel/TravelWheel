<?php

namespace App\Services;

use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class TravelFlexFlowService
{
    public const MINIMUM_PAYMENT_WINDOW_HOURS = 2;

    public function applicationForBooking(FlightBooking $booking): ?TravelFlexApplication
    {
        return TravelFlexApplication::query()
            ->where('flight_booking_id', $booking->id)
            ->orWhere(fn ($query) => $query
                ->whereNotNull('booking_ref')
                ->where('booking_ref', $booking->booking_ref))
            ->latest()
            ->first();
    }

    public function assertApprovedForDeposit(TravelFlexApplication $application): FlightBooking
    {
        $booking = $application->booking;
        if (! $booking) {
            throw ValidationException::withMessages(['travelflex' => 'The held booking could not be found.']);
        }

        if ($application->application_status !== 'approved' || $application->financing_status !== 'approved') {
            throw ValidationException::withMessages(['travelflex' => 'Fast Credit approval is required before a down payment can be made.']);
        }

        if ($booking->ticket_ordered || $booking->booking_status === 'ticketed') {
            throw ValidationException::withMessages(['travelflex' => 'This flight has already been ticketed.']);
        }

        if (! $booking->tkt_time_limit || $booking->tkt_time_limit->lte(now()->addHours(self::MINIMUM_PAYMENT_WINDOW_HOURS))) {
            throw ValidationException::withMessages([
                'travelflex' => 'The airline hold no longer leaves enough time to complete payment. Please contact TravelWheel so the fare can be rebooked.',
            ]);
        }

        if (! in_array($booking->booking_status, ['on_hold', 'confirmed', 'awaiting_approval', 'awaiting_deposit'], true)) {
            throw ValidationException::withMessages(['travelflex' => 'The held fare is no longer available for TravelFlex payment.']);
        }

        return $booking;
    }

    public function approvalDeadline(TravelFlexApplication $application): ?\Carbon\CarbonInterface
    {
        $holdDeadline = $application->booking?->tkt_time_limit?->copy()->subHours(self::MINIMUM_PAYMENT_WINDOW_HOURS);
        if (! $holdDeadline) return null;
        return $application->approval_expires_at && $application->approval_expires_at->lt($holdDeadline)
            ? $application->approval_expires_at
            : $holdDeadline;
    }

    public function approvalUrl(TravelFlexApplication $application): ?string
    {
        $deadline = $this->approvalDeadline($application);
        if (! $deadline || $deadline->isPast()) return null;

        return URL::temporarySignedRoute('flights.travelflex.approved', $deadline, ['application' => $application->id]);
    }

    public function revalidateHold(TravelFlexApplication $application): FlightBooking
    {
        $booking = $this->assertApprovedForDeposit($application->load('booking'));
        $result = app(AdminTicketingService::class)->tripDetails($booking);
        $status = strtoupper((string) ($result['booking_status'] ?? ''));

        if (! ($result['ok'] ?? false) || ! in_array($status, ['CONFIRMED', 'BOOKED', 'ON_HOLD', 'ON HOLD'], true)) {
            throw ValidationException::withMessages([
                'travelflex' => 'We could not confirm that the airline hold is still active. No payment was started. Please contact TravelWheel to rebook or revalidate the fare.',
            ]);
        }

        $application->update(['pricing_revalidated_at' => now()]);

        return $booking->fresh();
    }

    public function primeSession(TravelFlexApplication $application): void
    {
        $booking = $application->booking;
        if (! $booking) return;

        session([
            'travelFlexApplicationId' => $application->id,
            'travelFlexPlan' => $application->repayment_plan ?? [],
            'travelFlexApplicant' => array_merge($application->applicant_details ?? [], $application->employment_details ?? []),
            'travelFlexDocPaths' => $application->document_paths ?? [],
            'flightBookingDbId' => $booking->id,
            'bookingRef' => $booking->booking_ref,
            'bookingUniqueId' => $booking->unique_id,
            'bookingTktTimeLimit' => optional($booking->tkt_time_limit)->toIso8601String(),
            'bookingFlight' => ['flight' => $booking->flight_snapshot ?? []],
            'bookingContact' => ['email' => $booking->contact_email, 'phone' => $booking->contact_phone],
            'bookingPassengers' => $booking->passengers_snapshot ?? [],
            'selectedExtras' => $booking->extra_services_snapshot ?? [],
            'bookingIntent' => 'travelflex',
        ]);
    }
}

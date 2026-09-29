<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\PostTicketingRequest;
use App\Models\SystemHeartbeat;
use App\Models\TravelFlexApplication;
use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplication;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * The operations dashboard.
 *
 * Replaces two generic stats-overview widgets — seventeen tiles of identical
 * weight in which "Failed payment 5" and "Paid revenue NGN 370,009.40" were
 * indistinguishable apart from a 12px icon at the end of a caption.
 *
 * The order is the design: BROKEN, then WAITING, then MONEY.
 *
 *   Broken is what needs a person today. It is loud, it says why, and it links
 *   straight to the queue. When nothing is broken the whole band collapses to
 *   one calm line — which is the most important behaviour here, because a
 *   dashboard that is usually quiet is one people keep looking at, and a
 *   dashboard that is permanently amber is one they stop seeing.
 *
 *   Waiting is normal queues. Counts, no alarm colour.
 *
 *   Money is last, because it is context rather than an action. It was
 *   previously the loudest thing on the screen.
 *
 * Polling: Filament's widget default is every 5 seconds. Four widgets at that
 * rate is a full panel boot and a dozen aggregate queries every five seconds,
 * per open dashboard, forever. The queries are cheap (~50ms for all of them);
 * the frequency was the cost. Sixty seconds plus a manual refresh is what an
 * operations queue actually needs.
 */
class OperationsTriage extends Widget
{
    protected string $view = 'filament.widgets.operations-triage';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    /**
     * Signals that need a person today.
     *
     * @return list<array<string, mixed>>
     */
    public function getBroken(): array
    {
        $signals = [];

        $ticketingFailed = FlightBooking::query()
            ->where('payment_status', 'paid')
            ->whereIn('booking_status', ['failed', 'ticketing_failed'])
            ->count();

        if ($ticketingFailed > 0) {
            $signals[] = [
                'count' => $ticketingFailed,
                'label' => 'Ticketing failed',
                'detail' => 'Paid in full with no ticket issued',
                'url' => FlightBookingResource::getUrl('index', ['activeTab' => 'ticketing_failed']),
            ];
        }

        $failedPayment = FlightBooking::query()->where('payment_status', 'failed')->count();

        if ($failedPayment > 0) {
            $signals[] = [
                'count' => $failedPayment,
                'label' => 'Failed payment',
                'detail' => 'Customer payment did not complete',
                'url' => FlightBookingResource::getUrl(),
            ];
        }

        $failedMail = NotificationOutbox::query()
            ->whereNotNull('failed_at')
            ->whereNull('sent_at')
            ->count();

        if ($failedMail > 0) {
            $signals[] = [
                'count' => $failedMail,
                'label' => 'Email delivery failed',
                'detail' => 'Saved messages that could not be sent',
                'url' => null,
            ];
        }

        // Carbon's diffInMinutes is signed, so a comparison against a past
        // timestamp inverts. isAfter() says what is meant.
        $heartbeat = SystemHeartbeat::query()->where('name', 'scheduler')->first()?->last_seen_at;

        if (! $heartbeat?->isAfter(now()->subMinutes(3))) {
            $signals[] = [
                'count' => null,
                'label' => 'Scheduler stale',
                'detail' => $heartbeat
                    ? 'Last checked in '.$heartbeat->diffForHumans()
                    : 'The scheduler has never checked in',
                'url' => null,
            ];
        }

        return $signals;
    }

    /**
     * Normal queues. Counts, no alarm colour.
     *
     * @return list<array<string, mixed>>
     */
    public function getWaiting(): array
    {
        $queues = [
            [
                'count' => FlightBooking::query()->where('payment_status', 'awaiting_bank_transfer')->count(),
                'label' => 'Awaiting transfer',
                'url' => FlightBookingResource::getUrl('index', ['activeTab' => 'awaiting_transfer']),
            ],
            [
                'count' => FlightBooking::query()
                    ->where('payment_status', 'paid')
                    ->where('ticket_ordered', false)
                    ->where('booking_status', '!=', 'ticketed')
                    ->count(),
                'label' => 'Ready to ticket',
                'url' => FlightBookingResource::getUrl('index', ['activeTab' => 'ready_to_ticket']),
            ],
            [
                'count' => PostTicketingRequest::query()
                    ->whereIn('status', ['pending', 'submitted', 'in_process', 'inprocess'])
                    ->count(),
                'label' => 'Open post-ticketing',
                'url' => null,
            ],
            [
                'count' => TravelFlexApplication::query()->where('application_status', 'submitted')->count(),
                'label' => 'TravelFlex to review',
                'url' => null,
            ],
            [
                'count' => NotificationOutbox::query()->whereNull('sent_at')->whereNull('failed_at')->count(),
                'label' => 'Email queued',
                'url' => null,
            ],
        ];

        if (auth()->user()?->canViewVisaOperations()) {
            $queues[] = [
                'count' => VisaApplication::query()
                    ->where('status', 'submitted')
                    ->whereNull('assigned_to')
                    ->count(),
                'label' => 'Visas unassigned',
                'url' => VisaApplicationResource::getUrl(),
            ];
            $queues[] = [
                'count' => VisaAdditionalDocumentRequest::query()->where('status', 'submitted')->count(),
                'label' => 'Visa documents to review',
                'url' => VisaApplicationResource::getUrl(),
            ];
            $queues[] = [
                'count' => VisaApplication::query()->where('status', 'action_required')->count(),
                'label' => 'Visas awaiting applicant',
                'url' => VisaApplicationResource::getUrl(),
            ];
        }

        return $queues;
    }

    /** @return array<string, string> */
    public function getMoney(): array
    {
        $paid = FlightBooking::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30));

        return [
            'revenue' => 'NGN '.number_format((float) (clone $paid)->sum(
                DB::raw('COALESCE(payment_charged_amount, payment_amount, total_price, 0)'),
            ), 2),
            'charges' => 'NGN '.number_format((float) (clone $paid)->sum('markup_amount'), 2),
            'today' => number_format(FlightBooking::query()->whereDate('created_at', today())->count()),
            'ticketed' => number_format((clone $paid)->where('booking_status', 'ticketed')->count()),
        ];
    }

    public function getCheckedAt(): string
    {
        return now()->timezone('Africa/Lagos')->format('H:i');
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Models\FlightBooking;
use App\Models\PostTicketingRequest;
use App\Models\TravelFlexApplication;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentOperationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Operations Overview';

    protected ?string $description = 'High-priority payment, ticketing, and support queues.';

    protected function getStats(): array
    {
        $todayBookings = FlightBooking::query()
            ->whereDate('created_at', today())
            ->count();

        $paidRevenue = (float) FlightBooking::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_charged_amount, payment_amount, total_price, 0)'));

        $serviceCharges = (float) FlightBooking::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('markup_amount');

        return [
            Stat::make('Today bookings', number_format($todayBookings))
                ->description('New bookings created today')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('primary')
                ->url(FlightBookingResource::getUrl()),

            Stat::make('Paid revenue', 'NGN ' . number_format($paidRevenue, 2))
                ->description('Last 30 days')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('Service charges', 'NGN ' . number_format($serviceCharges, 2))
                ->description('Markup collected in last 30 days')
                ->descriptionIcon(Heroicon::OutlinedReceiptPercent)
                ->color('info'),

            Stat::make('Awaiting bank transfer', FlightBooking::query()
                ->where('payment_status', 'awaiting_bank_transfer')
                ->count())
                ->description('Customer has submitted transfer notice')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('warning')
                ->url(FlightBookingResource::getUrl()),

            Stat::make('Paid, not ticketed', FlightBooking::query()
                ->where('payment_status', 'paid')
                ->where('ticket_ordered', false)
                ->count())
                ->description('Ready for ticketing review')
                ->descriptionIcon(Heroicon::OutlinedTicket)
                ->color('success')
                ->url(FlightBookingResource::getUrl()),

            Stat::make('Failed payment', FlightBooking::query()
                ->where('payment_status', 'failed')
                ->count())
                ->description('Payment needs support follow-up')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->url(FlightBookingResource::getUrl()),

            Stat::make('Ticketing failed', FlightBooking::query()
                ->whereIn('booking_status', ['failed', 'ticketing_failed'])
                ->where('payment_status', 'paid')
                ->count())
                ->description('Paid booking with failed ticketing')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(FlightBookingResource::getUrl()),

            Stat::make('Open PTR', PostTicketingRequest::query()
                ->whereIn('status', ['pending', 'submitted', 'in_process', 'inprocess'])
                ->count())
                ->description('Refund, void, reissue, or status requests')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('info'),

            Stat::make('TravelFlex submitted', TravelFlexApplication::query()
                ->where('application_status', 'submitted')
                ->count())
                ->description('Loan applications awaiting admin review')
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('primary'),
        ];
    }
}

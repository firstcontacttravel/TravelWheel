<?php

namespace App\Filament\Resources\FlightBookings\Pages;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Models\FlightBooking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFlightBookings extends ListRecords
{
    protected static string $resource = FlightBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): int => FlightBooking::query()->count()),
            'awaiting_transfer' => Tab::make('Awaiting transfer')
                ->badge(fn (): int => FlightBooking::query()->where('payment_status', 'awaiting_bank_transfer')->count())
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => $query->where('payment_status', 'awaiting_bank_transfer')),
            'ready_to_ticket' => Tab::make('Ready to ticket')
                ->badge(fn (): int => FlightBooking::query()
                    ->where('payment_status', 'paid')
                    ->where('ticket_ordered', false)
                    ->where('booking_status', '!=', 'ticketed')
                    ->count())
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query
                    ->where('payment_status', 'paid')
                    ->where('ticket_ordered', false)
                    ->where('booking_status', '!=', 'ticketed')),
            'ticketing_failed' => Tab::make('Ticketing failed')
                ->badge(fn (): int => FlightBooking::query()
                    ->where('payment_status', 'paid')
                    ->whereIn('booking_status', ['failed', 'ticketing_failed'])
                    ->count())
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query
                    ->where('payment_status', 'paid')
                    ->whereIn('booking_status', ['failed', 'ticketing_failed'])),
            'ticketed' => Tab::make('Ticketed')
                ->badge(fn (): int => FlightBooking::query()->where('booking_status', 'ticketed')->count())
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->where('booking_status', 'ticketed')),
            'pending_payment' => Tab::make('Pending payment')
                ->badge(fn (): int => FlightBooking::query()->where('payment_status', 'pending')->count())
                ->badgeColor('gray')
                ->query(fn (Builder $query): Builder => $query->where('payment_status', 'pending')),
        ];
    }
}

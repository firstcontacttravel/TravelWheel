<?php

namespace App\Filament\Resources\FlightBookings\Pages;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Filament\Resources\FlightBookings\Tables\FlightBookingsTable;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewFlightBooking extends ViewRecord
{
    protected static string $resource = FlightBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                FlightBookingsTable::markBankTransferPaidAction(),
                FlightBookingsTable::markFeesTransferPaidAction(),
                FlightBookingsTable::verifySeerbitPaymentAction(),
                FlightBookingsTable::sendPaymentReceiptAction(),
            ])
                ->label('Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button(),

            ActionGroup::make([
                FlightBookingsTable::orderTicketAction(),
                FlightBookingsTable::fetchTripDetailsAction(),
                FlightBookingsTable::resendETicketAction(),
                FlightBookingsTable::sendTicketingFailureAlertAction(),
            ])
                ->label('Ticketing')
                ->icon('heroicon-o-ticket')
                ->color('warning')
                ->button(),

            ActionGroup::make([
                FlightBookingsTable::cancelBookingAction(),
                FlightBookingsTable::voidQuoteAction(),
                FlightBookingsTable::voidTicketAction(),
                FlightBookingsTable::refundQuoteAction(),
                FlightBookingsTable::refundTicketAction(),
                FlightBookingsTable::reissueQuoteAction(),
                FlightBookingsTable::reissueTicketAction(),
                FlightBookingsTable::searchPtrStatusAction(),
            ])
                ->label('Post-ticketing')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->button(),
        ];
    }
}

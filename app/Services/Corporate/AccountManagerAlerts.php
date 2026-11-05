<?php

namespace App\Services\Corporate;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Mail\CompanyBookingProblemMail;
use App\Models\FlightBooking;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Keeps a company's account manager in the picture: a bell notification for
 * each paid booking, and an email as well when one needs attention.
 */
class AccountManagerAlerts
{
    private const PROBLEM_STATUSES = ['failed', 'ticketing_failed', 'hold_expired_review'];

    public function bookingSaved(FlightBooking $booking): void
    {
        $manager = $booking->company?->accountManager;

        if (! $manager || $manager->isDeactivated()) {
            return;
        }

        $url = FlightBookingResource::getUrl('view', ['record' => $booking], panel: 'admin');
        $company = $booking->company->name;

        if ($booking->wasChanged('booking_status') && in_array($booking->booking_status, self::PROBLEM_STATUSES, true)) {
            Notification::make()
                ->danger()
                ->title($company.': booking '.$booking->booking_ref.' needs attention')
                ->body($booking->route)
                ->actions([Action::make('open')->label('Open')->url($url)->markAsRead()])
                ->sendToDatabase($manager);

            Mail::to($manager->email)->send(new CompanyBookingProblemMail($booking, $url));

            return;
        }

        if (($booking->wasRecentlyCreated || $booking->wasChanged('payment_status')) && $booking->payment_status === 'paid') {
            Notification::make()
                ->success()
                ->title($company.': booking '.$booking->booking_ref.' paid')
                ->body($booking->route)
                ->actions([Action::make('open')->label('Open')->url($url)->markAsRead()])
                ->sendToDatabase($manager);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Filament\Pages\NewBooking;
use App\Services\Flights\StaffFlightBooking;
use Illuminate\Http\RedirectResponse;

/** Staff leaving the website's flight checkout without booking for the customer. */
class StaffFlightBookingController extends Controller
{
    public function stop(StaffFlightBooking $staffFlight): RedirectResponse
    {
        $staffFlight->stop();

        return redirect()->to(NewBooking::getUrl());
    }
}

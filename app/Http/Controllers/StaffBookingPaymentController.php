<?php

namespace App\Http\Controllers;

use App\Models\StaffBooking;
use App\Services\SeerbitPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Where a customer lands from the payment link staff sent them. Paying takes
 * a click on this page, so a mail scanner opening the link starts nothing.
 */
class StaffBookingPaymentController extends Controller
{
    public function show(string $token): View
    {
        $staffBooking = StaffBooking::where('pay_token', $token)->firstOrFail();

        return view('staff-bookings.pay', [
            'token' => $token,
            'booking' => $staffBooking->bookable,
            'product' => $staffBooking->productLabel(),
            'summary' => $staffBooking->summary(),
            'total' => $staffBooking->total(),
        ]);
    }

    public function start(string $token, SeerbitPaymentService $seerbit): RedirectResponse
    {
        $staffBooking = StaffBooking::where('pay_token', $token)->firstOrFail();
        $booking = $staffBooking->bookable;

        if (! $staffBooking->isAwaitingPayment()) {
            return redirect()->route('staff-bookings.pay', $token);
        }

        try {
            $result = $seerbit->initializePayment([
                'amount' => (string) $staffBooking->total(),
                'currency' => 'NGN',
                'callbackUrl' => route($staffBooking->seerbitCallbackRoute()),
                'email' => $booking->email,
                'fullName' => $booking->full_name,
                'mobileNumber' => $booking->phone_number,
                'paymentReference' => $booking->payment_reference,
                'productDescription' => trim(preg_replace('/[^A-Za-z0-9 ().,-]/', '', Str::ascii($staffBooking->productLabel()))),
                'productId' => 'PRD'.$booking->payment_reference,
            ]);

            return redirect()->away($result['redirect_link']);
        } catch (\Exception $e) {
            Log::error('SeerBit Init Exception', ['reference' => $booking->payment_reference, 'error' => $e->getMessage()]);

            return redirect()->route('staff-bookings.pay', $token)
                ->with('error', 'We could not start the payment. Please try again in a few minutes.');
        }
    }
}

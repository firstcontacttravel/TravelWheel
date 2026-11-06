<?php

namespace App\Http\Controllers;

use App\Models\InsurancePurchase;
use App\Models\InsuranceQuote;
use App\Services\BookingThankYou;
use App\Services\InsuranceBookingService;
use App\Services\SeerbitPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class InsuranceController extends Controller
{
    public function makeRequestQuote(Request $request, InsuranceBookingService $insurance)
    {
        $dataform = $request->all();

        try {
            $quote = $insurance->requestQuote([
                'dob' => $dataform['dob'],
                'email' => $dataform['email'],
                'phone_no' => $dataform['phone_no'],
                'begin_date' => $dataform['begin_date'],
                'end_date' => $dataform['end_date'],
                'country' => $dataform['country'],
                'purpose_of_travel' => $dataform['purpose_of_travel'],
                'travel_plan' => $dataform['travel_plan'],
                'booking_type' => $dataform['booking_type'],
                'nop' => $dataform['nop'] ?? 1,
                'noc' => $dataform['noc'] ?? 0,
            ]);

            return redirect()->route('air.insurance.quote.show', ['qid' => $quote->id]);
        } catch (\Exception $e) {
            Log::error('Sanla quote error: ' . $e->getMessage());
            return back()->with('error', 'Could not retrieve quote. Please try again.');
        }
    }

    public function insuranceRequest(Request $request)
    {
        $qid = $request->query('qid');
        $quote = InsuranceQuote::findOrFail($qid);

        if ((int) $quote->bookingTypeId === 2) {
            return view('air.insurance.insurancePurchaseF', compact('quote'));
        }

        return view('air.insurance.insurancePurchase', compact('quote'));
    }

    public function insurancePurchase(Request $request, InsuranceBookingService $insurance)
    {
        $dataform = $request->all();

        // The amount the form sent was only for display; the quote's is the real one
        $quote = $insurance->quote($dataform['qouteId'] ?? null);
        if (! $quote) {
            return redirect()->route('air.insurance')->with('error', 'Your quote could not be found. Please get a new quote.');
        }
        $dataform['amount'] = $insurance->price($quote)['amount'];
        $dataform['bookingTypeId'] = $quote->bookingTypeId;

        Session::put('insurance_data_form', $dataform);

        return view('air.insurance.insurancePurchaseN', compact('dataform'));
    }

    public function makeRequestPurchase(Request $request, InsuranceBookingService $insurance)
    {
        $dataform2 = $request->all();

        $dataform  = Session::get('insurance_data_form');
        if (!$dataform) {
            return back()->with('error', 'Session expired. Please rebook.');
        }

        // Charge the quote's price, never an amount the browser sent
        $quote = $insurance->quote($dataform['qouteId'] ?? null);
        if (! $quote) {
            return redirect()->route('air.insurance')->with('error', 'Your quote could not be found. Please get a new quote.');
        }
        $price = $insurance->price($quote);

        $reference = 'INS' . strtoupper(bin2hex(random_bytes(6)));
        $dataform2 = array_merge($dataform2, [
            'c_amount' => $price['amount'],
            'vat' => $price['vat'],
            'p_amount' => $price['total'],
            'reference' => $reference,
        ]);
        Session::put('insurance_data_form2', $dataform2);

        $fullname  = trim(($dataform['surname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $amount    = $price['total'];
        $phone     = preg_replace('/\D/', '', '+234' . ltrim($dataform['phone_no'] ?? '', '0'));

        $result = app(SeerbitPaymentService::class)->initializePayment([
            'amount'             => (string) $amount,
            'currency'           => 'NGN',
            'email'              => $dataform['email'] ?? '',
            'fullName'           => $fullname,
            'mobileNumber'       => $phone,
            'callbackUrl'        => route('air.insurance.callback'),
            'paymentReference'   => $reference,
            'productId'          => 'INS' . $reference,
            'productDescription' => 'Travel Insurance Booking',
        ]);

        return redirect($result['redirect_link']);
    }

    public function callbackSeerbit(Request $request, InsuranceBookingService $insurance)
    {
        // SeerBit's hosted checkout redirects back with `reference` in some modes,
        // not just `paymentReference` — check both (see ProtocolController::callbackSeerbit).
        $paymentReference = $request->query('paymentReference')
            ?? $request->input('paymentReference')
            ?? $request->query('reference')
            ?? $request->input('reference')
            ?? $request->query('payRef')
            ?? $request->input('payRef');

        if (!$paymentReference) {
            return redirect()->route('air.insurance')->with('error', 'Payment reference missing.');
        }

        $result = app(SeerbitPaymentService::class)->verifyPayment($paymentReference);
        if (!$result['ok']) {
            return redirect()->route('air.insurance')->with('error', 'Payment was not successful.');
        }

        // Already saved: a repeat of this callback, or a policy staff booked before payment
        if ($existing = InsurancePurchase::where('trans_id', $paymentReference)->first()) {
            $insurance->completePayment($existing);

            return redirect()->route('air.insurance.success');
        }

        $dataform  = Session::get('insurance_data_form');
        $dataform2 = Session::get('insurance_data_form2');

        if (!$dataform || !$dataform2) {
            return redirect()->route('air.insurance')->with('error', 'Session expired. Please rebook.');
        }

        // The forms in the session must be the ones this payment was started for
        if (isset($dataform2['reference']) && $dataform2['reference'] !== $paymentReference) {
            Log::warning('Insurance payment does not match the checkout in this session', ['reference' => $paymentReference, 'expected' => $dataform2['reference']]);

            return redirect()->route('air.insurance')->with('error', 'We could not match this payment to your booking. Please contact support and quote '.$paymentReference.'.');
        }

        try {
            $coverId = $insurance->issuePolicy($dataform, $dataform2);

            $purchase = InsurancePurchase::create($insurance->purchaseAttributes($dataform, $dataform2, $paymentReference, $coverId, 'Successful'));
            $insurance->notifyReservations($purchase);
            // Insurance sends the customer no confirmation email, so the thank-you isn't
            // triggered automatically like the other products (see SendBookingThankYou)
            app(BookingThankYou::class)->send($purchase->email, $purchase->firstname, 'insurance');

            Session::forget(['insurance_data_form', 'insurance_data_form2']);
            return redirect()->route('air.insurance.success');

        } catch (\Exception $e) {
            Log::error('Sanla booking error: ' . $e->getMessage());

            // The customer already paid via SeerBit at this point, but the Sanlam
            // policy could not be confirmed — record it as failed so ops can follow
            // up, instead of silently telling a paying customer they're covered.
            if (! InsurancePurchase::where('trans_id', $paymentReference)->exists()) {
                $purchase = InsurancePurchase::create($insurance->purchaseAttributes($dataform, $dataform2, $paymentReference, null, 'Failed'));
                $insurance->notifyReservations($purchase);
            }

            return redirect()->route('air.insurance.success')->with([
                'insurance_pending_review' => true,
                'message' => "Your payment was received, but we couldn't confirm your policy automatically. Our team has been notified and will email your policy documents shortly. If you don't hear from us within 24 hours, please contact support with reference {$paymentReference}.",
            ]);
        }
    }

    public function insuranceSuccess()
    {
        return view('air.insurance.insurancesucces');
    }
}

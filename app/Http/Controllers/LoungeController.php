<?php

namespace App\Http\Controllers;

use App\Models\Lounge;
use App\Models\LoungeBooking;
use App\Services\LoungeBookingService;
use App\Services\LoungePairCatalogueSyncService;
use App\Services\SeerbitPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LoungeController extends Controller
{

    public function lounges(Request $request)
    {
        $data = $request->all();
        $location = $data['state'] ?? null;
        $service  = $data['service'] ?? null;

        if (!$location) {
            return back()->with('error', 'Invalid location selected');
        }

        if (!in_array($service, ['Departure', 'Arrival'])) {
            return back()->with('error', 'Invalid service segment selected');
        }

        $airportType = null;

        if ($location === 'Abuja') {
            $airportType = $data['airports'] ?? null;
        } elseif ($location === 'Kano') {
            $airportType = $data['airports2'] ?? null;
        } elseif ($location === 'Lagos') {
            $airportType = $data['airports1'] ?? null;
        }

        if (!in_array($airportType, [1, 2])) {
            return back()->with('error', 'Invalid airport selection');
        }

        return redirect()->route('air.lounges.results', [
            'state'   => $location,
            'airport' => $airportType,
            'service' => $service,
            // "International"/"Local" is a lounge access category within the
            // same airport, so both airport-type options share one IATA code
            'iata'    => Lounge::LOCAL_AIRPORTS[$location]['iata'] ?? null,
        ]);
    }

    /**
     * Separate entry point for the LoungePair-backed global lounge search —
     * intentionally isolated from lounges() above so it can never affect the
     * existing state/airport/service flow or its results.
     */
    public function loungePairSearch(Request $request)
    {
        $iata = strtoupper(trim((string) $request->input('iata')));

        if (! preg_match('/^[A-Z]{3}$/', $iata)) {
            return back()->withInput()->with('error', 'Enter a valid three-letter airport IATA code, e.g. SYD, LOS, or ABV.');
        }

        Log::info('[LoungePair] search requested', ['iata' => $iata]);

        try {
            $result = app(LoungePairCatalogueSyncService::class)->sync($iata);
            Log::info('[LoungePair] search completed', ['iata' => $iata] + $result);
        } catch (\Throwable $exception) {
            Log::error('[LoungePair] search failed — showing generic error to the user', [
                'iata' => $iata,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            return back()->withInput()->with('error', 'We could not retrieve lounges for that airport right now. Please try again shortly.');
        }

        return redirect()->route('air.lounge.global.results', ['iata' => $iata]);
    }

    public function loungeBooking($id)
    {
        $lounges = Lounge::active()->where('id', $id)->get();

        if ($lounges->isEmpty()) {
            return redirect()->route('air.lounge')->with('error', 'That lounge is no longer available. Please choose another.');
        }

        return view('air.lounge.loungebooking', compact('lounges'));
    }

    public function loungecheckout(Request $request, LoungeBookingService $bookings)
    {
        $request->validate(
            ['nop' => 'required'],
            ['nop.required' => 'The No. of Person(s) field is required.']
        );

        $dataform = $request->all();

        // Admin may have disabled the lounge after the customer opened it
        $lounge = Lounge::active()->find($dataform['lounge_id'] ?? null);
        if (! $lounge) {
            return redirect()->route('air.lounge')->with('error', 'That lounge is no longer available. Please choose another.');
        }

        // The prices the form sent were only for display; these are the real ones
        $quote = $bookings->quote($lounge, (int) ($dataform['adultValue'] ?? 0), (int) ($dataform['childValue'] ?? 0), (int) ($dataform['infantValue'] ?? 0));
        if ($quote['total'] <= 0) {
            return back()->withInput()->withErrors(['nop' => 'Please add at least one adult or child.']);
        }

        $dataform = array_merge($dataform, [
            'lounge' => $lounge->brand_name,
            'amount' => $quote['subtotal'],
            'adultAmount' => $quote['adult_amount'],
            'childAmount' => $quote['child_amount'],
            'infantAmount' => $quote['infant_amount'],
        ]);

        return view('air.lounge.loungecheckout', compact('dataform'));
    }

    public function makePurchase(Request $request, LoungeBookingService $bookings)
    {
        $dataform = $request->all();

        $lounge = Lounge::active()->find($dataform['lounge_id'] ?? null);
        if (! $lounge) {
            return redirect()->route('air.lounge')->with('error', 'That lounge is no longer available. Please choose another.');
        }

        // Charge what the lounge costs, never an amount the browser sent
        $quote = $bookings->quote($lounge, (int) ($dataform['noa'] ?? 0), (int) ($dataform['noc'] ?? 0), (int) ($dataform['noi'] ?? 0));
        if ($quote['total'] <= 0) {
            return redirect()->route('air.lounge')->with('error', 'Please add at least one adult or child.');
        }

        $reference = strtoupper('LNG' . bin2hex(random_bytes(6)));
        $dataform = array_merge($dataform, [
            'lounge' => $lounge->brand_name,
            'adultAmount' => $quote['adult_amount'],
            'childAmount' => $quote['child_amount'],
            'infantAmount' => $quote['infant_amount'],
            'c_amount' => $quote['subtotal'],
            'vat' => $quote['vat'],
            'p_amount' => $quote['total'],
            'reference' => $reference,
        ]);
        Session::put('lounge_checkout_form', $dataform);

        $fullname = trim(($dataform['lastname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $amount = $quote['total'];
        $phone = preg_replace('/\D/', '', '+234' . ltrim($dataform['phone'] ?? '', '0'));

        $result = app(SeerbitPaymentService::class)->initializePayment([
            'amount'             => (string) $amount,
            'currency'           => 'NGN',
            'email'              => $dataform['email'] ?? '',
            'fullName'           => $fullname,
            'mobileNumber'       => $phone,
            'callbackUrl'        => route('air.lounge.callback'),
            'paymentReference'   => $reference,
            'productId'          => 'LNG' . $reference,
            'productDescription' => 'Airport Lounge Service',
        ]);

        return redirect($result['redirect_link']);
    }

    public function callbackSeerbit(Request $request)
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
            return redirect()->route('air.lounge')->with('error', 'Payment reference missing.');
        }

        $result = app(SeerbitPaymentService::class)->verifyPayment($paymentReference);
        if (!$result['ok']) {
            return redirect()->route('air.lounge')->with('error', 'Payment was not successful.');
        }

        // Already saved: a repeat of this callback, or a booking staff made before payment
        if ($existing = LoungeBooking::where('trans_id', $paymentReference)->first()) {
            app(LoungeBookingService::class)->completePayment($existing);

            return redirect()->route('air.lounge_payment', ['trans_id' => $paymentReference]);
        }

        $dataform = Session::get('lounge_checkout_form');
        if (!$dataform) {
            return redirect()->route('air.lounge')->with('error', 'Session expired. Please rebook.');
        }

        // The form in the session must be the one this payment was started for
        if (isset($dataform['reference']) && $dataform['reference'] !== $paymentReference) {
            Log::warning('Lounge payment does not match the checkout in this session', ['reference' => $paymentReference, 'expected' => $dataform['reference']]);

            return redirect()->route('air.lounge')->with('error', 'We could not match this payment to your booking. Please contact support and quote '.$paymentReference.'.');
        }

        $fullname = trim(($dataform['lastname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $nop      = ((int)($dataform['noa'] ?? 0)) + ((int)($dataform['noc'] ?? 0)) + ((int)($dataform['noi'] ?? 0));

        // Re-fetch the lounge server-side rather than trusting client-submitted
        // provider fields — only the id is taken from the form.
        $lounge = filled($dataform['lounge_id'] ?? null) ? Lounge::find($dataform['lounge_id']) : null;

        $booking = LoungeBooking::create([
            'lounge_id'      => $lounge?->id,
            'lounge_name'    => $dataform['lounge'] ?? '',
            'provider'       => $lounge?->provider,
            'provider_url'   => $lounge?->provider_url,
            'payment_option' => 'seerbit',
            'fullname'       => $fullname,
            'service'        => 'Lounge Service',
            'email'          => $dataform['email'] ?? '',
            'phone_no'       => $dataform['phone'] ?? '',
            'nop'            => $nop ?: (int)($dataform['nop'] ?? 1),
            'noa'            => (int)($dataform['noa'] ?? 0),
            'noc'            => (int)($dataform['noc'] ?? 0),
            'noi'            => (int)($dataform['noi'] ?? 0),
            'travel_date'    => $dataform['travel_date'] ?? now()->toDateString(),
            'ticket_no'      => $dataform['ticket_no'] ?? '',
            'd_time'         => $dataform['d_time'] ?? '',
            'amount'         => (float) str_replace(',', '', (string)($dataform['c_amount'] ?? 0)),
            'amountA'        => (float) str_replace(',', '', (string)($dataform['adultAmount'] ?? 0)),
            'amountC'        => (float) str_replace(',', '', (string)($dataform['childAmount'] ?? 0)),
            'vat'            => (float) str_replace(',', '', (string)($dataform['vat'] ?? 0)),
            'status'         => 'Successful',
            'trans_id'       => $paymentReference,
            'ref_id'         => $paymentReference,
        ]);

        app(LoungeBookingService::class)->sendConfirmations($booking, $dataform);
        Session::forget('lounge_checkout_form');

        return redirect()->route('air.lounge_payment', ['trans_id' => $paymentReference]);
    }

    public function lounge_payment($trans_id)
    {
        // A booking staff made isn't paid yet, and an unpaid one gets no pass
        $booking = LoungeBooking::where('trans_id', $trans_id)->where('status', 'Successful')->firstOrFail();
        return view('air.lounge.lounge_payment', compact('booking', 'trans_id'));
    }

    public function generateLoungePass($trans_id)
    {
        $booking = LoungeBooking::where('trans_id', $trans_id)->where('status', 'Successful')->firstOrFail();
        $pdf = Pdf::loadView('pdf.lounge-pass', compact('booking'));
        return $pdf->download("lounge-pass-{$trans_id}.pdf");
    }

    public function lounge_success()
    {
        return view('air.lounge.lounge_success');
    }
}

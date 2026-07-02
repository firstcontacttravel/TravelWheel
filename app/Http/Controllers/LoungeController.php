<?php

namespace App\Http\Controllers;

use App\Mail\LoungeBookingMail;
use App\Models\Lounge;
use App\Models\LoungeBooking;
use App\Services\SeerbitPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class LoungeController extends Controller
{
    public function lounge()
    {
        return view('air.lounge.lounge');
    }

    public function lounges(Request $request)
    {
        $data = $request->all();
        $location = $data['state'] ?? null;

        if (!$location) {
            return back()->with('error', 'Invalid location selected');
        }

        $airportType = null;
        $terminal    = null;

        if ($location === 'Abuja') {
            $airportType = $data['airports'] ?? null;
        } elseif ($location === 'Kano') {
            $airportType = $data['airports2'] ?? null;
        } elseif ($location === 'Lagos') {
            $airportType = $data['airports1'] ?? null;
            if ($airportType == 1) {
                $terminal = ($data['airline'] ?? null) == 1 ? 'New Terminal' : 'Old Terminal';
            }
        }

        if (!in_array($airportType, [1, 2])) {
            return back()->with('error', 'Invalid airport selection');
        }

        $query = Lounge::where('location', $location)->where('airport', $airportType);
        if ($terminal) {
            $query->where('terminal', $terminal);
        }

        $lounges = $query->latest()->get();

        return view('air.lounge.lounges', compact('lounges'));
    }

    public function loungePlan($id)
    {
        $lounges = Lounge::where('id', $id)->get();
        return view('air.lounge.loungeplans', compact('lounges'));
    }

    public function loungeBooking($id)
    {
        $lounges = Lounge::where('id', $id)->get();
        return view('air.lounge.loungebooking', compact('lounges'));
    }

    public function loungecheckout(Request $request)
    {
        $request->validate(
            ['nop' => 'required'],
            ['nop.required' => 'The No. of Person(s) field is required.']
        );

        $dataform = $request->all();

        $airlineKey = $dataform['airport'] === 'International' ? 'airline1' : 'airline2';
        $otherKey   = $dataform['airport'] === 'International' ? 'other1'   : 'other2';
        $airline    = $dataform[$airlineKey] ?? null;
        if ($airline === 'OTHERS') {
            $airline = $dataform[$otherKey] ?? null;
        }
        $dataform['airline'] = $airline;

        return view('air.lounge.loungecheckout', compact('dataform'));
    }

    public function makePurchase(Request $request)
    {
        $dataform  = $request->all();
        Session::put('lounge_checkout_form', $dataform);

        $fullname  = trim(($dataform['lastname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $reference = strtoupper('LNG' . bin2hex(random_bytes(6)));
        $amount    = (float) str_replace(',', '', (string) ($dataform['p_amount'] ?? 0));
        $phone     = preg_replace('/\D/', '', '+234' . ltrim($dataform['phone'] ?? '', '0'));

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->initializePayment([
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
        $paymentReference = $request->query('paymentReference') ?? $request->input('paymentReference');
        if (!$paymentReference) {
            return redirect()->route('air.lounge')->with('error', 'Payment reference missing.');
        }

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->verifyPayment($paymentReference);
        if (!$result['ok']) {
            return redirect()->route('air.lounge')->with('error', 'Payment was not successful.');
        }

        if (LoungeBooking::where('trans_id', $paymentReference)->exists()) {
            return redirect()->route('air.lounge_payment', ['trans_id' => $paymentReference]);
        }

        $dataform = Session::get('lounge_checkout_form');
        if (!$dataform) {
            return redirect()->route('air.lounge')->with('error', 'Session expired. Please rebook.');
        }

        $fullname = trim(($dataform['lastname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $nop      = ((int)($dataform['noa'] ?? 0)) + ((int)($dataform['noc'] ?? 0)) + ((int)($dataform['noi'] ?? 0));

        LoungeBooking::create([
            'lounge_name'    => $dataform['lounge'] ?? '',
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
            'airline'        => $dataform['airline'] ?? '',
            'd_time'         => $dataform['d_time'] ?? '',
            'amount'         => (float) str_replace(',', '', (string)($dataform['c_amount'] ?? 0)),
            'amountA'        => (float) str_replace(',', '', (string)($dataform['adultAmount'] ?? 0)),
            'amountC'        => (float) str_replace(',', '', (string)($dataform['childAmount'] ?? 0)),
            'vat'            => (float) str_replace(',', '', (string)($dataform['vat'] ?? 0)),
            'status'         => 'Successful',
            'trans_id'       => $paymentReference,
            'ref_id'         => $paymentReference,
        ]);

        Mail::to($dataform['email'] ?? '')->send(
            new LoungeBookingMail($fullname, $dataform['email'] ?? '')
        );
        Session::forget('lounge_checkout_form');

        return redirect()->route('air.lounge_payment', ['trans_id' => $paymentReference]);
    }

    public function lounge_payment($trans_id)
    {
        $booking = LoungeBooking::where('trans_id', $trans_id)->firstOrFail();
        return view('air.lounge.lounge_payment', compact('booking', 'trans_id'));
    }

    public function generateLoungePass($trans_id)
    {
        $booking = LoungeBooking::where('trans_id', $trans_id)->firstOrFail();
        $pdf = Pdf::loadView('pdf.lounge-pass', compact('booking'));
        return $pdf->download("lounge-pass-{$trans_id}.pdf");
    }

    public function lounge_success()
    {
        return view('air.lounge.lounge_success');
    }
}

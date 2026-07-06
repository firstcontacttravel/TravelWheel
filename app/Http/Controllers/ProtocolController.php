<?php

namespace App\Http\Controllers;

use App\Mail\ProtocolBookingMail;
use App\Models\Protocol;
use App\Models\ProtocolBooking;
use App\Services\SeerbitPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class ProtocolController extends Controller
{
    public function protocol()
    {
        return view('air.protocol.protocol');
    }

    public function protocolPlan(Request $request)
    {
        $request->validate([ 
            'location' => 'required|string|max:255',
            'airport'  => 'required|string|max:255',
            'service'  => 'required|string|max:255',
        ]);

        $data = $request->only(['location', 'airport', 'service']);
        Session::put('protocol_data', $data);

        $airportKey = $data['airport'] === 'International Airport' ? 'International' : 'Local';
        $protocol = Protocol::where('location', $data['location'])
            ->where('airport', $airportKey)
            ->where('service', $data['service'])
            ->latest()->first();

        if (! $protocol) {
            return back()->with('error', 'No protocol service available for the selected location. Please contact us.');
        }

        if ($data['airport'] === 'International Airport') {
            return redirect()->route('air.protocolplansI', ['id' => $protocol->id]);
        }

        return redirect()->route('air.protocolplans', ['id' => $protocol->id]);
    }

    public function protocolPlans($id)
    {
        $protocol = Protocol::findOrFail($id);
        return view('air.protocol.protocolplans', [
            'price1' => $protocol->price1,
            'price2' => $protocol->price2,
        ]);
    }

    public function protocolPlansI($id)
    {
        $protocol = Protocol::findOrFail($id);
        return view('air.protocol.protocolInternational', [
            'price1' => $protocol->price1,
            'price2' => $protocol->price2,
        ]);
    }

    public function protocolForm(Request $request, $plan)
    {
        $data = Session::get('protocol_data');

        if (! $data) {
            return redirect()->route('air.protocol');
        }

        $airportKey = $data['airport'] === 'International Airport' ? 'International' : 'Local';
        $protocol = Protocol::where('location', $data['location'])
            ->where('airport', $airportKey)
            ->where('service', $data['service'])
            ->latest()->first();

        if (! $protocol) {
            return redirect()->route('air.protocol');
        }

        $price = $plan == '1' ? $protocol->price1 : $protocol->price2;

        return view('air.protocol.protocolForm', compact('data', 'price'));
    }

    public function protocol_checkout(Request $request)
    {
        $dataform = $request->all();

        $optinal_request  = 'None';
        $optionalVehicle  = 'None';
        $seaters          = 'None';

        $vehicleMap = [
            'Up to 3 Seaters'  => 'Saloon Comfort',
            'Up to 3  Seaters' => 'SUV Business',
            'Up to 5 Seaters'  => 'Mini Van',
        ];

        $optionalFields = [
            ['request' => 'optinal_requestA',  'price' => 'optionalPriceA'],
            ['request' => 'optinal_requestD',  'price' => 'optionalPriceD'],
            ['request' => 'optinal_requestA2', 'price' => 'optionalPriceA2'],
            ['request' => 'optinal_requestD2', 'price' => 'optionalPriceD2'],
        ];

        foreach ($optionalFields as $field) {
            if (! empty($dataform[$field['request']])) {
                $optinal_request = $dataform[$field['request']];
                $seaters         = $dataform[$field['price']] ?? 'None';
                $optionalVehicle = $vehicleMap[$seaters] ?? 'None';
                break;
            }
        }

        return view('air.protocol.protocol_checkout', compact('dataform', 'optinal_request', 'optionalVehicle', 'seaters'));
    }

    public function makePurchase(Request $request)
    {
        $dataform = $request->all();

        $nop             = (int) ($dataform['no_of_passenger'] ?? 1);
        $destinationPath = public_path('assets/means_id');

        if (! File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        for ($i = 1; $i <= $nop; $i++) {
            $fileKey = "means-id{$i}";
            if ($request->hasFile($fileKey)) {
                $fn = $nop === 1
                    ? (($dataform['lastname1'] ?? '') . ($dataform['firstname1'] ?? '') . '_' . time() . '.jpg')
                    : (($dataform["firstname{$i}"] ?? $i) . '_' . time() . '.jpg');

                $request->file($fileKey)->move($destinationPath, $fn);
                $dataform[$nop === 1 ? 'means_id_image' : "means_id_image_passenger{$i}"] = $fn;
                unset($dataform[$fileKey]);
            }
        }

        Session::put('protocol_checkout_form', $dataform);

        $fullname  = trim(($dataform['lastname1'] ?? '') . ' ' . ($dataform['firstname1'] ?? ''));
        $reference = strtoupper('PROT' . bin2hex(random_bytes(6)));
        $amount    = (float) str_replace(',', '', (string) ($dataform['p_amount'] ?? 0));
        $phone     = preg_replace('/\D/', '', ($dataform['country_code'] ?? '+234') . ($dataform['phone'] ?? ''));

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->initializePayment([
            'amount'             => (string) $amount,
            'currency'           => 'NGN',
            'email'              => $dataform['email'] ?? '',
            'fullName'           => $fullname,
            'mobileNumber'       => $phone,
            'callbackUrl'        => route('air.protocol.callback'),
            'paymentReference'   => $reference,
            'productId'          => 'PROT' . $reference,
            'productDescription' => 'Airport Protocol Service',
        ]);

        return redirect($result['redirect_link']);
    }

    public function callbackSeerbit(Request $request)
    {
        // SeerBit's hosted checkout redirects back with `reference` (confirmed against the
        // working SeerbitNormalizer integration elsewhere in the codebase), not `paymentReference`.
        // Our own initializePayment() call also echoes `paymentReference`/`payref` in some modes,
        // so all variants are checked here.
        $paymentReference = $request->query('paymentReference')
            ?? $request->input('paymentReference')
            ?? $request->query('reference')
            ?? $request->input('reference')
            ?? $request->query('payRef')
            ?? $request->input('payRef');

        if (! $paymentReference) {
            return redirect()->route('air.protocol')->with('error', 'Payment reference missing.');
        }

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->verifyPayment($paymentReference);

        if (! $result['ok']) {
            return redirect()->route('air.protocol')->with('error', 'Payment was not successful. Please try again.');
        }

        $dataform = Session::get('protocol_checkout_form');

        if (! $dataform) {
            return redirect()->route('air.protocol')->with('error', 'Session expired. Please rebook.');
        }

        $nop       = (int) ($dataform['no_of_passenger'] ?? 1);
        $fullnames = [];
        $pnrs      = [];
        $ticketNos = [];
        $nobs      = [];

        for ($i = 1; $i <= $nop; $i++) {
            $fullnames[]  = trim(($dataform["lastname{$i}"] ?? '') . ' ' . ($dataform["firstname{$i}"] ?? ''));
            $pnrs[]       = $dataform["pnr{$i}"] ?? '';
            $ticketNos[]  = $dataform["ticket-no{$i}"] ?? '';
            $nobs[]       = $dataform["nobs{$i}"] ?? '';
        }

        $meansId  = $nop === 1
            ? ($dataform['means_id_image'] ?? '')
            : ($dataform['means_id_image_passenger1'] ?? '');

        $fullname = $nop === 1 ? $fullnames[0] : $fullnames[0];

        ProtocolBooking::create([
            'paymentoption'          => 'seerbit',
            'fullname'               => $nop === 1 ? [$fullnames[0]] : $fullnames,
            'package'                => $dataform['package'] ?? '',
            'service'                => 'Protocol Service',
            'passenger'              => $nop,
            'email'                  => $dataform['email'] ?? '',
            'phone'                  => $dataform['phone'] ?? '',
            'travel_date'            => $dataform['travel_date'] ?? null,
            'state'                  => $dataform['state'] ?? '',
            'airline'                => $dataform['airline'] ?? '',
            'airport'                => $dataform['airport'] ?? '',
            'd_time'                 => $dataform['d_time'] ?? '',
            'service_type'           => $dataform['service'] ?? '',
            'status'                 => 'Successful',
            'amount'                 => (float) str_replace(',', '', (string) ($dataform['c_amount'] ?? 0)),
            'vat'                    => (float) ($dataform['vat'] ?? 0),
            'optional_request'       => $dataform['optional_request'] ?? 'None',
            'optionalRequestOption'  => $dataform['pickUpVehicle'] ?? $dataform['dropOffVehicle'] ?? null,
            'optionalRequestAddress' => $dataform['pickUpAddress'] ?? $dataform['dropOffAddress'] ?? null,
            'reservationCode'        => $pnrs,
            'eTicketNo'              => $ticketNos,
            'noOfBags'               => $nobs,
            'nextOfKin_fullname'     => $dataform['nextOfKin_fullname'] ?? null,
            'nextOfKin_phone'        => $dataform['nextOfKin_phone'] ?? null,
            'means_id'               => $meansId,
            'trans_id'               => $paymentReference,
            'ref_id'                 => $paymentReference,
        ]);

        $amount = (float) str_replace(',', '', (string) ($dataform['c_amount'] ?? 0));
        Mail::to($dataform['email'] ?? '')->send(new ProtocolBookingMail($fullname, $amount, $paymentReference));

        Session::forget('protocol_checkout_form');

        return redirect()->route('air.protocol_payment', ['trans_id' => $paymentReference]);
    }

    public function protocol_payment($trans_id)
    {
        $protocols = ProtocolBooking::where('trans_id', $trans_id)->firstOrFail();
        return view('air.protocol.protocol_payment', compact('protocols'));
    }

    public function generateProtocolPass($trans_id)
    {
        $booking   = ProtocolBooking::where('trans_id', $trans_id)->firstOrFail();
        $fullnames = is_array($booking->fullname) ? $booking->fullname : [$booking->fullname];
        $pnrs      = is_array($booking->reservationCode) ? $booking->reservationCode : [$booking->reservationCode];
        $ticketNos = is_array($booking->eTicketNo) ? $booking->eTicketNo : [$booking->eTicketNo];
        $nobs      = is_array($booking->noOfBags) ? $booking->noOfBags : [$booking->noOfBags];
        $package   = $booking->package == '2' ? 'Regular' : 'VIP';

        $pdf = Pdf::loadView('pdf.protocol-pass', compact('booking', 'fullnames', 'pnrs', 'ticketNos', 'nobs', 'package'));

        return $pdf->download("protocol-pass-{$trans_id}.pdf");
    }

    public function protocol_success()
    {
        return view('air.protocol.protocol_success');
    }
}

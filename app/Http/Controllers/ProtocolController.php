<?php

namespace App\Http\Controllers;

use App\Models\Protocol;
use App\Models\ProtocolBooking;
use App\Services\ProtocolBookingService;
use App\Services\SeerbitPaymentService;
use App\Support\ProtocolAirlines;
use App\Support\ProtocolVehicles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

        $airlines = ProtocolAirlines::for($data['location'], $data['airport'] === 'International Airport');

        return view('air.protocol.protocolForm', compact('data', 'price', 'plan', 'airlines'));
    }

    public function protocol_checkout(Request $request, ProtocolBookingService $bookings)
    {
        $dataform = $request->all();

        // The amount the form sent was only for display; this is the real one
        $protocol = $bookings->find($dataform['location'] ?? null, $dataform['airport'] ?? null, $dataform['service'] ?? null);
        $quote = $protocol ? $bookings->quote($protocol, (string) ($dataform['plan'] ?? ''), (int) ($dataform['nop'] ?? 0)) : null;
        if (! $quote || $quote['total'] <= 0) {
            return back()->withInput()->with('error', 'That protocol service is not available for this selection. Please check the plan and number of persons.');
        }
        $dataform['amount'] = $quote['subtotal'];

        $optinal_request  = 'None';
        $optionalVehicle  = 'None';
        $seaters          = 'None';
        $vehiclePrice     = null;
        $vehicleDirection = null; // 'Pick-Up' or 'Drop-Off' when the request needs a vehicle

        // Only the select for the chosen airport type and service is filled in
        foreach (['A', 'D', 'A2', 'D2'] as $suffix) {
            if (empty($dataform['optinal_request'.$suffix])) {
                continue;
            }

            $optinal_request = $dataform['optinal_request'.$suffix];

            if (ProtocolVehicles::needsVehicle($optinal_request)) {
                $vehicleDirection = str_contains($optinal_request, 'Pick-up') ? 'Pick-Up' : 'Drop-Off';
                $vehicle = ProtocolVehicles::find($dataform['optional_vehicle'.$suffix] ?? null);

                if (! $vehicle) {
                    return back()->withInput()->with('error', 'Please choose a vehicle for your '.strtolower($vehicleDirection).' request.');
                }

                $optionalVehicle = $vehicle['name'];
                $seaters         = 'Up to '.$vehicle['seats'].' Seaters';
                $vehiclePrice    = $vehicle['price'];
            }
            break;
        }

        return view('air.protocol.protocol_checkout', compact('dataform', 'optinal_request', 'optionalVehicle', 'seaters', 'vehiclePrice', 'vehicleDirection'));
    }

    public function makePurchase(Request $request, ProtocolBookingService $bookings)
    {
        $dataform = $request->all();

        // Charge what the protocol costs, never an amount the browser sent
        $protocol = $bookings->find($dataform['state'] ?? null, $dataform['airport'] ?? null, $dataform['service'] ?? null);
        $quote = $protocol ? $bookings->quote($protocol, (string) ($dataform['package'] ?? ''), (int) ($dataform['no_of_passenger'] ?? 0)) : null;
        if (! $quote || $quote['total'] <= 0) {
            return redirect()->route('air.protocol')->with('error', 'That protocol service is not available for this selection. Please start again.');
        }

        $nop             = (int) $dataform['no_of_passenger'];
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

        $reference = strtoupper('PROT' . bin2hex(random_bytes(6)));
        $dataform = array_merge($dataform, [
            'c_amount' => $quote['subtotal'],
            'vat' => $quote['vat'],
            'p_amount' => $quote['total'],
            'reference' => $reference,
        ]);
        Session::put('protocol_checkout_form', $dataform);

        $fullname  = trim(($dataform['lastname1'] ?? '') . ' ' . ($dataform['firstname1'] ?? ''));
        $amount    = $quote['total'];
        $phone     = preg_replace('/\D/', '', ($dataform['country_code'] ?? '+234') . ($dataform['phone'] ?? ''));

        $result = app(SeerbitPaymentService::class)->initializePayment([
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

        $result = app(SeerbitPaymentService::class)->verifyPayment($paymentReference);

        if (! $result['ok']) {
            return redirect()->route('air.protocol')->with('error', 'Payment was not successful. Please try again.');
        }

        // Already saved: a repeat of this callback, or a booking staff made before payment
        if ($existing = ProtocolBooking::where('trans_id', $paymentReference)->first()) {
            app(ProtocolBookingService::class)->completePayment($existing);

            return redirect()->route('air.protocol_payment', ['trans_id' => $paymentReference]);
        }

        $dataform = Session::get('protocol_checkout_form');

        if (! $dataform) {
            return redirect()->route('air.protocol')->with('error', 'Session expired. Please rebook.');
        }

        // The form in the session must be the one this payment was started for
        if (isset($dataform['reference']) && $dataform['reference'] !== $paymentReference) {
            Log::warning('Protocol payment does not match the checkout in this session', ['reference' => $paymentReference, 'expected' => $dataform['reference']]);

            return redirect()->route('air.protocol')->with('error', 'We could not match this payment to your booking. Please contact support and quote '.$paymentReference.'.');
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

        $booking = ProtocolBooking::create([
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
            'optionalRequestOption'  => $this->vehicleSummary($dataform),
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

        app(ProtocolBookingService::class)->sendConfirmation($booking);

        Session::forget('protocol_checkout_form');

        return redirect()->route('air.protocol_payment', ['trans_id' => $paymentReference]);
    }

    public function protocol_payment($trans_id)
    {
        // A booking staff made isn't paid yet, and an unpaid one gets no pass
        $protocols = ProtocolBooking::where('trans_id', $trans_id)->where('status', 'Successful')->firstOrFail();
        return view('air.protocol.protocol_payment', compact('protocols'));
    }

    public function generateProtocolPass($trans_id)
    {
        $booking   = ProtocolBooking::where('trans_id', $trans_id)->where('status', 'Successful')->firstOrFail();
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

    /** e.g. "Saloon Comfort (Up to 3 Seaters) - ₦25,000, not included in protocol amount" */
    private function vehicleSummary(array $dataform): ?string
    {
        $vehicle = $dataform['pickUpVehicle'] ?? $dataform['dropOffVehicle'] ?? null;
        if (blank($vehicle)) {
            return null;
        }

        $price = filled($dataform['vehicle_price'] ?? null) ? (float) $dataform['vehicle_price'] : null;

        return ProtocolVehicles::summary($vehicle, $dataform['seaters'] ?? null, $price);
    }
}

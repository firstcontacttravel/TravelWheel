<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AirCargoModel;
use App\Models\ShippingZone;
use App\Models\CargoDocumentPrice;
use App\Models\CargoPackagePrice;
use App\Mail\ShipmentMail;
use App\Services\SeerbitPaymentService;

class AirCargoController extends Controller
{
    public function airCargo()
    {
        return view('air.air_cargo.cargo');
    }

    public function airCargoInternational()
    {
        return view('air.air_cargo.createship');
    }

    public function getShippingPrice(Request $request)
    {
        $zone = ShippingZone::where('zone_name', $request->input('zone'))->first();
        if (!$zone) {
            return response()->json(['message' => 'Invalid zone provided'], 400);
        }

        $weight        = $request->input('weight');
        $shippingType  = $request->input('service');
        $price         = null;

        if ($shippingType === 'Document') {
            $price = CargoDocumentPrice::where('zone_id', $zone->id)->value($weight);
        } elseif ($shippingType === 'Package') {
            $price = CargoPackagePrice::where('zone_id', $zone->id)->value($weight);
        }

        if ($price !== null) {
            return response()->json(['price' => $price]);
        }

        return response()->json(['message' => 'Price not found for the given parameters'], 404);
    }

    public function getShippingZones()
    {
        return response()->json(ShippingZone::all());
    }

    public function airCargoPost(Request $request)
    {
        $dataform     = $request->input();
        $date         = Carbon::now()->format('md');
        $shippingId   = 'TWAC07' . $date . Str::random(2);
        $shippingType = $dataform['shipment_type'];

        if ($shippingType === 'Document') {
            $shippingPrice = str_replace(',', '', $dataform['price']);
            $shipPrice     = (int) $shippingPrice;

            $data = [
                'shipping_id'      => $shippingId,
                'shipping_price'   => $shipPrice,
                'pickup_price'     => $dataform['pick-Upshipment'] ?? null,
                'total_price'      => $dataform['totalPrice'],
                'sender_name'      => $dataform['fullname'],
                'sender_address'   => $dataform['address1'],
                'sender_email'     => $dataform['email'],
                'sender_phone'     => $dataform['phone_no'],
                'sender_country'   => $dataform['sCountry'] ?? '',
                'sender_postalCode'=> $dataform['postalcode1'],
                'receiver_name'    => $dataform['fullname2'],
                'receiver_address' => $dataform['address2'],
                'receiver_email'   => $dataform['email2'],
                'receiver_phone'   => $dataform['contact2'],
                'receiver_country' => $dataform['rCountry'] ?? '',
                'receiver_postalCode' => $dataform['postalcode2'] ?? '',
                'shipment_type'    => $shippingType,
                'document_type'    => $dataform['typeOfDoc'],
                'document_weight'  => $dataform['doc_weight'],
                'pickup_address'   => $dataform['pick_upAddress'] ?? null,
                'pickup_busstop'   => $dataform['nBus_stop'] ?? null,
                'pickup_date'      => $dataform['pick_upDate'] ?? null,
                'dropoff_date'     => $dataform['dropoff_date'] ?? null,
            ];

            $pdf      = Pdf::loadView('air.air_cargo.shipment2', $data);
            $filename = $shippingId . '.pdf';
            Storage::put('public/shipments/' . $filename, $pdf->output());

            if ($request->hasFile('preview')) {
                $previewImage = $shippingId . '.jpg';
                $request->file('preview')->move(public_path('assets/aircargo'), $previewImage);
            } else {
                $previewImage = null;
            }
        } else {
            $shippingPrice = str_replace(',', '', $dataform['price2']);
            $shipPrice     = (int) $shippingPrice;
            $pVolume       = 'Length: ' . ($dataform['length'] ?? '') . ' | Width: ' . ($dataform['width'] ?? '') . ' | Height: ' . ($dataform['height'] ?? '');

            $data = [
                'shipping_id'        => $shippingId,
                'shipping_price'     => $shipPrice,
                'pickup_price'       => $dataform['pick-Upshipment'] ?? null,
                'total_price'        => $dataform['totalPrice2'],
                'sender_name'        => $dataform['fullname'],
                'sender_address'     => $dataform['address1'],
                'sender_email'       => $dataform['email'],
                'sender_phone'       => $dataform['phone_no'],
                'sender_country'     => $dataform['sCountry'] ?? '',
                'sender_postalCode'  => $dataform['postalcode1'],
                'receiver_name'      => $dataform['fullname2'],
                'receiver_address'   => $dataform['address2'],
                'receiver_email'     => $dataform['email2'],
                'receiver_phone'     => $dataform['contact2'],
                'receiver_country'   => $dataform['rCountry'] ?? '',
                'receiver_postalCode'=> $dataform['postalcode2'] ?? '',
                'shipment_type'      => $shippingType,
                'pDescription'       => $dataform['pDescription'],
                'package_weight'     => $dataform['package_weight'],
                'package_volumetric' => $pVolume,
                'pickup_address'     => $dataform['pick_upAddress'] ?? null,
                'pickup_busstop'     => $dataform['nBus_stop'] ?? null,
                'pickup_date'        => $dataform['pick_upDate'] ?? null,
                'dropoff_date'       => $dataform['dropoff_date'] ?? null,
            ];

            $pdf      = Pdf::loadView('air.air_cargo.shipment2', $data);
            $filename = $shippingId . '.pdf';
            Storage::put('public/shipments/' . $filename, $pdf->output());

            if ($request->hasFile('pPreview')) {
                $previewImage = $shippingId . '.jpg';
                $request->file('pPreview')->move(public_path('assets/aircargo'), $previewImage);
            } else {
                $previewImage = null;
            }
        }

        AirCargoModel::create([
            'shipping_id'      => $shippingId,
            'fullname'         => $dataform['fullname'],
            'email'            => $dataform['email'],
            'phone'            => $dataform['phone_no'],
            'shipping_to'      => $dataform['rCountry'] ?? ($dataform['country1'] ?? ''),
            'shipment_type'    => $shippingType,
            'shipment_preview' => $previewImage,
            'shipment_details' => $filename,
            'price'            => $shippingPrice,
            'total_price'      => $data['total_price'],
            'payment_status'   => 'Pending',
        ]);

        Session::put('shipData', $data);

        $reference = 'ACG' . strtoupper(bin2hex(random_bytes(6)));
        $seerbit   = app(SeerbitPaymentService::class);
        $result    = $seerbit->initializePayment([
            'amount'             => $data['total_price'],
            'callbackUrl'        => route('air.cargo.callback'),
            'email'              => $dataform['email'],
            'client_name'        => $dataform['fullname'],
            'paymentReference'   => $reference,
            'productDescription' => 'Air Cargo Shipment',
            'productId'          => $reference,
        ]);

        return redirect()->away($result['redirect_link']);
    }

    public function callbackSeerbit(Request $request)
    {
        $paymentReference = $request->input('paymentReference');
        $shipData         = Session::get('shipData');

        if (!$shipData) {
            return redirect()->route('air.cargo.success');
        }

        $seerbit = app(SeerbitPaymentService::class);
        $verify  = $seerbit->verifyPayment($paymentReference);

        if (!($verify['ok'] ?? false)) {
            return redirect()->route('air.cargo')->with('error', 'Payment verification failed. Please contact support.');
        }

        $shippingId = $shipData['shipping_id'];
        $shipment   = AirCargoModel::where('shipping_id', $shippingId)->first();

        if ($shipment && $shipment->payment_status !== 'successful') {
            $shipment->payment_status  = 'successful';
            $shipment->transaction_ref = $paymentReference;
            $shipment->save();

            try {
                Mail::to($shipment->email)->send(new ShipmentMail($shipment, $shipment->shipment_details));
            } catch (\Exception $e) {
                Log::error('Cargo email failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('air.cargo.success')->with('data', $shipData);
    }

    public function airCargoSuccess()
    {
        return view('air.air_cargo.shipmentsucess');
    }
}

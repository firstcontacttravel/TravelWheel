<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\AirCargoModel;
use App\Models\ShippingZone;
use App\Models\CargoDocumentPrice;
use App\Models\CargoPackagePrice;
use App\Services\AirCargoBookingService;
use App\Services\SeerbitPaymentService;
use App\Support\CargoCountries;

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
        // The country dropdowns send a zone key like "zone_2" (from each option's
        // data-category attribute), while shipping_zones.zone_name is stored as "Zone 2".
        $zoneNumber = preg_replace('/\D+/', '', (string) $request->input('zone'));
        $zone = ShippingZone::where('zone_name', 'Zone ' . $zoneNumber)->first();
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

    public function airCargoPost(Request $request, AirCargoBookingService $cargo)
    {
        $dataform     = $request->input();
        $shippingId   = $cargo->newShippingId();
        $shippingType = $dataform['shipment_type'] ?? null;

        // Charge what the price tables say, never an amount the browser sent
        $weight = $shippingType === 'Document' ? ($dataform['doc_weight'] ?? null) : ($dataform['package_weight'] ?? null);
        $quote = $cargo->quote($shippingType, $dataform['country1'] ?? null, $weight, $dataform['nBus_stop'] ?? null);
        if (! $quote) {
            return back()->withInput()->with('error', 'We could not price this shipment. Please check the destination, shipment type and weight.');
        }
        $shippingPrice = $quote['shipping'];
        $receiverCountry = CargoCountries::find($dataform['country1'])['name'];

        if ($shippingType === 'Document') {
            $shipPrice     = $shippingPrice;

            $data = [
                'shipping_id'      => $shippingId,
                'shipping_price'   => $shipPrice,
                'pickup_price'     => $quote['pickup'] ?: null,
                'total_price'      => $quote['total'],
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
                'receiver_country' => $receiverCountry,
                'receiver_postalCode' => $dataform['postalcode2'] ?? '',
                'shipment_type'    => $shippingType,
                'document_type'    => $dataform['typeOfDoc'],
                'document_weight'  => $dataform['doc_weight'],
                'pickup_address'   => $dataform['pick_upAddress'] ?? null,
                'pickup_busstop'   => $dataform['nBus_stop'] ?? null,
                'pickup_date'      => $dataform['pick_upDate'] ?? null,
                'dropoff_date'     => $dataform['dropoff_date'] ?? null,
            ];

            $filename = $cargo->saveShipmentDocument($data);

            if ($request->hasFile('preview')) {
                $previewFile  = $request->file('preview');
                $extension    = $this->normalizePreviewExtension($previewFile->getClientOriginalExtension());
                $previewImage = $shippingId . '.' . $extension;
                $previewFile->move(public_path('assets/aircargo'), $previewImage);
            } else {
                $previewImage = null;
            }
        } else {
            $shipPrice     = $shippingPrice;
            $pVolume       = 'Length: ' . ($dataform['length'] ?? '') . ' | Width: ' . ($dataform['width'] ?? '') . ' | Height: ' . ($dataform['height'] ?? '');

            $data = [
                'shipping_id'        => $shippingId,
                'shipping_price'     => $shipPrice,
                'pickup_price'       => $quote['pickup'] ?: null,
                'total_price'        => $quote['total'],
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
                'receiver_country'   => $receiverCountry,
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

            $filename = $cargo->saveShipmentDocument($data);

            if ($request->hasFile('pPreview')) {
                $previewFile  = $request->file('pPreview');
                $extension    = $this->normalizePreviewExtension($previewFile->getClientOriginalExtension());
                $previewImage = $shippingId . '.' . $extension;
                $previewFile->move(public_path('assets/aircargo'), $previewImage);
            } else {
                $previewImage = null;
            }
        }

        // Kept on the shipment before payment, so a payment can only ever complete this shipment
        $reference = $cargo->newReference();

        AirCargoModel::create([
            'shipping_id'      => $shippingId,
            'fullname'         => $dataform['fullname'],
            'email'            => $dataform['email'],
            'phone'            => $dataform['phone_no'],
            'shipping_to'      => $receiverCountry,
            'shipment_type'    => $shippingType,
            'shipment_preview' => $previewImage,
            'shipment_details' => $filename,
            'price'            => $shippingPrice,
            'total_price'      => $data['total_price'],
            'payment_status'   => 'Pending',
            'transaction_ref'  => $reference,
        ]);

        Session::put('shipData', $data + ['reference' => $reference]);

        $seerbit   = app(SeerbitPaymentService::class);
        $result    = $seerbit->initializePayment([
            'amount'             => (string) $data['total_price'],
            'currency'           => 'NGN',
            'callbackUrl'        => route('air.cargo.callback'),
            'email'              => $dataform['email'],
            'fullName'           => $dataform['fullname'],
            'mobileNumber'       => $dataform['phone_no'] ?? '',
            'paymentReference'   => $reference,
            'productDescription' => 'Air Cargo Shipment',
            'productId'          => 'ACG' . $reference,
        ]);

        return redirect()->away($result['redirect_link']);
    }

    private function normalizePreviewExtension(?string $extension): string
    {
        $extension = strtolower((string) $extension);

        return in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true) ? $extension : 'jpg';
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
            return redirect()->route('air.cargo')->with('error', 'Payment reference missing.');
        }

        $seerbit = app(SeerbitPaymentService::class);
        $verify  = $seerbit->verifyPayment($paymentReference);

        if (!($verify['ok'] ?? false)) {
            return redirect()->route('air.cargo')->with('error', 'Payment verification failed. Please contact support.');
        }

        // The shipment this payment was started for: the website's or one staff made
        $shipData = Session::get('shipData');
        $shipment = AirCargoModel::where('transaction_ref', $paymentReference)->first();

        // A shipment started before references were kept has none yet
        if (! $shipment && $shipData && ! isset($shipData['reference'])) {
            $shipment = AirCargoModel::where('shipping_id', $shipData['shipping_id'])->whereNull('transaction_ref')->first();
            $shipment?->update(['transaction_ref' => $paymentReference]);
        }

        if (! $shipment) {
            Log::warning('Cargo payment matches no shipment', ['reference' => $paymentReference]);

            return redirect()->route('air.cargo')->with('error', 'We could not match this payment to a shipment. Please contact support and quote '.$paymentReference.'.');
        }

        app(AirCargoBookingService::class)->completePayment($shipment);

        return redirect()->route('air.cargo.success')->with('data', $shipData);
    }

    public function airCargoSuccess()
    {
        return view('air.air_cargo.shipmentsucess');
    }
}

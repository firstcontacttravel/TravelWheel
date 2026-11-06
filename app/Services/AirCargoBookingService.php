<?php

namespace App\Services;

use App\Mail\ShipmentMail;
use App\Models\AirCargoModel;
use App\Models\CargoDocumentPrice;
use App\Models\CargoPackagePrice;
use App\Models\ShippingZone;
use App\Support\CargoCountries;
use App\Support\CargoPickupAreas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pricing and completing air cargo shipments, whichever way they arrive: the
 * public shipment form or staff booking from the admin. The price is always
 * worked out here from the price tables, never taken from the request.
 */
class AirCargoBookingService
{
    /** Weight in kg as the form offers it => its column in the price tables. */
    public const WEIGHTS = [
        '0.5' => 'weight_0_5', '1' => 'weight_1_0', '1.5' => 'weight_1_5', '2' => 'weight_2_0', '2.5' => 'weight_2_5',
        '3' => 'weight_3_0', '3.5' => 'weight_3_5', '4' => 'weight_4_0', '4.5' => 'weight_4_5', '5' => 'weight_5_0',
    ];

    public const TYPES = ['Document', 'Package'];

    public const DROP_OFF_ADDRESS = '74, Ayanguran Road, Ikorodu Garage, Ikorodu, Lagos';

    /** The shipping price for this type, destination and weight, or null when there isn't one. */
    public function shippingPrice(?string $type, ?string $countryCode, ?string $weight): ?int
    {
        $country = CargoCountries::find($countryCode);
        $column = self::WEIGHTS[(string) $weight] ?? null;
        $table = match ($type) {
            'Document' => CargoDocumentPrice::class,
            'Package' => CargoPackagePrice::class,
            default => null,
        };

        if (! $country || ! $column || ! $table) {
            return null;
        }

        $zone = ShippingZone::where('zone_name', 'Zone '.$country['zone'])->first();
        $price = $zone ? $table::where('zone_id', $zone->id)->value($column) : null;

        return $price > 0 ? (int) $price : null;
    }

    /**
     * Null when there's no price for the shipment. A pick-up area that isn't on
     * the list costs nothing here, as on the website; support arrange it.
     *
     * @return array{shipping: int, pickup: int, total: int}|null
     */
    public function quote(?string $type, ?string $countryCode, ?string $weight, ?string $pickupArea = null): ?array
    {
        $shipping = $this->shippingPrice($type, $countryCode, $weight);

        if ($shipping === null) {
            return null;
        }

        $pickup = filled($pickupArea) ? (int) CargoPickupAreas::price($pickupArea) : 0;

        return ['shipping' => $shipping, 'pickup' => $pickup, 'total' => $shipping + $pickup];
    }

    public function newShippingId(): string
    {
        return 'TWAC07'.Carbon::now()->format('md').Str::random(2);
    }

    public function newReference(): string
    {
        return 'ACG'.strtoupper(bin2hex(random_bytes(6)));
    }

    /**
     * The shipment document the customer and operations get, saved where the
     * admin and the shipment email look for it.
     *
     * @return string its file name
     */
    public function saveShipmentDocument(array $data): string
    {
        $filename = $data['shipping_id'].'.pdf';
        Storage::put('public/shipments/'.$filename, Pdf::loadView('air.air_cargo.shipment2', $data)->output());

        return $filename;
    }

    /**
     * A shipment made before payment, which staff do for a customer.
     *
     * @param  array<string, mixed>  $data  sender_*, receiver_*, shipment_type, weight, description, the package's
     *                                      length/width/height, pickup_area/pickup_address/pickup_date or dropoff_date
     */
    public function createPending(array $data, ?string $previewImage = null): AirCargoModel
    {
        $quote = $this->quote($data['shipment_type'], $data['receiver_country'], $data['weight'], $data['pickup_area'] ?? null);
        $shippingId = $data['shipping_id'] ?? $this->newShippingId();
        $isDocument = $data['shipment_type'] === 'Document';

        $document = [
            'shipping_id' => $shippingId,
            'shipping_price' => $quote['shipping'],
            'pickup_price' => $quote['pickup'] ?: null,
            'total_price' => $quote['total'],
            'sender_name' => $data['sender_name'],
            'sender_address' => $data['sender_address'],
            'sender_email' => $data['sender_email'],
            'sender_phone' => $data['sender_phone'],
            'sender_country' => CargoCountries::find($data['sender_country'])['name'] ?? '',
            'sender_postalCode' => $data['sender_postal_code'],
            'receiver_name' => $data['receiver_name'],
            'receiver_address' => $data['receiver_address'],
            'receiver_email' => $data['receiver_email'],
            'receiver_phone' => $data['receiver_phone'],
            'receiver_country' => CargoCountries::find($data['receiver_country'])['name'],
            'receiver_postalCode' => $data['receiver_postal_code'] ?? '',
            'shipment_type' => $data['shipment_type'],
            'pickup_address' => $data['pickup_address'] ?? null,
            'pickup_busstop' => $data['pickup_area'] ?? null,
            'pickup_date' => $data['pickup_date'] ?? null,
            'dropoff_date' => $data['dropoff_date'] ?? null,
        ] + ($isDocument ? [
            'document_type' => $data['description'],
            'document_weight' => $data['weight'],
        ] : [
            'pDescription' => $data['description'],
            'package_weight' => $data['weight'],
            'package_volumetric' => 'Length: '.($data['length'] ?? '').' | Width: '.($data['width'] ?? '').' | Height: '.($data['height'] ?? ''),
        ]);

        return AirCargoModel::create([
            'shipping_id' => $shippingId,
            'fullname' => $data['sender_name'],
            'email' => $data['sender_email'],
            'phone' => $data['sender_phone'],
            'shipping_to' => $document['receiver_country'],
            'shipment_type' => $data['shipment_type'],
            'shipment_preview' => $previewImage,
            'shipment_details' => $this->saveShipmentDocument($document),
            'price' => (string) $quote['shipping'],
            'total_price' => (string) $quote['total'],
            'payment_status' => 'Pending',
            // The payment reference is known before payment, so a payment can only ever complete this shipment
            'transaction_ref' => $data['reference'] ?? $this->newReference(),
        ]);
    }

    /** Marks a shipment paid and sends its confirmation, once however many times it's called. */
    public function completePayment(AirCargoModel $shipment, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($shipment): bool {
            $locked = AirCargoModel::query()->lockForUpdate()->findOrFail($shipment->getKey());

            if (strtolower((string) $locked->payment_status) === 'successful') {
                return false;
            }

            $locked->update(['payment_status' => 'successful']);

            return true;
        });

        $shipment->refresh();

        if ($justPaid) {
            try {
                Mail::to($shipment->email)->send(new ShipmentMail($shipment, $shipment->shipment_details));
            } catch (\Exception $e) {
                Log::error('Cargo email failed: '.$e->getMessage());
            }
        }
    }
}

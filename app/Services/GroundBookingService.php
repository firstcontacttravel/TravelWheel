<?php

namespace App\Services;

use App\Mail\CarHireNotificationMail;
use App\Mail\CarHireSuccessMail;
use App\Mail\TransferNotificationMail;
use App\Mail\TransferSuccessMail;
use App\Models\CarHire;
use App\Models\Transfer;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Creating and completing Car Hire and Transfer bookings, whichever way they
 * arrive: the public booking page or staff booking from the admin. The price
 * is always worked out here, never taken from the request.
 */
class GroundBookingService
{
    public function __construct(
        private readonly CarFleetCatalogService $catalog,
        private readonly TransferPricingService $transferPricing,
    ) {}

    /**
     * @return array{base_fare: int|float, tear_wear_amount: int|float, fuel_amount: int|float, admin_fee_amount: int|float, total: int|float}
     */
    public function quoteCarHire(string $vehicleType, string $category, float $hours): array
    {
        $item = collect($this->catalog->categoriesData()[$vehicleType]['items'] ?? [])->firstWhere('name', $category);

        return $this->transferPricing->quoteForBaseFare($vehicleType, (int) ($item['price'] ?? 0), (int) round($hours * 60));
    }

    /** @throws DomainException when that state has no car in the category */
    public function createCarHire(array $data, string $paymentOption): CarHire
    {
        if (! $this->catalog->carHireCategoryAvailable($data['car_type'], $data['category'], $data['state'])) {
            throw new DomainException('That vehicle category is no longer available. Please choose another.');
        }

        $quote = $this->quoteCarHire($data['car_type'], $data['category'], (float) $data['rental_hours']);

        return CarHire::create([
            'car_type' => $data['car_type'],
            'category' => $data['category'],
            'state' => $data['state'],
            // Customers book a category; the actual car is chosen from what's
            // available on ground and recorded when a driver is assigned.
            'car_model' => null,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'passengers' => $data['passengers'],
            'pickup_location' => $data['pickup_location'],
            'pickup_date' => $data['pickup_date'],
            'pickup_time' => $data['pickup_time'],
            'rental_hours' => $data['rental_hours'],
            'duration_mins' => (int) round($data['rental_hours'] * 60),
            'amount' => $quote['total'],
            'base_fare' => $quote['base_fare'],
            'tear_wear_amount' => $quote['tear_wear_amount'],
            'fuel_amount' => $quote['fuel_amount'],
            'admin_fee_amount' => $quote['admin_fee_amount'],
            'payment_option' => $paymentOption,
            'payment_reference' => 'CARHIRE'.strtoupper(bin2hex(random_bytes(5))),
            'payment_status' => 'pending',
        ]);
    }

    /** @throws DomainException when that state has no car in the category */
    public function createTransfer(array $data, string $paymentOption): Transfer
    {
        if (! $this->catalog->transferCategoryAvailable($data['vehicle_type'], $data['category'], $data['state'])) {
            throw new DomainException('That vehicle category is no longer available. Please choose another.');
        }

        $quote = $this->transferPricing->quote($data['vehicle_type'], $data['category'], (int) $data['duration_mins']);

        return Transfer::create([
            'vehicle_type' => $data['vehicle_type'],
            // Customers book a category, not a specific car: the car sent depends
            // on what's available and is recorded when a driver is assigned.
            'vehicle_name' => $data['category'].' '.match ($data['vehicle_type']) {
                'van' => 'Mini Van',
                'suv' => 'SUV',
                default => ucfirst($data['vehicle_type']),
            },
            'category' => $data['category'],
            'state' => $data['state'],
            'amount' => (int) round($quote['total']),
            'distance_km' => $data['distance_km'],
            'duration_mins' => $data['duration_mins'],
            'base_fare' => $quote['base_fare'],
            'tear_wear_amount' => $quote['tear_wear_amount'],
            'fuel_amount' => $quote['fuel_amount'],
            'admin_fee_amount' => $quote['admin_fee_amount'],
            'pickup_location' => $data['pickup_location'],
            'dropoff_location' => $data['dropoff_location'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'passengers' => $data['passengers'],
            'flight_number' => $data['flight_number'] ?? null,
            'special_requests' => $data['special_requests'] ?? null,
            'pickup_date' => $data['pickup_date'],
            'pickup_time' => $data['pickup_time'],
            'payment_option' => $paymentOption,
            'payment_reference' => 'TRANSFER'.strtoupper(bin2hex(random_bytes(5))),
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Road distance from Google Maps. Each origin may be an address or "lat,lng".
     *
     * @return array{distance_km: int, distance_text: string, duration_text: string, duration_mins: int, drive_time: string}
     *
     * @throws RuntimeException with a message fit to show
     */
    public function distance(string $origin, string $destination): array
    {
        $apiKey = config('services.google_maps.key');
        if (! $apiKey) {
            throw new RuntimeException('Maps API not configured.');
        }

        $data = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $origin,
            'destinations' => $destination,
            'units' => 'metric',
            'region' => 'NG',
            'key' => $apiKey,
        ])->json();

        if (($data['status'] ?? '') !== 'OK') {
            Log::warning('Google Maps Distance API error', ['status' => $data['status'] ?? 'unknown']);

            throw new RuntimeException('Google API error: '.($data['status'] ?? 'UNKNOWN'));
        }

        $element = $data['rows'][0]['elements'][0] ?? null;
        if (! $element || ($element['status'] ?? '') !== 'OK') {
            throw new RuntimeException('No route found. Status: '.($element['status'] ?? 'N/A'));
        }

        $durationMins = (int) ceil($element['duration']['value'] / 60);
        $driveTime = $durationMins >= 60
            ? floor($durationMins / 60).'h '.($durationMins % 60 > 0 ? ($durationMins % 60).'m' : '')
            : $durationMins.' mins';

        return [
            'distance_km' => max(1, (int) ceil($element['distance']['value'] / 1000)),
            'distance_text' => $element['distance']['text'],
            'duration_text' => $element['duration']['text'],
            'duration_mins' => $durationMins,
            'drive_time' => trim($driveTime),
        ];
    }

    /** Marks a booking paid and sends its confirmations, once however many times it's called. */
    public function completePayment(CarHire|Transfer $booking, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($booking, $paymentOption): bool {
            $locked = $booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($locked->payment_status === 'paid') {
                return false;
            }

            $locked->update(array_filter(['payment_status' => 'paid', 'payment_option' => $paymentOption]));

            return true;
        });

        $booking->refresh();

        if ($justPaid) {
            $this->sendConfirmations($booking);
        }
    }

    private function sendConfirmations(Model $booking): void
    {
        $supportMail = 'damilola@travelwheel.ng';

        try {
            if ($booking instanceof CarHire) {
                Mail::to($booking->email)->send(new CarHireSuccessMail($booking));
                Mail::to($supportMail)->send(new CarHireNotificationMail($booking));
            } else {
                Mail::to($booking->email)->send(new TransferSuccessMail($booking));
                Mail::to($supportMail)->send(new TransferNotificationMail($booking));
            }
        } catch (\Exception $e) {
            Log::error('Mail Error for '.$booking->getMorphClass(), [
                'reference' => $booking->payment_reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}

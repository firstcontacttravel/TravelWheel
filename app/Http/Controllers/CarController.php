<?php

namespace App\Http\Controllers;

use App\Models\CarHire;
use App\Models\Transfer;
use App\Services\BudPayPaymentService;
use App\Services\CarFleetCatalogService;
use App\Services\SeerbitPaymentService;
use App\Services\TransferPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CarController extends Controller
{
    public function __construct(
        private readonly CarFleetCatalogService $catalog,
        private readonly TransferPricingService $transferPricing,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | GOOGLE MAPS DISTANCE PROXY
    |--------------------------------------------------------------------------
    */
    public function distance(Request $request)
    {
        $request->validate([
            'origin' => 'required|string|max:500',
            'destination' => 'required|string|max:500',
        ]);

        $apiKey = config('services.google_maps.key');
        if (! $apiKey) {
            return response()->json(['error' => 'Maps API not configured.'], 500);
        }

        $origin = ($request->filled('origin_lat') && $request->filled('origin_lng'))
            ? $request->origin_lat . ',' . $request->origin_lng
            : $request->origin;

        $destination = ($request->filled('dest_lat') && $request->filled('dest_lng'))
            ? $request->dest_lat . ',' . $request->dest_lng
            : $request->destination;

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => $origin,
                'destinations' => $destination,
                'units' => 'metric',
                'region' => 'NG',
                'key' => $apiKey,
            ]);

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                Log::warning('Google Maps Distance API error', ['status' => $data['status'] ?? 'unknown']);

                return response()->json(['error' => 'Google API error: ' . ($data['status'] ?? 'UNKNOWN')], 500);
            }

            $element = $data['rows'][0]['elements'][0] ?? null;
            if (! $element || ($element['status'] ?? '') !== 'OK') {
                return response()->json(['error' => 'No route found. Status: ' . ($element['status'] ?? 'N/A')], 400);
            }

            $distanceKm = max(1, (int) ceil($element['distance']['value'] / 1000));
            $durationMins = (int) ceil($element['duration']['value'] / 60);
            $driveTime = $durationMins >= 60
                ? floor($durationMins / 60) . 'h ' . ($durationMins % 60 > 0 ? ($durationMins % 60) . 'm' : '')
                : $durationMins . ' mins';

            return response()->json([
                'distance_km' => $distanceKm,
                'distance_text' => $element['distance']['text'],
                'duration_text' => $element['duration']['text'],
                'duration_mins' => $durationMins,
                'drive_time' => trim($driveTime),
            ]);
        } catch (\Exception $e) {
            Log::error('Google Maps Distance Exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Distance calculation failed. Please try again.'], 500);
        }
    }

    /*
    |==========================================================================
    | CAR HIRE — submit, callbacks, success
    |==========================================================================
    */
    public function submitCarHire(Request $request)
    {
        $data = $request->validate([
            'car_type' => 'required|string',
            'category' => 'required|in:Regular,Standard,Executive',
            'car_model' => 'required|string|max:100',
            'rental_hours' => 'required|numeric|min:1',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'passengers' => 'required|integer|min:1',
            'pickup_location' => 'required|string|max:255',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'payment_option' => 'required|in:budpay,seerbit',
        ]);

        $allCats = $this->catalog->categoriesData();
        $typeData = $allCats[$data['car_type']] ?? null;
        $catItem = collect($typeData['items'] ?? [])->firstWhere('name', $data['category']);
        $basePrice = (int) ($catItem['price'] ?? 0);
        $durationMins = (int) round($data['rental_hours'] * 60);

        $quote = $this->transferPricing->quoteForBaseFare($data['car_type'], $basePrice, $durationMins);
        $verifiedPrice = $quote['total'];

        $reference = 'CARHIRE-' . strtoupper(bin2hex(random_bytes(5)));

        CarHire::create([
            'car_type' => $data['car_type'],
            'category' => $data['category'],
            'car_model' => $data['car_model'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'passengers' => $data['passengers'],
            'pickup_location' => $data['pickup_location'],
            'pickup_date' => $data['pickup_date'],
            'pickup_time' => $data['pickup_time'],
            'rental_hours' => $data['rental_hours'],
            'duration_mins' => $durationMins,
            'amount' => $verifiedPrice,
            'base_fare' => $quote['base_fare'],
            'tear_wear_amount' => $quote['tear_wear_amount'],
            'fuel_amount' => $quote['fuel_amount'],
            'admin_fee_amount' => $quote['admin_fee_amount'],
            'payment_option' => $data['payment_option'],
            'payment_reference' => $reference,
            'payment_status' => 'pending',
        ]);

        return $this->launchPayment(
            payment_option: $data['payment_option'],
            amount: $verifiedPrice,
            email: $data['email'],
            phone: $data['phone_number'],
            customerName: $data['full_name'],
            reference: $reference,
            product_title: 'Car Hire — ' . ucfirst($data['car_type']) . ' · ' . $data['category'] . ' · ' . $data['car_model'],
            callback_route: 'air.carhire.budpay.callback',
            cancel_route: 'air.carhire',
            seerbit_route: 'air.carhire.seerbit.callback',
        );
    }

    public function budpayCallbackCarHire(Request $request)
    {
        return $this->handleBudpayCallback(
            request: $request,
            model: CarHire::class,
            product_type: 'car_hire',
            fail_route: 'air.carhire',
            success_route: 'air.carhire.success',
        );
    }

    public function seerbitCallbackCarHire(Request $request)
    {
        return $this->handleSeerbitCallback(
            request: $request,
            model: CarHire::class,
            product_type: 'car_hire',
            fail_route: 'air.carhire',
            success_route: 'air.carhire.success',
        );
    }

    public function successCarHire()
    {
        return view('air.carhire.success')
            ->with('success', session('success', 'Your Car Hire booking was confirmed!'));
    }

    /*
    |==========================================================================
    | TRANSFER — submit, callbacks, success
    |==========================================================================
    */
    public function submitTransfer(Request $request)
    {
        $data = $request->validate([
            'vehicle_type' => 'required|string',
            'vehicle_name' => 'required|string',
            'category' => 'required|string|in:Regular,Standard,Executive',
            'distance_km' => 'required|numeric|min:0.1',
            'duration_mins' => 'required|integer|min:1',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'passengers' => 'required|integer|min:1',
            'flight_number' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string|max:500',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'payment_option' => 'required|in:budpay,seerbit',
        ]);

        if (! $this->catalog->transferVehicleExists($data['vehicle_type'], $data['category'], $data['vehicle_name'])) {
            return back()->with('error', 'The selected vehicle is no longer available. Please choose another.');
        }

        $quote = $this->transferPricing->quote($data['vehicle_type'], $data['category'], $data['duration_mins']);
        $verifiedPrice = (int) round($quote['total']);

        $reference = 'TRANSFER-' . strtoupper(bin2hex(random_bytes(5)));

        Transfer::create([
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_name' => $data['vehicle_name'],
            'category' => $data['category'],
            'amount' => $verifiedPrice,
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
            'payment_option' => $data['payment_option'],
            'payment_reference' => $reference,
            'payment_status' => 'pending',
        ]);

        return $this->launchPayment(
            payment_option: $data['payment_option'],
            amount: $verifiedPrice,
            email: $data['email'],
            phone: $data['phone_number'],
            customerName: $data['full_name'],
            reference: $reference,
            product_title: 'Transfer — ' . $data['vehicle_name'] . ' (' . $data['distance_km'] . ' km)',
            callback_route: 'air.transfer.budpay.callback',
            cancel_route: 'air.carhire',
            seerbit_route: 'air.transfer.seerbit.callback',
        );
    }

    public function budpayCallbackTransfer(Request $request)
    {
        return $this->handleBudpayCallback(
            request: $request,
            model: Transfer::class,
            product_type: 'transfer',
            fail_route: 'air.carhire',
            success_route: 'air.transfer.success',
        );
    }

    public function seerbitCallbackTransfer(Request $request)
    {
        return $this->handleSeerbitCallback(
            request: $request,
            model: Transfer::class,
            product_type: 'transfer',
            fail_route: 'air.carhire',
            success_route: 'air.transfer.success',
        );
    }

    public function successTransfer()
    {
        return view('air.carhire.success')
            ->with('success', session('success', 'Your Transfer booking was confirmed!'));
    }

    /*
    |==========================================================================
    | SHARED PAYMENT LAUNCHER
    |==========================================================================
    */
    private function launchPayment(
        string $payment_option,
        float $amount,
        string $email,
        string $phone,
        string $customerName,
        string $reference,
        string $product_title,
        string $callback_route,
        string $cancel_route,
        string $seerbit_route
    ) {
        if ($payment_option === 'seerbit') {
            return $this->launchSeerbitPayment(
                amount: $amount,
                email: $email,
                customerName: $customerName,
                reference: $reference,
                product_title: $product_title,
                seerbit_route: $seerbit_route,
            );
        }

        $budpay = app(BudPayPaymentService::class);
        $publicKey = $budpay->publicKey();
        $callbackUrl = route($callback_route);
        $cancelUrl = route($cancel_route);
        $firstName = explode(' ', $customerName)[0];
        $lastName = explode(' ', $customerName)[1] ?? '';

        return response()->make('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Redirecting to BudPay</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <script src="https://inlinepay.budpay.com/budpay-inline-custom.js"></script>
                <style>
                    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: "DM Sans", "Segoe UI", Arial, sans-serif; background: #f7f6f2; display: flex; align-items: center; justify-content: center; height: 100vh; }
                    .payment-card { background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 48px 36px; max-width: 420px; width: 100%; text-align: center; animation: fadeIn 0.5s ease-in-out; }
                    .logo-bar img { height: 32px; margin-bottom: 24px; }
                    h2 { font-size: 1.25rem; color: #1a1a1a; margin-bottom: 6px; }
                    .sub { color: #777; font-size: 0.875rem; margin-bottom: 24px; }
                    .amount-box { font-size: 2rem; font-weight: 700; color: #0d1883; margin-bottom: 28px; }
                    #payBtn { background: linear-gradient(135deg, #0d1883, #2d39b6); color: #fff; border: none; border-radius: 12px; padding: 14px 28px; font-size: 0.95rem; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s, transform 0.15s; }
                    #payBtn:hover { background: #0b1570; transform: translateY(-1px); }
                    #payBtn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
                    .loader { display: none; margin: 16px auto 0; border: 3px solid #eee; border-top-color: #0d1883; border-radius: 50%; width: 28px; height: 28px; animation: spin 0.8s linear infinite; }
                    .secure-note { margin-top: 18px; font-size: 0.8rem; color: #aaa; }
                    @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
                    @keyframes spin   { to { transform:rotate(360deg); } }
                </style>
            </head>
            <body>
                <div class="payment-card">
                    <div class="logo-bar">
                        <img src="' . asset('assets/img/favicon/twlogo.png') . '" alt="TravelWheel Logo">
                    </div>
                    <h2>Complete Your Payment</h2>
                    <p class="sub">You are about to pay for <strong>' . $product_title . '</strong></p>
                    <div class="amount-box">₦' . number_format($amount) . '</div>
                    <button id="payBtn">Proceed to Pay Securely</button>
                    <div class="loader" id="loader"></div>
                    <p class="secure-note">🔒 Secured by BudPay · NGN payment</p>
                </div>
                <script>
                    const payBtn = document.getElementById("payBtn");
                    const loader = document.getElementById("loader");
                    payBtn.addEventListener("click", function () {
                        loader.style.display = "block";
                        payBtn.disabled = true;
                        payBtn.textContent = "Launching...";
                        BudPayCheckout({
                            key:          "' . $publicKey . '",
                            email:        "' . $email . '",
                            amount:       "' . $amount . '",
                            first_name:   "' . $firstName . '",
                            last_name:    "' . $lastName . '",
                            currency:     "NGN",
                            reference:    "' . $reference . '",
                            callback_url: "' . $callbackUrl . '",
                            onSuccess: function (res) {
                                window.location.href = "' . $callbackUrl . '?reference=" + res.reference;
                            },
                            onClose: function () {
                                loader.style.display = "none";
                                payBtn.disabled = false;
                                payBtn.textContent = "Proceed to Pay Securely";
                                window.location.href = "' . $cancelUrl . '?payment=cancelled";
                            }
                        });
                    });
                </script>
            </body>
            </html>
        ');
    }

    private function launchSeerbitPayment(
        float $amount,
        string $email,
        string $customerName,
        string $reference,
        string $product_title,
        string $seerbit_route
    ) {
        try {
            $seerbit = app(SeerbitPaymentService::class);
            $result = $seerbit->initializePayment([
                'amount' => (string) $amount,
                'currency' => 'NGN',
                'callbackUrl' => route($seerbit_route),
                'email' => $email,
                'fullName' => $customerName,
                'paymentReference' => $reference,
                'productDescription' => $product_title,
                'productId' => 'PRD' . $reference,
            ]);

            return redirect()->away($result['redirect_link']);
        } catch (\Exception $e) {
            Log::error('SeerBit Init Exception', ['reference' => $reference, 'error' => $e->getMessage()]);

            return back()->with('error', 'SeerBit Error: ' . $e->getMessage());
        }
    }

    /*
    |==========================================================================
    | SHARED CALLBACK HANDLERS
    |==========================================================================
    */
    private function handleBudpayCallback(
        Request $request,
        string $model,
        string $product_type,
        string $fail_route,
        string $success_route
    ) {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route($fail_route)->with('error', 'Missing payment reference. Please contact support.');
        }

        $verify = app(BudPayPaymentService::class)->verifyTransaction($reference);

        if (! $verify['ok']) {
            return redirect()->route($fail_route)
                ->with('error', 'Payment was not completed. Status: ' . ($verify['status'] ?? 'Unknown'));
        }

        return $this->finalizeBooking($model, $reference, $product_type, $fail_route, $success_route);
    }

    private function handleSeerbitCallback(
        Request $request,
        string $model,
        string $product_type,
        string $fail_route,
        string $success_route
    ) {
        $reference = $request->query('reference');
        $message = $request->query('message');

        if (strtolower((string) $message) !== 'successful') {
            return redirect()->route($fail_route)->with('error', 'Payment failed or was cancelled. Please try again.');
        }

        return $this->finalizeBooking($model, $reference, $product_type, $fail_route, $success_route);
    }

    private function finalizeBooking(string $model, string $reference, string $product_type, string $fail_route, string $success_route)
    {
        $record = $model::where('payment_reference', $reference)->first();

        if (! $record) {
            return redirect()->route($fail_route)->with('error', 'Payment received but booking not found. Please contact support.');
        }

        if ($record->payment_status !== 'paid') {
            $record->update(['payment_status' => 'paid']);
            $this->sendMails($record, $product_type);
        }

        $successMessage = $product_type === 'car_hire'
            ? 'Car Hire booking confirmed! Your vehicle is reserved.'
            : 'Transfer booking confirmed! Your vehicle is reserved.';

        return redirect()->route($success_route)->with('success', $successMessage);
    }

    /*
    |==========================================================================
    | MAIL SENDER
    |==========================================================================
    */
    private function sendMails($record, string $product_type): void
    {
        $supportMail = 'damilola@travelwheel.ng';

        try {
            switch ($product_type) {
                case 'car_hire':
                    Mail::to($record->email)->send(new \App\Mail\CarHireSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\CarHireNotificationMail($record));
                    break;
                case 'transfer':
                    Mail::to($record->email)->send(new \App\Mail\TransferSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\TransferNotificationMail($record));
                    break;
                default:
                    Log::warning('sendMails: unknown product_type', ['product_type' => $product_type]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Mail Error for ' . $product_type, [
                'reference' => $record->payment_reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}

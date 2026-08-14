<?php

namespace App\Http\Controllers;

use App\Models\SupportExtraLuggage;
use App\Models\SupportFlightAssist;
use App\Models\SupportProductPrice;
use App\Models\SupportVisaConfirmation;
use App\Models\SupportYellowCard;
use App\Services\BudPayPaymentService;
use App\Services\SeerbitPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    /*
    |==========================================================================
    | FORM PAGES
    |==========================================================================
    */
    public function flightAssistForm()
    {
        return view('air.support.flight-assist', [
            'flightAssistPrice' => SupportProductPrice::amountFor('flight_assist', 25000),
        ]);
    }

    public function extraLuggageForm()
    {
        return view('air.support.extra-luggage');
    }

    public function visaConfirmationForm()
    {
        return view('air.support.visa-confirmation');
    }

    public function yellowCardForm()
    {
        return view('air.support.yellow-card');
    }

    /*
    |==========================================================================
    | FLIGHT ASSIST
    |==========================================================================
    */
    public function submitFlightAssist(Request $request)
    {
        $data = $request->validate([
            'request_type' => 'required|in:date_change,rerouting',
            'booking_source' => 'required|in:airline,agent',
            'name_on_ticket' => 'nullable|string|max:255',
            'airline_reference' => 'nullable|string|max:255',
            'airline_category' => 'nullable|string|max:50',
            'airline' => 'nullable|string|max:255',
            'trip_type' => 'nullable|string|max:50',
            'travel_date_oneway' => 'nullable|date',
            'departure_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'route_from' => 'nullable|string|max:255',
            'route_to' => 'nullable|string|max:255',
            'preferred_time' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'additional_info' => 'nullable|string',
        ]);

        $reference = 'SPA-' . strtoupper(bin2hex(random_bytes(6)));

        // Flight Assist isn't paid for here — the fee is billed together with
        // the client's main flight booking fee, so this just records the
        // request and notifies both the client and support team directly.
        // The amount is looked up server-side (not trusted from the form) so
        // it always reflects the admin-configured price at submission time.
        $record = SupportFlightAssist::create(array_merge($data, [
            'amount' => SupportProductPrice::amountFor('flight_assist', 25000),
            'payment_option' => 'not_required',
            'payment_reference' => $reference,
            'payment_status' => 'billed_with_main_fee',
        ]));

        $this->sendMails($record, 'flight_assist');

        return redirect()->route('air.support.success')
            ->with('success', $this->successMessage('flight_assist'));
    }

    /*
    |==========================================================================
    | EXTRA LUGGAGE
    |==========================================================================
    */
    public function submitExtraLuggage(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'airline_category' => 'required|string|max:50',
            'data_page' => 'required|file|mimes:pdf,jpg,png',
            'contact_number' => 'required|string|max:20',
            'airline' => 'required|string|max:255',
            'ticket' => 'required|file|mimes:pdf,jpg,png',
            'email' => 'required|email|max:255',
            'payment_option' => 'required|in:budpay,seerbit',
            'amount' => 'required|integer|in:25000',
        ]);

        $dataPage = $request->file('data_page')->store('support/extra-luggage', 'public');
        $ticket = $request->file('ticket')->store('support/extra-luggage-tickets', 'public');
        $reference = 'SEL-' . strtoupper(bin2hex(random_bytes(6)));

        SupportExtraLuggage::create([
            'full_name' => $data['full_name'],
            'airline_category' => $data['airline_category'],
            'airline' => $data['airline'],
            'data_page' => $dataPage,
            'ticket' => $ticket,
            'contact_number' => $data['contact_number'],
            'email' => $data['email'],
            'payment_option' => $data['payment_option'],
            'payment_reference' => $reference,
            'payment_status' => 'pending',
            'amount' => $data['amount'],
        ]);

        return $this->launchPayment(
            payment_option: $data['payment_option'],
            amount: $data['amount'],
            email: $data['email'],
            customerName: $data['full_name'],
            reference: $reference,
            product_title: 'Extra Luggage',
        );
    }

    /*
    |==========================================================================
    | VISA CONFIRMATION
    |==========================================================================
    */
    public function submitVisaConfirmation(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'visa_file' => 'required|file|mimes:pdf,jpg,png',
            'payment_option' => 'required|in:budpay,seerbit',
            'amount' => 'required|integer|in:50000',
            'additional_info' => 'nullable|string',
        ]);

        $visaFile = $request->file('visa_file')->store('support/visa-confirmation', 'public');
        $reference = 'SVC-' . strtoupper(bin2hex(random_bytes(6)));

        SupportVisaConfirmation::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'visa_file' => $visaFile,
            'additional_info' => $data['additional_info'] ?? null,
            'payment_option' => $data['payment_option'],
            'payment_reference' => $reference,
            'payment_status' => 'pending',
            'amount' => $data['amount'],
        ]);

        return $this->launchPayment(
            payment_option: $data['payment_option'],
            amount: $data['amount'],
            email: $data['email'],
            customerName: $data['full_name'],
            reference: $reference,
            product_title: 'Visa Confirmation',
        );
    }

    /*
    |==========================================================================
    | YELLOW CARD
    |==========================================================================
    */
    public function submitYellowCard(Request $request)
    {
        $data = $request->validate([
            'service_type' => 'required|in:standard,fasttrack',
            'full_name' => 'required|string|max:255',
            'data_page' => 'required|file|mimes:pdf,jpg,png',
            'email' => 'required|email|max:255',
            'home_address' => 'required|string',
            'payment_option' => 'required|in:budpay,seerbit',
            'phone_number' => 'required|string|max:20',
            'delivery_address' => 'required|string',
        ]);

        $filePath = $request->file('data_page')->store('support/yellow-card', 'public');
        $reference = 'SYC-' . strtoupper(bin2hex(random_bytes(6)));
        $amount = $data['service_type'] === 'fasttrack' ? 50000 : 30000;

        SupportYellowCard::create([
            'service_type' => $data['service_type'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'data_page' => $filePath,
            'home_address' => $data['home_address'],
            'phone_number' => $data['phone_number'],
            'delivery_address' => $data['delivery_address'],
            'payment_option' => $data['payment_option'],
            'payment_reference' => $reference,
            'payment_status' => 'pending',
            'amount' => $amount,
        ]);

        return $this->launchPayment(
            payment_option: $data['payment_option'],
            amount: $amount,
            email: $data['email'],
            customerName: $data['full_name'],
            reference: $reference,
            product_title: 'Yellow Card',
        );
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
        string $customerName,
        string $reference,
        string $product_title
    ) {
        if ($payment_option === 'seerbit') {
            return $this->launchSeerbitPayment($amount, $email, $customerName, $reference, $product_title);
        }

        $budpay = app(BudPayPaymentService::class);
        $publicKey = $budpay->publicKey();
        $callbackUrl = route('air.support.budpay.callback');
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
                    body { font-family: "Segoe UI", Arial, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; }
                    .payment-card { background: #fff; border-radius: 18px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); padding: 45px 35px; max-width: 430px; width: 100%; text-align: center; animation: fadeIn 0.6s ease-in-out; }
                    .logo-bar img { height: 30px; margin-bottom: 20px; }
                    .amount-box { font-size: 1.7rem; font-weight: 700; color: #0d1883; margin: 25px 0; }
                    #payBtn { background: linear-gradient(135deg, #0d1883, #2d39b6); color: #fff; border: none; border-radius: 10px; padding: 15px 30px; font-size: 1rem; cursor: pointer; transition: 0.3s ease; width: 100%; }
                    #payBtn:hover { background: #0c146e; transform: translateY(-2px); }
                    .loader { display: none; margin: 15px auto; border: 4px solid #f3f3f3; border-top: 4px solid #0d1883; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; }
                    .secure-note { margin-top: 20px; font-size: 0.85rem; color: #777; }
                    @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
                    @keyframes spin { 0% { transform:rotate(0deg); } 100% { transform:rotate(360deg); } }
                </style>
            </head>
            <body>
                <div class="payment-card">
                    <div class="logo-bar">
                        <img src="' . asset('assets/twlogo.png') . '" alt="TravelWheel Logo">
                    </div>
                    <h2 style="color:#1a1a1a; margin-bottom: 8px;">Redirecting to BudPay</h2>
                    <p style="color:#555;">You\'re about to complete your <b>' . $product_title . '</b> payment securely.</p>
                    <div class="amount-box">₦' . number_format($amount) . '</div>
                    <button id="payBtn">Proceed to Pay Securely</button>
                    <div class="loader" id="loader"></div>
                    <p class="secure-note">Secured payment powered by BudPay</p>
                </div>
                <script>
                    const payBtn = document.getElementById("payBtn");
                    const loader = document.getElementById("loader");
                    payBtn.addEventListener("click", function (e) {
                        e.preventDefault();
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
                                window.location.href = "' . route('air.support') . '?payment=cancelled";
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
        string $product_title
    ) {
        try {
            $seerbit = app(SeerbitPaymentService::class);
            $result = $seerbit->initializePayment([
                'amount' => (string) $amount,
                'currency' => 'NGN',
                'callbackUrl' => route('air.support.seerbit.callback'),
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
    | CALLBACKS
    |==========================================================================
    */
    public function budpayCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('air.support')->with('error', 'Missing payment reference. Please contact support.');
        }

        $verify = app(BudPayPaymentService::class)->verifyTransaction($reference);

        if (! $verify['ok']) {
            return redirect()->route('air.support')
                ->with('error', 'Payment was not completed. Status: ' . ($verify['status'] ?? 'Unknown'));
        }

        return $this->finalizeRequest($reference);
    }

    public function seerbitCallback(Request $request)
    {
        $reference = $request->query('reference');
        $message = $request->query('message');

        if (strtolower((string) $message) !== 'successful') {
            return redirect()->route('air.support')->with('error', 'Payment failed or was cancelled. Please try again.');
        }

        return $this->finalizeRequest($reference);
    }

    private function finalizeRequest(string $reference)
    {
        $models = [
            'flight_assist' => SupportFlightAssist::class,
            'extra_luggage' => SupportExtraLuggage::class,
            'visa_confirmation' => SupportVisaConfirmation::class,
            'yellow_card' => SupportYellowCard::class,
        ];

        $record = null;
        $type = null;

        foreach ($models as $modelType => $modelClass) {
            $record = $modelClass::where('payment_reference', $reference)->first();
            if ($record) {
                $type = $modelType;
                break;
            }
        }

        if (! $record) {
            return redirect()->route('air.support')->with('error', 'Payment received but request not found. Please contact support.');
        }

        if ($record->payment_status !== 'paid') {
            $record->update(['payment_status' => 'paid']);
            $this->sendMails($record, $type);
        }

        return redirect()->route('air.support.success')->with('success', $this->successMessage($type));
    }

    private function successMessage(string $type): string
    {
        return match ($type) {
            'flight_assist' => 'Flight Assist request confirmed!',
            'extra_luggage' => 'Extra Luggage request confirmed!',
            'visa_confirmation' => 'Visa Confirmation request confirmed!',
            'yellow_card' => 'Yellow Card request confirmed!',
            default => 'Request confirmed!',
        };
    }

    private function sendMails($record, string $type): void
    {
        $supportMail = 'damilola@travelwheel.ng';

        try {
            switch ($type) {
                case 'flight_assist':
                    Mail::to($record->email)->send(new \App\Mail\SupportFlightAssistSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\SupportFlightAssistNotificationMail($record));
                    break;
                case 'extra_luggage':
                    Mail::to($record->email)->send(new \App\Mail\SupportExtraLuggageSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\SupportExtraLuggageNotificationMail($record, [
                        storage_path('app/public/' . $record->data_page),
                        storage_path('app/public/' . $record->ticket),
                    ]));
                    break;
                case 'visa_confirmation':
                    Mail::to($record->email)->send(new \App\Mail\SupportVisaConfirmationSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\SupportVisaConfirmationNotificationMail($record, [
                        storage_path('app/public/' . $record->visa_file),
                    ]));
                    break;
                case 'yellow_card':
                    Mail::to($record->email)->send(new \App\Mail\SupportYellowCardSuccessMail($record));
                    Mail::to($supportMail)->send(new \App\Mail\SupportYellowCardNotificationMail($record, [
                        storage_path('app/public/' . $record->data_page),
                    ]));
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Support mail error for ' . $type, [
                'reference' => $record->payment_reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function success()
    {
        return view('air.support.success')->with('success', session('success', 'Your request was submitted successfully!'));
    }
}

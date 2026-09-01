<?php

use App\Http\Controllers\AdminReportExportController;
use App\Http\Controllers\AdminVisaDocumentController;
use App\Http\Controllers\AirCargoController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\LeadwayController;
use App\Http\Controllers\LoungeController;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\VisaApplicationController;
use App\Http\Controllers\VisaPaymentController;
use App\Http\Controllers\VisaPortalController;
use App\Http\Controllers\VisaSearchController;
use App\Http\Middleware\EnsureVisaProductEnabled;
use App\Livewire\Pages\AirCargo\AirCargo as AirCargoPage;
use App\Livewire\Pages\Legal\AirportProtocolServiceTerms;
use App\Livewire\Pages\Legal\BookingServiceAgreement;
use App\Livewire\Pages\Legal\CookiePolicy;
use App\Livewire\Pages\Legal\Disclaimer;
use App\Livewire\Pages\Legal\PaymentPolicy;
use App\Livewire\Pages\Legal\PaySmallSmallAgreement;
use App\Livewire\Pages\Legal\PrivacyPolicy;
use App\Livewire\Pages\Legal\RefundCancellationPolicy;
use App\Livewire\Pages\Legal\TermsAndConditions;
use App\Livewire\Pages\Legal\TravelInsuranceTerms;
use App\Livewire\Pages\AirCargo\AirCargoCreate;
use App\Livewire\Pages\CarHire\CarHire as CarHirePage;
use App\Livewire\Pages\FlightBooking;
use App\Livewire\Pages\FlightPage;
use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\Insurance\Insurance as InsurancePage;
use App\Livewire\Pages\Insurance\InsuranceQuote as InsuranceQuotePage;
use App\Livewire\Pages\Lounge\Lounge as LoungePage;
use App\Livewire\Pages\Lounge\LoungePlan;
use App\Livewire\Pages\Lounge\LoungeResults;
use App\Livewire\Pages\Protocol\Protocol as ProtocolPage;
use App\Livewire\Pages\Protocol\ProtocolInternationalPlans;
use App\Livewire\Pages\Protocol\ProtocolPlans;
use App\Livewire\Pages\Support\Support as SupportPage;
use App\Livewire\Pages\Visa\ApplicationWizard as VisaApplicationWizard;
use App\Livewire\Pages\Visa\Discovery as VisaDiscovery;
use App\Livewire\Pages\Visa\Results as VisaResults;

if (app()->environment('local')) {
    Route::view('/__design-system/visa', 'design-system.visa')->name('design-system.visa');
}

Route::get('/', HomePage::class)->name('home');
Route::get('/air', HomePage::class)->name('air');

// Add other routes
Route::get('/about-us', function () { /* ... */
})->name('aboutus');
Route::get('/faq', function () { /* ... */
})->name('faq');
Route::get('/help', function () { /* ... */
})->name('help');

// Air routes
Route::redirect('/air/flight', '/')->name('air.flight');
Route::get('/air/flight-s', FlightPage::class)->name('air.flight-s');
// Route::post('/air/flight/search', [FlightPage::class, 'search'])->name('flights.search');
// Route::post('/air/flight/select', [FlightSearchController::class, 'select'])->name('flights.select');

Route::post('/flights/search', [FlightController::class, 'search'])->middleware('throttle:12,1')->name('flights.search');
Route::get('/flights/search/loading', [FlightController::class, 'loading'])->name('flights.search.loading');
Route::get('/flights/search/run', [FlightController::class, 'runPendingSearch'])->middleware('throttle:12,1')->name('flights.search.run');

// Route::post('/flights/select', [FlightController::class, 'select'])->name('flights.select');

Route::get('/air/hotel', function () { /* ... */
})->name('air.hotel');
// Protocol routes

Route::get('/air/protocol', ProtocolPage::class)->name('air.protocol');
Route::post('/air/protocol/plan', [ProtocolController::class, 'protocolPlan'])->name('air.protocolplan');
Route::get('/air/protocol/plans/{id}', ProtocolPlans::class)->name('air.protocolplans');
Route::get('/air/protocol/plans-intl/{id}', ProtocolInternationalPlans::class)->name('air.protocolplansI');
Route::get('/air/protocol/form/{plan}', [ProtocolController::class, 'protocolForm'])->name('air.protocolForm');
Route::post('/air/protocol/checkout', [ProtocolController::class, 'protocol_checkout'])->name('air.protocol_checkout');
Route::post('/air/protocol/purchase', [ProtocolController::class, 'makePurchase'])->name('air.protocolmakePurchase');
Route::get('/air/protocol/payment/callback', [ProtocolController::class, 'callbackSeerbit'])->name('air.protocol.callback');
Route::get('/air/protocol/payment/{trans_id}', [ProtocolController::class, 'protocol_payment'])->name('air.protocol_payment');
Route::get('/air/protocol/generate/{trans_id}', [ProtocolController::class, 'generateProtocolPass'])->name('air.protocol_generate');
Route::get('/air/protocol/success', [ProtocolController::class, 'protocol_success'])->name('air.protocol_success');

// Lounge routes
Route::get('/air/lounge', LoungePage::class)->name('air.lounge');
Route::post('/air/lounge/search', [LoungeController::class, 'lounges'])
    ->middleware('throttle:10,1')
    ->name('air.lounges');
Route::get('/air/lounge/results', LoungeResults::class)->name('air.lounges.results');
Route::get('/air/lounge/plans/{id}', LoungePlan::class)->name('air.loungeplans');
Route::get('/air/lounge/booking/{id}', [LoungeController::class, 'loungeBooking'])->name('air.loungebooking');
Route::post('/air/lounge/checkout', [LoungeController::class, 'loungecheckout'])->name('air.loungecheckout');
Route::post('/air/lounge/purchase', [LoungeController::class, 'makePurchase'])->name('air.lounge.purchase');
Route::get('/air/lounge/payment/callback', [LoungeController::class, 'callbackSeerbit'])->name('air.lounge.callback');
Route::get('/air/lounge/payment/{trans_id}', [LoungeController::class, 'lounge_payment'])->name('air.lounge_payment');
Route::get('/air/lounge/generate/{trans_id}', [LoungeController::class, 'generateLoungePass'])->name('air.lounge_generate');
Route::get('/air/lounge/success', [LoungeController::class, 'lounge_success'])->name('air.lounge_success');
// Insurance (Sanla/Allianz) routes
Route::get('/air/insurance', InsurancePage::class)->name('air.insurance');
Route::post('/air/insurance/quote', [InsuranceController::class, 'makeRequestQuote'])->name('air.insurance.quote');
Route::get('/air/insurance/quote/{qid}', InsuranceQuotePage::class)->name('air.insurance.quote.show');
Route::get('/air/insurance/request', [InsuranceController::class, 'insuranceRequest'])->name('air.insurance.request');
Route::post('/air/insurance/purchase', [InsuranceController::class, 'insurancePurchase'])->name('air.insurance.purchase');
Route::post('/air/insurance/pay', [InsuranceController::class, 'makeRequestPurchase'])->name('air.insurance.pay');
Route::get('/air/insurance/payment/callback', [InsuranceController::class, 'callbackSeerbit'])->name('air.insurance.callback');
Route::get('/air/insurance/success', [InsuranceController::class, 'insuranceSuccess'])->name('air.insurance.success');
// Insurance (Leadway) routes
Route::get('/air/insurance/leadway', [LeadwayController::class, 'insuranceLeadway'])->name('air.insuranceLeadway');
Route::get('/air/insurance/leadway/plan', [LeadwayController::class, 'insuranceLeadwayP'])->name('air.insuranceLeadwayP');
Route::post('/air/insurance/leadway/quote', [LeadwayController::class, 'insuranceLeadwayQ'])->name('air.insuranceLeadwayQ');
Route::post('/air/insurance/leadway/purchase', [LeadwayController::class, 'makePurchase'])->name('air.makePurchase');
Route::middleware(EnsureVisaProductEnabled::class)->group(function () {
    Route::get('/air/visa', VisaDiscovery::class)->name('air.visa');
    Route::get('/visas/destinations', [VisaSearchController::class, 'destinations'])->name('visa.destinations');
    Route::post('/visas/search', [VisaSearchController::class, 'search'])->name('visa.search');
    Route::get('/visas/search/loading', [VisaSearchController::class, 'loading'])->name('visa.search.loading');
    Route::get('/visas/search/run', [VisaSearchController::class, 'runPendingSearch'])->name('visa.search.run');
    Route::get('/visas/results', VisaResults::class)->name('visa.results');
    Route::post('/visas/applications', [VisaApplicationController::class, 'start'])->name('visa.applications.start');
    Route::get('/visas/applications/{application:reference}', VisaApplicationWizard::class)->name('visa.application');
    Route::get('/visas/applications/{application:reference}/resume/{token}', [VisaApplicationController::class, 'resume'])->name('visa.application.resume');
    Route::post('/visas/applications/{application:reference}/quote', [VisaPaymentController::class, 'quote'])->name('visa.quotes.create');
    Route::post('/visas/quotes/{quote:reference}/payments', [VisaPaymentController::class, 'initialize'])->name('visa.payments.initialize');
    Route::get('/visas/payments/seerbit/callback', [VisaPaymentController::class, 'callback'])->name('visa.payments.callback');
    Route::post('/visas/payments/seerbit/webhook', [VisaPaymentController::class, 'webhook'])->name('visa.payments.webhook');
    Route::get('/visas/payments/{payment:reference}/result', [VisaPaymentController::class, 'result'])->name('visa.payments.result');
    Route::get('/visa-portal', [VisaPortalController::class, 'entry'])->name('visa.portal.entry');
    Route::post('/visa-portal/access-code', [VisaPortalController::class, 'requestCode'])->name('visa.portal.code.request');
    Route::get('/visa-portal/verify', [VisaPortalController::class, 'verifyForm'])->name('visa.portal.verify.form');
    Route::post('/visa-portal/verify', [VisaPortalController::class, 'verify'])->name('visa.portal.verify');
    Route::get('/visa-portal/{application:reference}', [VisaPortalController::class, 'show'])->name('visa.portal.show');
    Route::post('/visa-portal/{application:reference}/requests/{documentRequest}/upload', [VisaPortalController::class, 'upload'])->name('visa.portal.requests.upload');
    Route::get('/visa-portal/{application:reference}/documents/{document}/download', [VisaPortalController::class, 'downloadDocument'])->name('visa.portal.documents.download');
    Route::get('/visa-portal/{application:reference}/issued-documents/{document}/download', [VisaPortalController::class, 'downloadIssuedDocument'])->name('visa.portal.issued-documents.download');
    Route::get('/visa-portal/{application:reference}/payments/{payment}/receipt', [VisaPortalController::class, 'receipt'])->name('visa.portal.receipts.show');
    Route::post('/visa-portal/{application:reference}/notifications/{notification}/resend', [VisaPortalController::class, 'resend'])->name('visa.portal.notifications.resend');
});
Route::middleware('auth')->prefix('admin/visa-documents')->name('admin.visa.documents.')->group(function () {
    Route::get('/application/{document}', [AdminVisaDocumentController::class, 'application'])->name('application');
    Route::get('/requested/{documentRequest}', [AdminVisaDocumentController::class, 'requested'])->name('requested');
    Route::get('/issued/{document}', [AdminVisaDocumentController::class, 'issued'])->name('issued');
});
// Air Cargo routes
Route::get('/air/cargo', AirCargoPage::class)->name('air.cargo');
Route::get('/air/cargo/international', AirCargoCreate::class)->name('air.cargo.international');
Route::post('/air/cargo/shipping-price', [AirCargoController::class, 'getShippingPrice'])->name('air.cargo.shipping-price');
Route::get('/air/cargo/zones', [AirCargoController::class, 'getShippingZones'])->name('air.cargo.zones');
Route::post('/air/cargo/create', [AirCargoController::class, 'airCargoPost'])->name('air.cargo.post');
Route::get('/air/cargo/payment/callback', [AirCargoController::class, 'callbackSeerbit'])->name('air.cargo.callback');
Route::get('/air/cargo/success', [AirCargoController::class, 'airCargoSuccess'])->name('air.cargo.success');
// Support routes
Route::get('/air/support', SupportPage::class)->name('air.support');
Route::get('/air/support/flight-assist', [SupportController::class, 'flightAssistForm'])->name('air.support.flight-assist.form');
Route::post('/air/support/flight-assist', [SupportController::class, 'submitFlightAssist'])->name('air.support.flight-assist');
Route::get('/air/support/extra-luggage', [SupportController::class, 'extraLuggageForm'])->name('air.support.extra-luggage.form');
Route::post('/air/support/extra-luggage', [SupportController::class, 'submitExtraLuggage'])->name('air.support.extra-luggage');
Route::get('/air/support/visa-confirmation', [SupportController::class, 'visaConfirmationForm'])->name('air.support.visa-confirmation.form');
Route::post('/air/support/visa-confirmation', [SupportController::class, 'submitVisaConfirmation'])->name('air.support.visa-confirmation');
Route::get('/air/support/yellow-card', [SupportController::class, 'yellowCardForm'])->name('air.support.yellow-card.form');
Route::post('/air/support/yellow-card', [SupportController::class, 'submitYellowCard'])->name('air.support.yellow-card');
Route::get('/air/support/payment/callback/budpay', [SupportController::class, 'budpayCallback'])->name('air.support.budpay.callback');
Route::get('/air/support/payment/callback/seerbit', [SupportController::class, 'seerbitCallback'])->name('air.support.seerbit.callback');
Route::get('/air/support/success', [SupportController::class, 'success'])->name('air.support.success');

// Car Hire routes
Route::get('/air/carhire', CarHirePage::class)->name('air.carhire');
Route::post('/air/carhire/submit', [CarController::class, 'submitCarHire'])->name('air.carhire.submit');
Route::get('/air/carhire/payment/callback/budpay', [CarController::class, 'budpayCallbackCarHire'])->name('air.carhire.budpay.callback');
Route::get('/air/carhire/payment/callback/seerbit', [CarController::class, 'seerbitCallbackCarHire'])->name('air.carhire.seerbit.callback');
Route::get('/air/carhire/success', [CarController::class, 'successCarHire'])->name('air.carhire.success');
Route::post('/air/carhire/distance', [CarController::class, 'distance'])->name('air.carhire.distance');

// Transfer routes
Route::post('/air/transfer/submit', [CarController::class, 'submitTransfer'])->name('air.transfer.submit');
Route::get('/air/transfer/payment/callback/budpay', [CarController::class, 'budpayCallbackTransfer'])->name('air.transfer.budpay.callback');
Route::get('/air/transfer/payment/callback/seerbit', [CarController::class, 'seerbitCallbackTransfer'])->name('air.transfer.seerbit.callback');
Route::get('/air/transfer/success', [CarController::class, 'successTransfer'])->name('air.transfer.success');

Route::get('/admin/travelflex-applications/{application}/documents/{key}', function (\App\Models\TravelFlexApplication $application, string $key) {
    $user = auth()->user();
    $adminEmails = collect(config('travelwheel.admin_emails', []));

    abort_unless($user && ($user->is_admin || $adminEmails->contains(strtolower($user->email))), 403);

    $documents = $application->document_paths ?? [];
    $path = $key === 'fast_credit_application'
        ? $application->generated_application_path
        : (is_array($documents) ? ($documents[$key] ?? null) : null);

    abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
})->middleware('auth')->name('admin.travelflex.documents.download');

Route::get('/admin/air-cargo-bookings/{record}/document-preview', function (\App\Models\AirCargoModel $record) {
    $user = auth()->user();
    $adminEmails = collect(config('travelwheel.admin_emails', []));

    abort_unless($user && ($user->is_admin || $adminEmails->contains(strtolower($user->email))), 403);

    $path = 'public/shipments/'.$record->shipment_details;

    abort_unless($record->shipment_details && \Illuminate\Support\Facades\Storage::exists($path), 404);

    return \Illuminate\Support\Facades\Storage::response($path);
})->middleware('auth')->name('admin.aircargo.document.preview');

Route::get('/admin/reports/export/{report}', AdminReportExportController::class)
    ->middleware('auth')
    ->name('admin.reports.export');

// Flight routes

Route::post('/flights/select', [FlightBookingController::class, 'select'])
    ->middleware('throttle:20,1')->name('flights.select');

Route::get('/flights/booking', FlightBooking::class)->name('flights.booking');

Route::post('/flights/book', [FlightBookingController::class, 'book'])
    ->middleware('throttle:10,1')->name('flights.book');
Route::get('/flights/payment/gateway', [FlightBookingController::class, 'paymentGateway'])->name('flights.payment.gateway');
Route::post('/flights/payment/gateway/process', [FlightBookingController::class, 'processGatewayPayment'])->middleware('throttle:10,1')->name('flights.payment.gateway.process');

// Non-LCC: 3-option payment page (booking already on hold)
Route::get('/flights/payment/options', [FlightBookingController::class, 'paymentOptions'])->name('flights.payment.options');
Route::get('/flights/payment/options/resume/{bookingRef}', [FlightBookingController::class, 'resumePaymentOptions'])
    ->middleware('signed')->name('flights.payment.options.resume');

// Non-LCC: Bank transfer — user clicks "I have paid"
Route::post('/flights/payment/bank-transfer', [FlightBookingController::class, 'bankTransferNotify'])->name('flights.payment.bank-transfer');

// Non-LCC: Gateway on payment options → ticket_order
Route::post('/flights/payment/gateway-ticket', [FlightBookingController::class, 'processTicketPayment'])->middleware('throttle:10,1')->name('flights.payment.gateway-ticket');

// Pending page (bank transfer awaiting verification)
Route::get('/flights/pending', [FlightBookingController::class, 'pending'])->name('flights.pending');

// Final confirmation (WebFare confirmed OR Non-LCC ticketed)
Route::get('/flights/confirmation', [FlightBookingController::class, 'confirmation'])->name('flights.confirmation');
Route::get('/payments/seerbit/callback', [FlightBookingController::class, 'seerbitCallback'])->middleware('throttle:30,1')->name('payments.seerbit.callback');
Route::post('/payments/seerbit/webhook', [FlightBookingController::class, 'seerbitWebhook'])->middleware('throttle:60,1')->name('payments.seerbit.webhook');
Route::get('/payments/seerbit/processing', [FlightBookingController::class, 'seerbitProcessing'])->name('payments.seerbit.processing');
Route::get('/payments/seerbit/status', [FlightBookingController::class, 'seerbitStatus'])->middleware('throttle:120,1')->name('payments.seerbit.status');
Route::get('/flights/travelflex', [FlightBookingController::class, 'travelFlex'])->name('flights.travelflex');
Route::post('/flights/travelflex/application', [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application');
Route::get('/flights/travelflex/fastcredit', [FlightBookingController::class, 'travelFlexFastCreditRedirect'])->name('flights.travelflex.fastcredit');
Route::get('/flights/travelflex/application', [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application.get');
Route::post('/flights/travelflex/submit-application', [FlightBookingController::class, 'travelFlexSubmitApplication'])->middleware('throttle:5,1')->name('flights.travelflex.submit-application');
Route::post('/flights/travelflex/bank-transfer', [FlightBookingController::class, 'travelFlexBankTransfer'])->name('flights.travelflex.bank-transfer');
Route::get('/flights/travelflex/bank-transfer-form', [FlightBookingController::class, 'travelFlexBankTransferForm'])->name('flights.travelflex.bank-transfer-form');
Route::get('/flights/travelflex/pending', [FlightBookingController::class, 'travelFlexPending'])->name('flights.travelflex.pending');
Route::get('/flights/travelflex/applications/{application}/approved', [FlightBookingController::class, 'travelFlexApproved'])
    ->middleware('signed')->name('flights.travelflex.approved');
Route::post('/flights/travelflex/approved/payment', [FlightBookingController::class, 'travelFlexApprovedPayment'])
    ->name('flights.travelflex.approved.payment');
Route::get('/flights/travelflex/confirmation', [FlightBookingController::class, 'travelFlexConfirmation'])->name('flights.travelflex.confirmation');
// Route::get('/flights/booking', \App\Livewire\Pages\FlightBooking::class)->name('flights.booking');

// Legal pages
Route::get('/legal/terms-and-conditions', TermsAndConditions::class)->name('legal.terms');
Route::get('/legal/privacy-policy', PrivacyPolicy::class)->name('legal.privacy');
Route::get('/legal/refund-cancellation-policy', RefundCancellationPolicy::class)->name('legal.refund');
Route::get('/legal/payment-policy', PaymentPolicy::class)->name('legal.payment');
Route::get('/legal/booking-service-agreement', BookingServiceAgreement::class)->name('legal.booking-agreement');
Route::get('/legal/pay-small-small-agreement', PaySmallSmallAgreement::class)->name('legal.pay-small-small');
Route::get('/legal/travel-insurance-terms', TravelInsuranceTerms::class)->name('legal.insurance-terms');
Route::get('/legal/airport-protocol-service-terms', AirportProtocolServiceTerms::class)->name('legal.protocol-terms');
Route::get('/legal/cookie-policy', CookiePolicy::class)->name('legal.cookies');
Route::get('/legal/disclaimer', Disclaimer::class)->name('legal.disclaimer');

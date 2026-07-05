<?php

use App\Http\Controllers\AdminReportExportController;
use App\Http\Controllers\AirCargoController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\LeadwayController;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\LoungeController;
use App\Http\Controllers\AdminVisaDocumentController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\VisaApplicationController;
use App\Http\Controllers\VisaPaymentController;
use App\Http\Controllers\VisaPortalController;
use App\Http\Controllers\VisaSearchController;
use App\Http\Middleware\EnsureVisaProductEnabled;
use App\Livewire\Pages\FlightBooking;
use App\Livewire\Pages\FlightIndex;
use App\Livewire\Pages\FlightPage;
use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\Protocol\Protocol as ProtocolPage;
use App\Livewire\Pages\Protocol\ProtocolInternationalPlans;
use App\Livewire\Pages\Protocol\ProtocolPlans;
use App\Livewire\Pages\Lounge\Lounge as LoungePage;
use App\Livewire\Pages\Lounge\LoungeResults;
use App\Livewire\Pages\Lounge\LoungePlan;
use App\Livewire\Pages\Insurance\Insurance as InsurancePage;
use App\Livewire\Pages\Insurance\InsuranceQuote as InsuranceQuotePage;
use App\Livewire\Pages\AirCargo\AirCargo as AirCargoPage;
use App\Livewire\Pages\AirCargo\AirCargoCreate;
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
Route::get('/air/flight', FlightIndex::class)->name('air.flight');
Route::get('/air/flight-s', FlightPage::class)->name('air.flight-s');
// Route::post('/air/flight/search', [FlightPage::class, 'search'])->name('flights.search');
// Route::post('/air/flight/select', [FlightSearchController::class, 'select'])->name('flights.select');

Route::post('/flights/search', [FlightController::class, 'search'])->name('flights.search');
Route::get('/flights/search/loading', [FlightController::class, 'loading'])->name('flights.search.loading');
Route::get('/flights/search/run', [FlightController::class, 'runPendingSearch'])->name('flights.search.run');

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
Route::post('/air/lounge/search', [LoungeController::class, 'lounges'])->name('air.lounges');
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
Route::get('/air/support', function () { /* ... */
})->name('air.support');

Route::get('/admin/travelflex-applications/{application}/documents/{key}', function (\App\Models\TravelFlexApplication $application, string $key) {
    $user = auth()->user();
    $adminEmails = collect(explode(',', (string) env('ADMIN_EMAILS', '')))
        ->map(fn (string $email): string => strtolower(trim($email)))
        ->filter();

    abort_unless($user && ($user->is_admin || $adminEmails->contains(strtolower($user->email))), 403);

    $documents = $application->document_paths ?? [];
    $path = is_array($documents) ? ($documents[$key] ?? null) : null;

    abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
})->middleware('auth')->name('admin.travelflex.documents.download');

Route::get('/admin/reports/export/{report}', AdminReportExportController::class)
    ->middleware('auth')
    ->name('admin.reports.export');

// Flight routes

Route::post('/flights/select', [FlightBookingController::class, 'select'])
    ->name('flights.select');

Route::get('/flights/booking', FlightBooking::class)->name('flights.booking');

Route::post('/flights/book', [FlightBookingController::class, 'book'])
    ->name('flights.book');
Route::get('/flights/payment/gateway', [FlightBookingController::class, 'paymentGateway'])->name('flights.payment.gateway');
Route::post('/flights/payment/gateway/process', [FlightBookingController::class, 'processGatewayPayment'])->name('flights.payment.gateway.process');

// Non-LCC: 3-option payment page (booking already on hold)
Route::get('/flights/payment/options', [FlightBookingController::class, 'paymentOptions'])->name('flights.payment.options');
Route::get('/flights/payment/options/resume/{bookingRef}', [FlightBookingController::class, 'resumePaymentOptions'])
    ->name('flights.payment.options.resume');

// Non-LCC: Bank transfer — user clicks "I have paid"
Route::post('/flights/payment/bank-transfer', [FlightBookingController::class, 'bankTransferNotify'])->name('flights.payment.bank-transfer');

// Non-LCC: Gateway on payment options → ticket_order
Route::post('/flights/payment/gateway-ticket', [FlightBookingController::class, 'processTicketPayment'])->name('flights.payment.gateway-ticket');

// Pending page (bank transfer awaiting verification)
Route::get('/flights/pending', [FlightBookingController::class, 'pending'])->name('flights.pending');

// Final confirmation (WebFare confirmed OR Non-LCC ticketed)
Route::get('/flights/confirmation', [FlightBookingController::class, 'confirmation'])->name('flights.confirmation');
// COMMENTED OUT - Seerbit integration disabled for simulated payments
Route::get('/payments/seerbit/callback', [FlightBookingController::class, 'seerbitCallback'])->name('payments.seerbit.callback');
Route::post('/payments/seerbit/webhook', [FlightBookingController::class, 'seerbitWebhook'])->name('payments.seerbit.webhook');
Route::get('/flights/travelflex', [FlightBookingController::class, 'travelFlex'])->name('flights.travelflex');
Route::post('/flights/travelflex/application', [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application');
Route::get('/flights/travelflex/application', [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application.get');
Route::post('/flights/travelflex/submit-application', [FlightBookingController::class, 'travelFlexSubmitApplication'])->name('flights.travelflex.submit-application');
Route::get('/flights/travelflex/gateway-process', [FlightBookingController::class, 'travelFlexGatewayProcess'])->name('flights.travelflex.gateway-process');
Route::post('/flights/travelflex/bank-transfer', [FlightBookingController::class, 'travelFlexBankTransfer'])->name('flights.travelflex.bank-transfer');
Route::get('/flights/travelflex/bank-transfer-form', [FlightBookingController::class, 'travelFlexBankTransferForm'])->name('flights.travelflex.bank-transfer-form');
Route::get('/flights/travelflex/pending', [FlightBookingController::class, 'travelFlexPending'])->name('flights.travelflex.pending');
Route::get('/flights/travelflex/confirmation', [FlightBookingController::class, 'travelFlexConfirmation'])->name('flights.travelflex.confirmation');
// Route::get('/flights/booking', \App\Livewire\Pages\FlightBooking::class)->name('flights.booking');

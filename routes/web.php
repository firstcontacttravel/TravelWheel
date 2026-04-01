<?php

use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\FlightPage;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\FlightBookingController;
use App\Livewire\Pages\FlightBooking;
use App\Http\Controllers\FlightController;


Route::get('/', HomePage::class)->name('home');
Route::get('/air', HomePage::class)->name('air');

// Add other routes
Route::get('/about-us', function() { /* ... */ })->name('aboutus');
Route::get('/faq', function() { /* ... */ })->name('faq'); 
Route::get('/help', function() { /* ... */ })->name('help');

// Air routes
Route::get('/air/flight', FlightPage::class)->name('air.flight');
Route::get('/air/flight-s', FlightPage::class)->name('air.flight-s');
//Route::post('/air/flight/search', [FlightPage::class, 'search'])->name('flights.search');
// Route::post('/air/flight/select', [FlightSearchController::class, 'select'])->name('flights.select');

Route::post('/flights/search', [FlightController::class, 'search'])->name('flights.search');


//Route::post('/flights/select', [FlightController::class, 'select'])->name('flights.select');

Route::get('/air/hotel', function() { /* ... */ })->name('air.hotel');
Route::get('/air/protocol', function() { /* ... */ })->name('air.protocol');
Route::get('/air/lounge', function() { /* ... */ })->name('air.lounge');
Route::get('/air/insurance', function() { /* ... */ })->name('air.insurance');
Route::get('/air/visa', function() { /* ... */ })->name('air.visa');
Route::get('/air/cargo', function() { /* ... */ })->name('air.cargo');
Route::get('/air/support', function() { /* ... */ })->name('air.support');

// Flight routes
 
Route::post('/flights/select', [FlightBookingController::class, 'select'])
    ->name('flights.select');
 
Route::get('/flights/booking', FlightBooking::class) ->name('flights.booking');
 
Route::post('/flights/book', [FlightBookingController::class, 'book'])
    ->name('flights.book');
    Route::get( '/flights/payment/gateway',         [FlightBookingController::class, 'paymentGateway'])->name('flights.payment.gateway');
    Route::post('/flights/payment/gateway/process', [FlightBookingController::class, 'processGatewayPayment'])->name('flights.payment.gateway.process');
     
    // Non-LCC: 3-option payment page (booking already on hold)
    Route::get( '/flights/payment/options',         [FlightBookingController::class, 'paymentOptions'])->name('flights.payment.options');
     
    // Non-LCC: Bank transfer — user clicks "I have paid"
    Route::post('/flights/payment/bank-transfer',   [FlightBookingController::class, 'bankTransferNotify'])->name('flights.payment.bank-transfer');
     
    // Non-LCC: Gateway on payment options → ticket_order
    Route::post('/flights/payment/gateway-ticket',  [FlightBookingController::class, 'processTicketPayment'])->name('flights.payment.gateway-ticket');
     
    // Pending page (bank transfer awaiting verification)
    Route::get('/flights/pending',       [FlightBookingController::class, 'pending'])->name('flights.pending');
     
    // Final confirmation (WebFare confirmed OR Non-LCC ticketed)
    Route::get('/flights/confirmation',  [FlightBookingController::class, 'confirmation'])->name('flights.confirmation');
    Route::get( '/flights/travelflex',                       [FlightBookingController::class, 'travelFlex'])->name('flights.travelflex');
    Route::post('/flights/travelflex/application',           [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application');
    Route::get( '/flights/travelflex/application',           [FlightBookingController::class, 'travelFlexApplication'])->name('flights.travelflex.application.get');
    Route::post('/flights/travelflex/submit-application',    [FlightBookingController::class, 'travelFlexSubmitApplication'])->name('flights.travelflex.submit-application');
    Route::get( '/flights/travelflex/gateway-process',       [FlightBookingController::class, 'travelFlexGatewayProcess'])->name('flights.travelflex.gateway-process');
    Route::post('/flights/travelflex/bank-transfer',         [FlightBookingController::class, 'travelFlexBankTransfer'])->name('flights.travelflex.bank-transfer');
    Route::get( '/flights/travelflex/bank-transfer-form',    [FlightBookingController::class, 'travelFlexBankTransferForm'])->name('flights.travelflex.bank-transfer-form');
    Route::get( '/flights/travelflex/pending',               [FlightBookingController::class, 'travelFlexPending'])->name('flights.travelflex.pending');
    Route::get( '/flights/travelflex/confirmation',          [FlightBookingController::class, 'travelFlexConfirmation'])->name('flights.travelflex.confirmation');
//Route::get('/flights/booking', \App\Livewire\Pages\FlightBooking::class)->name('flights.booking');
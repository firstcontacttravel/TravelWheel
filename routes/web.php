<?php

use App\Livewire\Pages\HomePage;
use App\Livewire\Pages\FlightPage;
use App\Http\Controllers\FlightSearchController;

Route::get('/', HomePage::class)->name('home');
Route::get('/air', HomePage::class)->name('air');

// Add other routes
Route::get('/about-us', function() { /* ... */ })->name('aboutus');
Route::get('/faq', function() { /* ... */ })->name('faq'); 
Route::get('/help', function() { /* ... */ })->name('help');

// Air routes
Route::get('/air/flight', FlightPage::class)->name('air.flight');
Route::get('/air/flight-s', FlightPage::class)->name('air.flight-s');
Route::post('/air/flight/search', [FlightPage::class, 'search'])->name('flights.search');
Route::post('/air/flight/select', [FlightSearchController::class, 'select'])->name('flights.select');


Route::get('/air/hotel', function() { /* ... */ })->name('air.hotel');
Route::get('/air/protocol', function() { /* ... */ })->name('air.protocol');
Route::get('/air/lounge', function() { /* ... */ })->name('air.lounge');
Route::get('/air/insurance', function() { /* ... */ })->name('air.insurance');
Route::get('/air/visa', function() { /* ... */ })->name('air.visa');
Route::get('/air/cargo', function() { /* ... */ })->name('air.cargo');
Route::get('/air/support', function() { /* ... */ })->name('air.support');

// Flight routes
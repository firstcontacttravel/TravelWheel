<?php

use App\Livewire\Pages\HomePage;

Route::get('/', HomePage::class)->name('home');
Route::get('/air', HomePage::class)->name('air');

// Add other routes
Route::get('/about-us', function() { /* ... */ })->name('aboutus');
Route::get('/faq', function() { /* ... */ })->name('faq');
Route::get('/help', function() { /* ... */ })->name('help');

// Air routes
Route::get('/air/flight', function() { /* ... */ })->name('air.flight');
Route::get('/air/hotel', function() { /* ... */ })->name('air.hotel');
Route::get('/air/protocol', function() { /* ... */ })->name('air.protocol');
Route::get('/air/lounge', function() { /* ... */ })->name('air.lounge');
Route::get('/air/insurance', function() { /* ... */ })->name('air.insurance');
Route::get('/air/visa', function() { /* ... */ })->name('air.visa');
Route::get('/air/cargo', function() { /* ... */ })->name('air.cargo');
Route::get('/air/support', function() { /* ... */ })->name('air.support');
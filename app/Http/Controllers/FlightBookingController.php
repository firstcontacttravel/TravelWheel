<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FlightBookingController extends Controller
{
    public function select(Request $request)
    {
        $validated = $request->validate([
            'fare_source_code' => 'required|string',
            'session_id'       => 'required|string',
        ]);

        // Read from the PERSISTENT session key (not flash)
        $allFlights = session('flightResultsStore', []);

        $flight = collect($allFlights)
                    ->firstWhere('fareSourceCode', $validated['fare_source_code']);

        if (! $flight) {
            return redirect()->route('air.flight-s')
                ->withErrors(['error' => 'Selected flight not found. Please search again.']);
        }

        session([
            'bookingFlight'       => $flight,
            'bookingSessionId'    => $validated['session_id'],
            'bookingSearchParams' => session('searchParamsStore', []),
        ]);

        return redirect()->route('flights.booking');
    }

    public function booking()
    {
        if (! session()->has('bookingFlight')) {
            return redirect()->route('air.flight-s')
                ->withErrors(['error' => 'No flight selected. Please search again.']);
        }

        return view('livewire.pages.flight.flight-booking');
    }

    public function book(Request $request)
    {
        $validated = $request->validate([
            'fare_source_code'          => 'required|string',
            'session_id'                => 'required|string',
            'contact.email'             => 'required|email',
            'contact.phone'             => 'required|string|min:7',
            'passengers'                => 'required|array|min:1',
            'passengers.*.type'         => 'required|in:ADT,CHD,INF',
            'passengers.*.title'        => 'required|in:Mr,Mrs,Ms,Miss,Dr',
            'passengers.*.first_name'   => 'required|string|max:100',
            'passengers.*.last_name'    => 'required|string|max:100',
            'passengers.*.dob'          => 'required|date',
            'passengers.*.nationality'  => 'required|string|size:2',
            'passengers.*.passport_no'  => 'nullable|string|max:20',
            'passengers.*.passport_exp' => 'nullable|date|after:today',
        ]);

        // ── TODO: wire up TravelNext booking API here ─────────────────────────
        // $response = Http::timeout(60)->post('https://travelnext.works/api/aeroVE5/book', [
        //     'user_id'        => config('services.travelnext.user_id'),
        //     'user_password'  => config('services.travelnext.password'),
        //     'access'         => config('services.travelnext.access'),
        //     'ip_address'     => config('services.travelnext.ip'),
        //     'FareSourceCode' => $validated['fare_source_code'],
        //     'session_id'     => $validated['session_id'],
        //     'passengers'     => $validated['passengers'],
        //     'contact'        => $validated['contact'],
        // ]);
        // if ($response->failed()) {
        //     return back()->withErrors(['error' => 'Booking failed. Please try again.']);
        // }
        // session(['bookingConfirmation' => $response->json()]);
        // return redirect()->route('flights.confirmation');
        // ─────────────────────────────────────────────────────────────────────

        return back()->with('stub_notice',
            'Booking API not yet connected. Fare: ' . substr($validated['fare_source_code'], 0, 20) . '...'
        );
    }
}
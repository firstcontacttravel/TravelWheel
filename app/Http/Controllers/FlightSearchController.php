<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;


class FlightSearchController extends Controller
{
    public function search(Request $request)
    {
        //dd($request->all());
        // Validate the incoming request data
        $validatedData = $request->validate([
            'trip' => 'required|string|max:255',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255',
            'depart' => 'required|date',
            // 'returning' => 'nullable|date|after_or_equal:depart',
            // 'adults' => 'required|integer|min:1',
            // 'childs' => 'nullable|integer|min:0',
            // 'kids' => 'nullable|integer|min:0',
            // 'flight_type' => 'required|string|in:Y,S,C,F',
            // 'multi_legs' => 'nullable|array',
            // 'departure_date' => 'required|date',
            // 'return_date' => 'nullable|date|after_or_equal:departure_date',
            // 'passengers' => 'required|integer|min:1',
            // 'class' => 'required|string|in:economy,business,first',
        ]);

        // Here you would typically call a service to perform the flight search
        // For demonstration, we'll return a dummy response

        $flights = [
            [
                'airline' => 'Airline A',
                'flight_number' => 'AA123',
                'departure_time' => '2024-07-01T08:00:00',
                'arrival_time' => '2024-07-01T12:00:00',
                'price' => 200.00,
                'currency' => 'USD',
            ],
            [
                'airline' => 'Airline B',
                'flight_number' => 'BB456',
                'departure_time' => '2024-07-01T09:00:00',
                'arrival_time' => '2024-07-01T13:00:00',
                'price' => 250.00,
                'currency' => 'USD',
            ],
            [
                'airline' => 'Airline C',
                'flight_number' => 'CC789',
                'departure_time' => '2024-07-01T10:00:00',
                'arrival_time' => '2024-07-01T14:00:00',
                'price' => 300.00,
                'currency' => 'USD',
            ],
            [
                'airline' => 'Airline D',
                'flight_number' => 'DD101',
                'departure_time' => '2024-07-01T11:00:00',
                'arrival_time' => '2024-07-01T15:00:00',
                'price' => 350.00,
                'currency' => 'USD',
            ],
            [
                'airline' => 'Airline E',
                'flight_number' => 'EE202',
                'departure_time' => '2024-07-01T12:00:00',
                'arrival_time' => '2024-07-01T16:00:00',
                'price' => 400.00,
                'currency' => 'USD',
            ],
            [
                'airline' => 'Airline F',
                'flight_number' => 'FF303',
                'departure_time' => '2024-07-01T13:00:00',
                'arrival_time' => '2024-07-01T17:00:00',
                'price' => 450.00,
                'currency' => 'USD',
            ]

        ];


        //dd($flights);
        return view('livewire.flight-result', compact('flights'));
        
    }

    public function select(Request $request)
    {
        dd($request->all());
        // Validate the incoming request data
        $validated = $request->validate([
            'flight_id' => 'required|string|max:255',
            // Add other necessary validation rules based on your flight data structure
        ]);

        // Here you would typically call a service to retrieve the selected flight details
        // For demonstration, we'll return a dummy response

        $jsonPath = storage_path('app/public/flight-results.json');
        $jsonAirlinesPath = storage_path('app/public/airlines.json');

        $jsonAirlinesData = json_decode(file_get_contents($jsonAirlinesPath), true);

        if (!file_exists($jsonPath)) {
            return back()->with('error', 'Selected flight data not found.');
        }
    }
}
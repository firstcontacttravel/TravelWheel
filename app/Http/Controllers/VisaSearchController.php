<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\VisaDiscoveryService;
use App\Services\VisaFunnelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisaSearchController extends Controller
{
    public function search(Request $request, VisaFunnelService $funnel): RedirectResponse
    {
        $validated = $request->validate($this->rules(), [
            'infants.lte' => 'The number of infants cannot exceed the number of adults.',
        ]);

        session()->forget(['visaResultsStore', 'visaSearchParamsStore']);
        session(['pendingVisaSearch' => $validated, 'pendingVisaSearchStartedAt' => now()->toIso8601String()]);
        $funnel->record('search_started', ['nationality_id' => $validated['nationality_id'], 'destination_id' => $validated['destination_id'], 'travelers' => $validated['adults'] + $validated['children'] + $validated['infants']]);

        return redirect()->route('visa.search.loading');
    }

    public function loading(): View|RedirectResponse
    {
        $search = session('pendingVisaSearch');
        if (! is_array($search) || $search === []) {
            return redirect()->route('air.visa')->withErrors(['error' => 'Please start a new visa search.']);
        }

        return view('visa.loading', [
            'search' => $search,
            'nationality' => Country::query()->find($search['nationality_id']),
            'residence' => filled($search['residence_country_id'] ?? null) ? Country::query()->find($search['residence_country_id']) : null,
            'destination' => Country::query()->find($search['destination_id']),
        ]);
    }

    public function runPendingSearch(VisaDiscoveryService $discovery, VisaFunnelService $funnel): RedirectResponse
    {
        $search = session('pendingVisaSearch');
        if (! is_array($search) || $search === []) {
            return redirect()->route('air.visa')->withErrors(['error' => 'Visa search expired. Please search again.']);
        }

        $nationality = Country::query()->findOrFail($search['nationality_id']);
        $destination = Country::query()->findOrFail($search['destination_id']);
        $residence = filled($search['residence_country_id'] ?? null) ? Country::query()->findOrFail($search['residence_country_id']) : null;
        $travelers = ['adult' => (int) $search['adults'], 'child' => (int) $search['children'], 'infant' => (int) $search['infants']];

        $results = $discovery->search($nationality, $destination, $residence, $travelers, [
            'arrival_date' => $search['arrival_date'],
            'departure_date' => $search['departure_date'],
            'travelers' => $travelers,
        ])->all();

        session()->forget(['pendingVisaSearch', 'pendingVisaSearchStartedAt']);
        session([
            'visaResultsStore' => $results,
            'visaSearchParamsStore' => array_merge($search, [
                'nationality_name' => $nationality->name,
                'destination_name' => $destination->name,
                'residence_name' => $residence?->name,
                'travelers' => $travelers,
            ]),
        ]);
        $funnel->record('search_completed', ['destination_id' => $destination->id, 'result_count' => count($results)]);

        return redirect()->route('visa.results');
    }

    private function rules(): array
    {
        return [
            'nationality_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
            'residence_country_id' => ['nullable', Rule::exists('countries', 'id')->where('is_active', true)],
            'destination_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
            'arrival_date' => ['required', 'date', 'after_or_equal:today'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['required', 'integer', 'min:0', 'max:9'],
            'infants' => ['required', 'integer', 'min:0', 'max:9', 'lte:adults'],
        ];
    }
}

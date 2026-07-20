<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\VisaDestination;
use App\Services\VisaDiscoveryService;
use App\Services\VisaFunnelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VisaSearchController extends Controller
{
    public function destinations(Request $request, VisaDiscoveryService $discovery): JsonResponse
    {
        $validated = $request->validate([
            'nationality_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
        ]);

        $nationality = Country::query()->where('is_active', true)->findOrFail($validated['nationality_id']);
        $destinations = $discovery->availableDestinationsForNationality($nationality);

        return response()->json([
            'countries' => $destinations['countries']->values(),
            'regions' => $destinations['regions']->values(),
        ]);
    }

    public function search(Request $request, VisaFunnelService $funnel, VisaDiscoveryService $discovery): RedirectResponse
    {
        $validated = $request->validate($this->rules(), [
            'infants.lte' => 'The number of infants cannot exceed the number of adults.',
        ]);
        [$destinationType, $destination] = $this->resolveDestination($validated);
        $nationality = Country::query()->where('is_active', true)->findOrFail($validated['nationality_id']);

        if ($destinationType === 'country' && (int) $validated['nationality_id'] === (int) $destination->id) {
            throw ValidationException::withMessages([
                'destination_ref' => 'Your passport nationality and destination cannot be the same country.',
            ]);
        }

        if (! $discovery->destinationIsAvailableForNationality($nationality, $destination)) {
            throw ValidationException::withMessages([
                'destination_ref' => 'This destination is not currently available for the selected passport nationality.',
            ]);
        }

        $validated['destination_type'] = $destinationType;
        $validated['destination_id'] = $destinationType === 'country' ? $destination->id : null;
        $validated['visa_destination_id'] = $destinationType === 'region' ? $destination->id : null;
        $validated['destination_ref'] = $destinationType.':'.$destination->id;

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
            'destination' => ($search['destination_type'] ?? 'country') === 'region'
                ? VisaDestination::query()->find($search['visa_destination_id'])
                : Country::query()->find($search['destination_id']),
        ]);
    }

    public function runPendingSearch(VisaDiscoveryService $discovery, VisaFunnelService $funnel): RedirectResponse
    {
        $search = session('pendingVisaSearch');
        if (! is_array($search) || $search === []) {
            return redirect()->route('air.visa')->withErrors(['error' => 'Visa search expired. Please search again.']);
        }

        $nationality = Country::query()->findOrFail($search['nationality_id']);
        $destination = ($search['destination_type'] ?? 'country') === 'region'
            ? VisaDestination::query()->findOrFail($search['visa_destination_id'])
            : Country::query()->findOrFail($search['destination_id']);
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
            'destination_ref' => ['nullable', 'required_without:destination_id', 'regex:/^(country|region):[1-9][0-9]*$/'],
            'destination_id' => ['nullable', 'required_without:destination_ref', Rule::exists('countries', 'id')->where('is_active', true)],
            'arrival_date' => ['required', 'date', 'after_or_equal:today'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['required', 'integer', 'min:0', 'max:9'],
            'infants' => ['required', 'integer', 'min:0', 'max:9', 'lte:adults'],
        ];
    }

    private function resolveDestination(array $search): array
    {
        if (filled($search['destination_ref'] ?? null)) {
            [$type, $id] = explode(':', $search['destination_ref'], 2);
            $destination = $type === 'region'
                ? VisaDestination::query()->where('is_active', true)->findOrFail($id)
                : Country::query()->where('is_active', true)->findOrFail($id);

            return [$type, $destination];
        }

        return ['country', Country::query()->where('is_active', true)->findOrFail($search['destination_id'])];
    }
}

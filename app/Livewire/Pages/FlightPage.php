<?php

namespace App\Livewire\Pages;

use App\Services\Flights\FlightSearchStore;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\FlightSupplierRegistry;
use App\Services\TravelnextFlightService;
use Livewire\Attributes\Renderless;
use App\Support\FlightMarkup;
use App\Support\FlightMatch;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class FlightPage extends Component
{
    /**
     * Nothing on this component is public state.
     *
     * Livewire serialises every public property into the wire:snapshot it
     * embeds in the page AND ships it back and forth on every subsequent
     * request. The search results are the biggest object on this page and are
     * needed exactly once — at first paint, to seed the Alpine component in
     * flight-result.blade.php. Holding them publicly meant the wire:init call
     * that fetches SkyLink first uploaded the entire TravelNext result set and
     * then had it echoed back: measured against a representative mapped flight
     * (2 segments plus fare breakdown, ~2.9 KB), a 40-flight result set is
     * ~117 KB each way, on top of ~199 KB of duplicate snapshot in the initial
     * HTML — pure overhead on a mobile uplink before SkyLink is even called.
     *
     * FlightController::search() writes these durable session keys before
     * redirecting here, so reading them per-render costs nothing and survives
     * refreshes, back-button navigation and Livewire re-renders just as well.
     * Reading the criteria from the session inside the action is also strictly
     * safer than trusting anything that made a round trip through the client.
     */
    /**
     * Only fares from APIs that are still switched on: one switched off since
     * the search can no longer be booked, so its fares leave the page on the
     * next load. matchKey is added for searches stored before it existed.
     */
    protected function flightResults(): array
    {
        $enabled = app(FlightSupplierControl::class)->enabledKeys();

        return array_values(array_filter(
            FlightMatch::tag(array_values(array_filter(session('flightResultsStore', []), 'is_array'))),
            fn (array $flight): bool => in_array($flight['source'] ?? TravelnextFlightService::KEY, $enabled, true),
        ));
    }

    protected function searchParams(): array
    {
        return session('searchParamsStore', []);
    }

    /**
     * Fired via wire:init right after the initial render — the first page of
     * results is already on screen by the time this runs, so a slow or failing
     * supplement never delays or breaks the page. The mapped flights are
     * pushed to the browser as an event; the Alpine component in
     * flight-result.blade.php merges/dedupes/re-sorts them into the visible
     * list.
     *
     * Renderless: this call exists only to hand the browser an event. Alpine
     * does the merging, so nothing Blade renders changes as a result of it —
     * but Livewire re-renders the component after every call by default, and
     * that re-render was the single largest thing in the response. Measured on
     * staging, a round trip came back with 1.24 MB of re-rendered HTML (a 69 KB
     * inline <style> block, plus a second full copy of the flight list inlined
     * by @js) on top of the 834 KB of flights actually asked for.
     *
     * Skipping it also removes a hazard rather than just weight: that HTML gets
     * morphed over the live DOM, re-running the @js seed underneath an Alpine
     * component that has already merged the supplements into its own state.
     */
    #[Renderless]
    public function loadSupplementalResults(): void
    {
        $this->dispatch('supplier-results-ready', flights: $this->supplementalFlights());
    }

    /**
     * Searches every switched-on API that the loading page didn't reach —
     * FlightController::performSearch() stops at the first one with flights
     * and lists the rest in searchSupplementSuppliers.
     *
     * Each supplier's results are kept in supplementResultsStore[key],
     * REPLACED rather than appended on every call: a page reload re-fires
     * wire:init, and each call is a fresh, authoritative search for the same
     * criteria, so the previous batch is simply stale. They are kept out of
     * flightResultsStore, which seeds the next page load's initial (undeduped)
     * paint. FlightBookingController::select() finds fares in both.
     *
     * Every failure mode yields no flights rather than propagating. A
     * supplement can never degrade the page, so the guarantee covers the
     * mapping and session write too — not just the HTTP call.
     */
    private function supplementalFlights(): array
    {
        $searchParams = $this->searchParams();

        // A parallel search's page fetches every API itself.
        if ($searchParams === [] || app(FlightSearchStore::class)->currentId() !== null) {
            return [];
        }

        $control = app(FlightSupplierControl::class);
        $registry = app(FlightSupplierRegistry::class);
        $flights = [];
        $stores = [];
        $meta = session('supplierSearchMeta', []);

        foreach ($this->supplementKeys($control) as $key) {
            // Checked again here: an admin may have switched it off since the
            // search began.
            if (! $control->isEnabled($key)) {
                continue;
            }

            try {
                $result = $registry->get($key)->search($searchParams);

                if ($result['error'] ?? true) {
                    continue;
                }

                $mapped = FlightMatch::tag(array_values(array_map(
                    fn (array $flight): array => FlightMarkup::apply($flight),
                    (array) data_get($result, 'data.flights', [])
                )));

                $stores[$key] = $mapped;
                $meta[$key] = (array) data_get($result, 'data.meta', []);
                array_push($flights, ...$mapped);
            } catch (Throwable $exception) {
                Log::warning('Supplemental flight search failed', [
                    'supplier' => $key,
                    'error' => $exception->getMessage(),
                    'exception' => $exception::class,
                ]);
            }
        }

        try {
            session(['supplementResultsStore' => $stores, 'supplierSearchMeta' => $meta]);
        } catch (Throwable $exception) {
            Log::warning('Supplemental flight results could not be stored', ['error' => $exception->getMessage()]);

            return [];
        }

        return $flights;
    }

    /**
     * The APIs to search as supplements: normally the list the loading page
     * left in searchSupplementSuppliers.
     *
     * Two cases widen it to every switched-on API not already on the page:
     *   - the first page lost flights because their API has since been
     *     switched off — the customer would otherwise be left with less, or
     *     nothing, when another API could fill the gap;
     *   - a search made before the list existed — which is exactly what the
     *     list would have held. An empty first page there was TravelNext's.
     */
    private function supplementKeys(FlightSupplierControl $control): array
    {
        $listed = session('searchSupplementSuppliers');
        $stored = array_filter(session('flightResultsStore', []), 'is_array');
        $visible = $this->flightResults();

        if (is_array($listed) && count($visible) === count($stored)) {
            return $listed;
        }

        $onPage = collect($visible)
            ->map(fn (array $flight): string => (string) ($flight['source'] ?? TravelnextFlightService::KEY))
            ->unique()
            ->values()
            ->all();

        if ($onPage === [] && $stored === [] && ! is_array($listed)) {
            $onPage = [TravelnextFlightService::KEY];
        }

        return array_values(array_unique(array_merge(
            is_array($listed) ? $listed : [],
            array_diff($control->enabledKeys(), $onPage),
        )));
    }

    /**
     * For a parallel search: the id, and one URL per switched-on API for the
     * page to fetch side by side (FlightSupplierSearchController). Null for a
     * search that ran through the loading page.
     *
     * The APIs are those switched on NOW, not when the search began: one
     * switched off since contributes nothing, one switched on since is
     * searched too.
     */
    private function parallelSearch(): ?array
    {
        $searchId = app(FlightSearchStore::class)->currentId();

        if ($searchId === null) {
            return null;
        }

        return [
            'searchId' => $searchId,
            'endpoints' => collect(app(FlightSupplierControl::class)->enabledKeys())
                ->mapWithKeys(fn (string $key): array => [$key => route('flights.search.supplier', [
                    'search' => $searchId,
                    'supplier' => $key,
                ])])
                ->all(),
        ];
    }

    public function render()
    {
        $parallel = $this->parallelSearch();

        return view('livewire.pages.flight.flight-page-result', [
            // A parallel search starts empty; flights arrive per API.
            'flightResults' => $parallel === null ? $this->flightResults() : [],
            'searchParams' => $this->searchParams(),
            'searchSessionId' => session('searchSessionId', ''),
            'parallel' => $parallel,
            // Priority order: breaks exact price ties between two APIs'
            // copies of the same flight in the page's dedupe.
            'supplierOrder' => app(FlightSupplierControl::class)->enabledKeys(),
            // Whether "Searching more airlines…" should show at all: when the
            // loading page already tried every API there is nothing to wait for.
            'expectsSupplements' => $parallel !== null
                ? $parallel['endpoints'] !== []
                : $this->searchParams() !== [] && $this->supplementKeys(app(FlightSupplierControl::class)) !== [],
        ]);
    }
}

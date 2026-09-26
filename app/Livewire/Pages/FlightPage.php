<?php

namespace App\Livewire\Pages;

use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\FlightSupplierRegistry;
use App\Services\TravelnextFlightService;
use Livewire\Attributes\Renderless;
use App\Support\FlightMarkup;
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
    protected function flightResults(): array
    {
        return session('flightResultsStore', []);
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

        if ($searchParams === []) {
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

                $mapped = array_values(array_map(
                    fn (array $flight): array => FlightMarkup::apply($flight),
                    (array) data_get($result, 'data.flights', [])
                ));

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
     * A search made before searchSupplementSuppliers existed has no list; for
     * those, every switched-on API whose flights aren't already on the page
     * is a supplement — which is exactly what that list would have held.
     */
    private function supplementKeys(FlightSupplierControl $control): array
    {
        $listed = session('searchSupplementSuppliers');

        if (is_array($listed)) {
            return $listed;
        }

        $onPage = collect($this->flightResults())
            ->map(fn ($flight): string => (string) (is_array($flight) ? ($flight['source'] ?? TravelnextFlightService::KEY) : ''))
            ->unique()
            ->all();

        return array_values(array_diff($control->enabledKeys(), $onPage === [] ? [TravelnextFlightService::KEY] : $onPage));
    }

    public function render()
    {
        return view('livewire.pages.flight.flight-page-result', [
            'flightResults' => $this->flightResults(),
            'searchParams' => $this->searchParams(),
            'searchSessionId' => session('searchSessionId', ''),
            // Whether "Searching more airlines…" should show at all: when the
            // loading page already tried every API there is nothing to wait for.
            'expectsSupplements' => $this->searchParams() !== []
                && $this->supplementKeys(app(FlightSupplierControl::class)) !== [],
        ]);
    }
}

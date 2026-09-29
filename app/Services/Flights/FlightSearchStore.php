<?php

namespace App\Services\Flights;

use App\Models\FlightSearch;
use App\Models\FlightSearchResult;
use App\Services\TravelnextFlightService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Where a customer's search results live, whichever way the search ran.
 *
 * Sequential search (the loading page) keeps them in the session:
 * flightResultsStore for the first page, supplementResultsStore[key] and
 * supplierSearchMeta[key] for the rest. Parallel search keeps them in
 * flight_searches / flight_search_results under the search's id, because
 * its per-API requests run concurrently and without a session.
 *
 * Everything that needs "the flights this customer was shown" — select(),
 * FlightBookingGuard's alternates — asks here, so neither cares which mode
 * produced them.
 */
class FlightSearchStore
{
    public const SESSION_ID = 'flightSearchId';

    public const SESSION_MODE = 'flightSearchMode';

    // =========================================================================
    //  Parallel searches
    // =========================================================================

    public function start(array $criteria): string
    {
        $search = FlightSearch::query()->create([
            'id' => (string) Str::uuid(),
            'criteria' => $criteria,
            'created_at' => now(),
            'expires_at' => now()->addMinutes($this->searchMinutes()),
        ]);

        return $search->id;
    }

    /** The validated search form, or null once the search has expired. */
    public function criteria(string $searchId): ?array
    {
        return FlightSearch::query()
            ->whereKey($searchId)
            ->where('expires_at', '>', now())
            ->first()
            ?->criteria;
    }

    /**
     * @return array{flights: list<array>, meta: array}|null
     */
    public function result(string $searchId, string $supplierKey): ?array
    {
        $row = FlightSearchResult::query()
            ->where('flight_search_id', $searchId)
            ->where('supplier', $supplierKey)
            ->where('expires_at', '>', now())
            ->first();

        return $row === null ? null : [
            'flights' => $this->decode($row->flights),
            'meta' => $row->meta ?? [],
        ];
    }

    /**
     * Replaces any earlier answer from this API: each is a complete, fresh
     * result for the same criteria.
     */
    public function put(string $searchId, string $supplierKey, array $flights, array $meta = []): void
    {
        FlightSearchResult::query()->updateOrCreate(
            ['flight_search_id' => $searchId, 'supplier' => $supplierKey],
            [
                'flights' => $this->encode($flights),
                'meta' => $meta,
                'flight_count' => count($flights),
                'created_at' => now(),
                'expires_at' => now()->addMinutes($this->resultMinutes()),
            ],
        );
    }

    // =========================================================================
    //  The current customer's search, either mode
    // =========================================================================

    public function currentId(): ?string
    {
        return session(self::SESSION_MODE) === 'parallel' ? (session(self::SESSION_ID) ?: null) : null;
    }

    /** Every flight this customer's current search found, from every API. */
    public function flights(): Collection
    {
        $flights = collect(session('flightResultsStore', []))
            ->merge(collect(session('supplementResultsStore', []))->flatten(1))
            // Where SkyLink supplements lived before supplementResultsStore.
            ->merge(session('skylinkResultsStore', []));

        if ($searchId = $this->currentId()) {
            FlightSearchResult::query()
                ->where('flight_search_id', $searchId)
                ->where('expires_at', '>', now())
                ->get()
                ->each(function (FlightSearchResult $row) use (&$flights): void {
                    $flights = $flights->merge($this->decode($row->flights));
                });
        }

        return $flights->filter(fn ($flight): bool => is_array($flight))->values();
    }

    /**
     * One fare, matched on supplier as well as fare code, so a code that
     * exists at two suppliers can never resolve to the wrong one. A flight
     * with no `source` predates the tag and is TravelNext's.
     */
    public function find(string $supplierKey, string $fareSourceCode): ?array
    {
        return $this->flights()->first(fn (array $flight): bool => ($flight['fareSourceCode'] ?? null) === $fareSourceCode
            && ($flight['source'] ?? TravelnextFlightService::KEY) === $supplierKey);
    }

    /** What the API said about the search itself — TravelNext's session_id. */
    public function meta(string $supplierKey): array
    {
        $fromSession = session("supplierSearchMeta.{$supplierKey}");

        if (is_array($fromSession) && $fromSession !== []) {
            return $fromSession;
        }

        $searchId = $this->currentId();

        return $searchId === null ? [] : ($this->result($searchId, $supplierKey)['meta'] ?? []);
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================

    private function encode(array $flights): string
    {
        return base64_encode(gzcompress(json_encode($flights, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION), 6));
    }

    private function decode(?string $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        $json = @gzuncompress((string) base64_decode($payload, true));

        return is_string($json) ? (json_decode($json, true) ?: []) : [];
    }

    private function searchMinutes(): int
    {
        return max(10, (int) config('flights.search_minutes', 120));
    }

    private function resultMinutes(): int
    {
        return max(1, (int) config('flights.result_minutes', 20));
    }
}

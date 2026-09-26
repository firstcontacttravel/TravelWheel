<?php

namespace App\Contracts;

/**
 * One flight API (TravelNext, SkyLink, ...), as the search and checkout flow
 * sees it. Resolved by key through App\Services\Flights\FlightSupplierRegistry.
 *
 * Every method returns the same envelope the suppliers already used:
 *   ['error' => bool, 'message' => ?string, 'data' => array]
 *
 * Flights come back in the canonical mapped shape the results page, booking
 * views and FlightMarkup all read, priced at the SUPPLIER's price in USD —
 * markup is the caller's job, applied after, so a pricing change never has to
 * touch a supplier. Every flight carries `source` = key().
 *
 * Deliberately not here yet: booking, ticketing and post-ticketing. The two
 * suppliers do them in fundamentally different orders (TravelNext holds, then
 * takes payment; SkyLink takes payment, then reserves) and nothing yet needs
 * to call them without knowing which supplier it is talking to. They stay as
 * supplier-specific methods until a third API shows what they have in common.
 */
interface FlightSupplier
{
    /** Stable identifier stored on bookings (flight_bookings.supplier). */
    public function key(): string;

    /** Human-readable name for admin screens. */
    public function label(): string;

    /**
     * Whether a booking can be held with the airline before payment — the
     * property TravelFlex's review window depends on.
     */
    public function supportsHold(): bool;

    /**
     * $criteria is the validated search form (trip, from, to, depart,
     * returning, multi_legs, adults, childs, kids, flight_type).
     * $context carries search_id and started_at for logging.
     *
     * data: ['flights' => list<array>, 'meta' => array]
     */
    public function search(array $criteria, array $context = []): array;

    /**
     * Re-confirms one fare before checkout.
     *
     * $searchedFlight is the flight as the customer saw it on the results
     * page (already marked up), or null when it can't be found.
     * $context carries session_id and anything else the supplier needs.
     *
     * data: ['flight' => array, 'extraServices' => array, 'fareRules' => array]
     *
     * An error result also carries 'surface' => 'flash'|'errors' — how the
     * failure has always been reported back to the results page. Preserved
     * so this move changes nothing a customer sees.
     */
    public function select(string $fareSourceCode, ?array $searchedFlight, array $criteria, array $context = []): array;
}

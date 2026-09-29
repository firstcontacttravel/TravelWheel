<?php

namespace App\Services\Flights;

use App\Models\FlightBooking;
use App\Support\FlightMarkup;
use App\Support\FlightMatch;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Holding a TravelFlex booking on our side, for an API that can't hold
 * seats (FlightSupplier::supportsHold() false — SkyLink today).
 *
 * The booking waits here while Fast Credit reviews it; nothing exists at the
 * supplier and no seat is reserved. When the approved customer comes to pay,
 * reconfirm() finds the same flight again with a fresh search — the price
 * quote from the original search expired within minutes — and re-prices it.
 * If it is gone, or now costs more than was approved, nothing is charged.
 * The supplier booking itself is only made after payment.
 */
class FlightPlatformHold
{
    public const UNAVAILABLE = 'This flight is no longer available at the approved fare, so no payment was taken. Please contact TravelWheel to choose another flight.';

    public const PRICE_INCREASED = 'The fare for this flight has gone up since your application was approved, so no payment was taken. Please choose the flight again or contact TravelWheel.';

    public const UNCONFIRMED = 'We could not confirm this fare right now, so no payment was taken. Please try again in a few minutes.';

    public function __construct(
        private readonly FlightSupplierRegistry $registry,
        private readonly FlightSupplierControl $control,
    ) {}

    /** The booking's supplier can't hold seats, so any hold is ours. */
    public function applies(FlightBooking|array $bookingOrFlight): bool
    {
        $key = $bookingOrFlight instanceof FlightBooking
            ? ($bookingOrFlight->supplier ?: 'travelnext')
            : (string) ($bookingOrFlight['source'] ?? 'travelnext');

        return $this->registry->has($key) && ! $this->registry->get($key)->supportsHold();
    }

    public function holdUntil(): CarbonInterface
    {
        return now()->addHours(max(1, (int) config('flights.platform_hold.hold_hours', 72)));
    }

    /**
     * Finds the held flight again and re-prices it.
     *
     * ok: the fresh flight (marked up) — its fareSourceCode is a live token
     * for the supplier's next call. Otherwise reason is unavailable,
     * price_increased or unconfirmed, with the customer-facing message.
     *
     * @return array{ok: bool, reason?: string, message?: string, flight?: array, approved_price?: float, current_price?: float}
     */
    public function reconfirm(FlightBooking $booking): array
    {
        $key = $booking->supplier ?: 'travelnext';
        $held = (array) ($booking->flight_snapshot ?? []);

        try {
            if (! $this->control->isEnabled($key)) {
                return $this->refuse('unconfirmed', $booking, 'API switched off');
            }

            $supplier = $this->registry->get($key);
            $criteria = $this->criteriaFor($booking, $held);
            $search = $supplier->search($criteria);

            if ($search['error'] ?? true) {
                return $this->refuse('unconfirmed', $booking, 'search failed: '.($search['message'] ?? ''));
            }

            $matchKey = FlightMatch::key($held);
            $candidate = collect((array) ($search['data']['flights'] ?? []))
                ->filter(fn ($flight): bool => is_array($flight) && FlightMatch::key($flight) === $matchKey)
                ->sortBy(fn (array $flight): float => (float) ($flight['price'] ?? INF))
                ->first();

            if ($candidate === null) {
                return $this->refuse('unavailable', $booking, 'flight not found in a fresh search');
            }

            $selected = $supplier->select(
                (string) $candidate['fareSourceCode'],
                FlightMarkup::apply($candidate),
                $criteria,
            );

            if ($selected['error'] ?? true) {
                return $this->refuse('unavailable', $booking, 'fare could not be verified: '.($selected['message'] ?? ''));
            }

            $fresh = FlightMarkup::apply($selected['data']['flight']);
            $approved = (float) ($held['price'] ?? 0);
            $current = (float) ($fresh['price'] ?? 0);
            $tolerance = (float) config('flights.platform_hold.price_tolerance', 50);

            if ($current > $approved + $tolerance) {
                return $this->refuse('price_increased', $booking, "fare rose from {$approved} to {$current}", [
                    'approved_price' => $approved,
                    'current_price' => $current,
                ]);
            }

            return ['ok' => true, 'flight' => $fresh, 'approved_price' => $approved, 'current_price' => $current];
        } catch (Throwable $exception) {
            return $this->refuse('unconfirmed', $booking, $exception->getMessage());
        }
    }

    /**
     * Records the fresh fare on the booking so the next supplier call uses a
     * live token. The customer's price stays the approved one; what the fare
     * costs us now is kept alongside it.
     */
    public function adopt(FlightBooking $booking, array $fresh): void
    {
        $snapshot = (array) ($booking->flight_snapshot ?? []);

        foreach (['fareSourceCode', 'skylinkBookingToken'] as $field) {
            if (array_key_exists($field, $fresh)) {
                $snapshot[$field] = $fresh[$field];
            }
        }

        $booking->update([
            'fare_source_code' => (string) ($fresh['fareSourceCode'] ?? $booking->fare_source_code),
            'flight_snapshot' => $snapshot,
            'supplier_price' => $fresh['supplierPrice'] ?? $booking->supplier_price,
        ]);
    }

    /**
     * The search form, rebuilt from the held flight: same airports, dates,
     * cabin and travellers.
     */
    public function criteriaFor(FlightBooking $booking, array $held): array
    {
        $date = fn (?string $value): string => $value ? Carbon::parse($value)->format('d/m/Y') : '';
        $segments = (array) ($held['segments'] ?? []);
        $return = (array) ($held['returnSegments'] ?? []);
        $legs = (array) ($held['multiLegs'] ?? []);

        $trip = match (true) {
            $legs !== [] => 'multi',
            $return !== [] => 'return',
            default => 'oneway',
        };

        $criteria = [
            'trip' => $trip,
            'adults' => max(1, (int) $booking->adult_count),
            'childs' => (int) $booking->child_count,
            'kids' => (int) $booking->infant_count,
            'flight_type' => match (FlightMatch::cabin($held)) {
                'premium_economy' => 'S',
                'business' => 'C',
                'first' => 'F',
                default => 'Y',
            },
        ];

        if ($trip === 'multi') {
            $criteria['multi_legs'] = array_map(fn (array $leg): array => [
                'from' => (string) ($leg['from'] ?? ($leg['segments'][0]['from'] ?? '')),
                'to' => (string) ($leg['to'] ?? (end($leg['segments'])['to'] ?? '')),
                'depart' => $date($leg['departDT'] ?? ($leg['segments'][0]['departDT'] ?? null)),
            ], $legs);

            return $criteria;
        }

        $criteria['from'] = (string) ($segments[0]['from'] ?? '');
        $criteria['to'] = (string) (end($segments)['to'] ?? '');
        $criteria['depart'] = $date($segments[0]['departDT'] ?? null);

        if ($trip === 'return') {
            $criteria['returning'] = $date($return[0]['departDT'] ?? null);
        }

        return $criteria;
    }

    private function refuse(string $reason, FlightBooking $booking, string $detail, array $extra = []): array
    {
        Log::info('Platform-held fare not reconfirmed', [
            'booking_id' => $booking->id,
            'supplier' => $booking->supplier,
            'reason' => $reason,
            'detail' => $detail,
        ]);

        return [
            'ok' => false,
            'reason' => $reason,
            'message' => match ($reason) {
                'unavailable' => self::UNAVAILABLE,
                'price_increased' => self::PRICE_INCREASED,
                default => self::UNCONFIRMED,
            },
        ] + $extra;
    }
}

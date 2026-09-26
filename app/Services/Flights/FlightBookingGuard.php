<?php

namespace App\Services\Flights;

use App\Models\FlightBooking;
use App\Services\TravelnextFlightService;
use App\Support\FlightMatch;
use Illuminate\Support\Facades\Log;

/**
 * Stops a new booking going to a flight API that has been switched off since
 * the customer's search, and finds the same flight from another API instead.
 *
 * The line is drawn where the booking becomes real at the supplier. Before
 * that — choosing a fare, entering passengers, starting a pay-first payment —
 * a switched-off API is refused. After it — a TravelNext hold exists, so
 * paying for it, ticketing it, anything else — the booking always carries
 * on: the API being off only means no NEW bookings.
 */
class FlightBookingGuard
{
    public const MESSAGE = 'This fare is no longer available.';

    public function __construct(private readonly FlightSupplierControl $control) {}

    /**
     * Null when the booking may go ahead. Otherwise what the results page
     * needs to tell the customer: the message and, when the same flight is
     * on offer from a switched-on API, that offer.
     *
     * @return array{message: string, alternate: array|null}|null
     */
    public function refusal(array $flight, ?FlightBooking $booking = null): ?array
    {
        $key = self::supplierOf($flight, $booking);

        if ($this->existsAtSupplier($booking) || $this->control->isEnabled($key)) {
            return null;
        }

        $alternate = $this->alternateFor($flight, $key);

        Log::info('Booking refused — flight API switched off', [
            'supplier' => $key,
            'fare_source_code' => $flight['fareSourceCode'] ?? null,
            'booking_id' => $booking?->id,
            'alternate_supplier' => $alternate['source'] ?? null,
        ]);

        return [
            'message' => self::MESSAGE,
            'alternate' => $alternate === null ? null : [
                'fareSourceCode' => (string) ($alternate['fareSourceCode'] ?? ''),
                'source' => (string) ($alternate['source'] ?? TravelnextFlightService::KEY),
                'price' => (float) ($alternate['price'] ?? 0),
                'currency' => (string) ($alternate['currency'] ?? 'NGN'),
                'airline' => (string) ($alternate['airline'] ?? ''),
            ],
        ];
    }

    /**
     * The cheapest offer of the same flight from another switched-on API,
     * among everything this search found (first page and supplements).
     */
    public function alternateFor(array $flight, string $excludingKey): ?array
    {
        $key = FlightMatch::key($flight);
        $enabled = $this->control->enabledKeys();

        return collect(session('flightResultsStore', []))
            ->merge(collect(session('supplementResultsStore', []))->flatten(1))
            ->filter(fn ($candidate): bool => is_array($candidate)
                && ($source = self::supplierOf($candidate)) !== $excludingKey
                && in_array($source, $enabled, true)
                && filled($candidate['fareSourceCode'] ?? null)
                && FlightMatch::key($candidate) === $key)
            ->sortBy(fn (array $candidate): float => (float) ($candidate['price'] ?? INF))
            ->first();
    }

    /**
     * A held booking (TravelNext's hold reference) already exists at the
     * supplier. Pay-first bookings have no reference until after payment.
     */
    private function existsAtSupplier(?FlightBooking $booking): bool
    {
        return filled($booking?->unique_id) || filled(session('bookingUniqueId'));
    }

    public static function supplierOf(array $flight, ?FlightBooking $booking = null): string
    {
        return (string) ($booking?->supplier ?: ($flight['source'] ?? TravelnextFlightService::KEY));
    }
}

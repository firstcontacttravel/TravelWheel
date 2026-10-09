<?php

namespace App\Services\Flights;

use App\Models\FlightBooking;
use App\Services\SkylinkFlightService;
use App\Support\FlightMarkup;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps a SkyLink booking's fare token live up to the moment it is reserved.
 *
 * SkyLink's guide: re-price "immediately before /api/flights/reserve" (within
 * five minutes), reserve with the token THAT pricing call returns, and expect
 * offers to expire after 10-15 minutes. We price once when the customer picks
 * the flight, then the passenger form and the card payment follow, so by
 * reserve time the token was often stale and SkyLink answered "Please
 * re-price and try again" after the customer had paid (staging booking 126).
 *
 * refresh() re-prices the token the booking holds — one pricing call, no
 * search, nothing the customer notices. Only when that token has expired is
 * the flight found again with a fresh search (FlightPlatformHold::reconfirm).
 * Either way the customer's price stays what they were shown; a fare that is
 * now dearer beyond the conversion tolerance is refused.
 */
class SkylinkFareRefresh
{
    public const UNAVAILABLE = 'This flight is no longer available at the fare you selected, so no payment was taken. Please choose another flight.';

    public const PRICE_INCREASED = 'The fare for this flight has gone up since you selected it, so no payment was taken. Please choose the flight again.';

    public const UNCONFIRMED = 'We could not confirm this fare with the airline right now, so no payment was taken. Please try again in a few minutes.';

    public function __construct(
        private readonly SkylinkFlightService $skylink,
        private readonly FlightPlatformHold $hold,
    ) {}

    /**
     * ok: the booking now holds a freshly priced token. Otherwise reason is
     * unavailable, price_increased or unconfirmed, with the customer message
     * for before payment and a detail line for support.
     *
     * @return array{ok: bool, researched: bool, reason?: string, message?: string, detail?: string}
     */
    public function refresh(FlightBooking $booking): array
    {
        $held = (array) ($booking->flight_snapshot ?? []);

        try {
            $selected = $this->skylink->select(
                (string) $booking->fare_source_code,
                $held,
                $this->hold->criteriaFor($booking, $held),
            );
        } catch (Throwable $exception) {
            $selected = ['error' => true, 'message' => $exception->getMessage()];
        }

        if (! ($selected['error'] ?? true)) {
            $fresh = FlightMarkup::apply($selected['data']['flight']);
            $shown = (float) ($held['price'] ?? 0);
            $current = (float) ($fresh['price'] ?? 0);

            if ($current > $shown + (float) config('flights.platform_hold.price_tolerance', 50)) {
                return $this->refuse('price_increased', $booking, "fare rose from {$shown} to {$current} on re-pricing", false);
            }

            $this->hold->adopt($booking, $fresh);

            return ['ok' => true, 'researched' => false];
        }

        // The token itself has expired: find the same flight again. This is
        // the slow path (a full search), taken only when re-pricing failed.
        $reconfirmed = $this->hold->reconfirm($booking);

        if (! $reconfirmed['ok']) {
            return $this->refuse(
                $reconfirmed['reason'] ?? 'unconfirmed',
                $booking,
                're-pricing failed ('.($selected['message'] ?? 'no message').'), then the fresh search could not reconfirm the fare',
                true,
            );
        }

        $this->hold->adopt($booking, $reconfirmed['flight']);

        return ['ok' => true, 'researched' => true];
    }

    private function refuse(string $reason, FlightBooking $booking, string $detail, bool $researched): array
    {
        Log::info('SkyLink fare not refreshed', [
            'booking_id' => $booking->id,
            'reason' => $reason,
            'detail' => $detail,
        ]);

        return [
            'ok' => false,
            'researched' => $researched,
            'reason' => $reason,
            'detail' => $detail,
            'message' => match ($reason) {
                'unavailable' => self::UNAVAILABLE,
                'price_increased' => self::PRICE_INCREASED,
                default => self::UNCONFIRMED,
            },
        ];
    }
}

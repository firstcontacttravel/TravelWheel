<?php

namespace App\Http\Controllers;

use App\Services\Flights\FlightSearchStore;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\FlightSupplierRegistry;
use App\Support\FlightMarkup;
use App\Support\FlightMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GET /flights/search/{search}/suppliers/{supplier} — one API's flights for
 * one parallel search.
 *
 * The results page calls this once per switched-on API, all at the same
 * time. That is only safe because this route runs WITHOUT a session (see
 * routes/web.php): Laravel writes the whole session back at the end of every
 * request, so a slow API finishing after the customer had already chosen a
 * fare would put back the session as it was before, choice and all. The
 * search id — an unguessable UUID — is all it needs to find the search.
 *
 * Every answer is 200 with a status, never an error page: an API that fails
 * is simply one with no flights, and the page carries on with the others.
 */
class FlightSupplierSearchController extends Controller
{
    public function __invoke(
        string $search,
        string $supplier,
        FlightSearchStore $store,
        FlightSupplierRegistry $registry,
        FlightSupplierControl $control,
    ): JsonResponse {
        if (! $registry->has($supplier)) {
            return $this->answer('unknown', httpStatus: 404);
        }

        $criteria = $store->criteria($search);

        if ($criteria === null) {
            return $this->answer('expired', httpStatus: 404);
        }

        // Checked on every call, not just when the search began: a reload
        // after an API is switched off must not bring its fares back.
        if (! $control->isEnabled($supplier)) {
            return $this->answer('off');
        }

        // A reload, or the back button from checkout, shows what the
        // customer saw rather than asking the API again.
        if ($cached = $store->result($search, $supplier)) {
            return $this->answer('ok', $cached['flights']);
        }

        set_time_limit(120);

        try {
            $result = $registry->get($supplier)->search($criteria, [
                'search_id' => $search,
                'started_at' => microtime(true),
            ]);

            if ($result['error'] ?? true) {
                return $this->answer('error');
            }

            $flights = FlightMatch::tag(array_values(array_map(
                fn (array $flight): array => FlightMarkup::apply($flight),
                (array) ($result['data']['flights'] ?? []),
            )));

            $store->put($search, $supplier, $flights, (array) ($result['data']['meta'] ?? []));

            return $this->answer('ok', $flights);
        } catch (Throwable $exception) {
            Log::warning('Parallel flight search failed', [
                'supplier' => $supplier,
                'search_id' => $search,
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return $this->answer('error');
        }
    }

    private function answer(string $state, array $flights = [], int $httpStatus = 200): JsonResponse
    {
        return response()->json(['status' => $state, 'flights' => $flights], $httpStatus)
            // Per-customer and short-lived: never let a proxy keep it.
            ->header('Cache-Control', 'no-store, private');
    }
}

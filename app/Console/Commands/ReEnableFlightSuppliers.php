<?php

namespace App\Console\Commands;

use App\Services\Flights\FlightSupplierControl;
use Illuminate\Console\Command;

/**
 * Records every scheduled flight-API switch-on that has come due.
 *
 * Customers see the API as on from the due time whether or not this has run
 * (FlightSupplierControl::enabledKeys() honours the time directly); this
 * makes the saved setting and the admin's history agree.
 */
class ReEnableFlightSuppliers extends Command
{
    protected $signature = 'flights:re-enable-suppliers';

    protected $description = 'Switch flight APIs back on whose scheduled switch-on time has passed';

    public function handle(FlightSupplierControl $control): int
    {
        $count = $control->reEnableDue();

        $this->info($count === 0 ? 'No flight APIs due to switch back on.' : "Switched {$count} flight API(s) back on.");

        return self::SUCCESS;
    }
}

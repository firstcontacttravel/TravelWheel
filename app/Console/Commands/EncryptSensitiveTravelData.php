<?php

namespace App\Console\Commands;

use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptSensitiveTravelData extends Command
{
    protected $signature = 'privacy:encrypt-sensitive-data {--limit=0}';

    protected $description = 'Encrypt existing passenger and TravelFlex sensitive JSON values in place';

    public function handle(): int
    {
        $remaining = max(0, (int) $this->option('limit'));
        $processed = 0;

        DB::table('flight_bookings')
            ->select(['id', 'passengers_snapshot'])
            ->whereNotNull('passengers_snapshot')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$processed, &$remaining): bool {
                foreach ($rows as $row) {
                    if ($remaining > 0 && $processed >= $remaining) {
                        return false;
                    }
                    if (str_contains((string) $row->passengers_snapshot, '__travelwheel_encrypted')) {
                        continue;
                    }

                    $booking = FlightBooking::find($row->id);
                    if ($booking && $booking->passengers_snapshot !== null) {
                        $booking->passengers_snapshot = $booking->passengers_snapshot;
                        $booking->save();
                        $processed++;
                    }
                }

                return true;
            });

        $fields = [
            'applicant_details',
            'bvn_metadata',
            'identity_details',
            'employment_details',
            'bank_details',
            'next_of_kin_details',
            'company_details',
            'representative_details',
            'document_paths',
            'agreement_acceptance',
            'repayment_plan',
        ];

        DB::table('travel_flex_applications')
            ->select(array_merge(['id'], $fields))
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($fields, &$processed, &$remaining): bool {
                foreach ($rows as $row) {
                    if ($remaining > 0 && $processed >= $remaining) {
                        return false;
                    }

                    $application = TravelFlexApplication::find($row->id);
                    if (! $application) {
                        continue;
                    }

                    $dirty = false;
                    foreach ($fields as $field) {
                        $raw = $row->{$field};
                        if ($raw === null || str_contains((string) $raw, '__travelwheel_encrypted')) {
                            continue;
                        }

                        $application->{$field} = $application->{$field};
                        $dirty = true;
                    }

                    if ($dirty) {
                        $application->save();
                        $processed++;
                    }
                }

                return true;
            });

        $this->info("Sensitive records encrypted: {$processed}");

        return self::SUCCESS;
    }
}

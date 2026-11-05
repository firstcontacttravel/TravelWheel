<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\FlightBooking;
use App\Services\Corporate\CompanyInvoices;
use Illuminate\Console\Command;

/**
 * Daily: deletes saved travellers of companies suspended past the retention
 * period, and issues any invoice that failed at the time.
 */
class CorporateHousekeeping extends Command
{
    protected $signature = 'corporate:housekeeping {--dry-run : List what would happen, and change nothing}';

    protected $description = 'Delete expired saved travellers and issue any missing corporate invoices';

    public function handle(CompanyInvoices $invoices): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = (int) config('corporate.traveller_retention_days');

        Company::query()
            ->where('status', 'suspended')
            ->where('suspended_at', '<=', now()->subDays($days))
            ->whereHas('travellers')
            ->each(function (Company $company) use ($dryRun, $days): void {
                $count = $company->travellers()->count();
                $this->line("{$company->name}: {$count} saved traveller(s) past {$days} days");

                if ($dryRun) {
                    return;
                }

                $company->travellers()->delete();

                ActivityLog::record(
                    'corporate.travellers_expired',
                    "Deleted {$count} saved travellers for {$company->name}, suspended over {$days} days",
                    $company,
                    user: null,
                );
            });

        FlightBooking::query()
            ->whereNotNull('company_id')
            ->where('payment_status', 'paid')
            ->where(fn ($query) => $query->whereIn('booking_status', ['confirmed', 'ticketed'])->orWhere('ticket_ordered', true))
            ->whereDoesntHave('companyInvoices', fn ($query) => $query->where('type', 'invoice'))
            ->each(function (FlightBooking $booking) use ($invoices, $dryRun): void {
                $this->line("{$booking->booking_ref}: invoice missing");

                if (! $dryRun) {
                    $invoices->issueForBooking($booking);
                }
            });

        return self::SUCCESS;
    }
}

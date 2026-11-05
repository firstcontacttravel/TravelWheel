<?php

namespace App\Services\Corporate;

use App\Models\Company;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** A month of a company's wallet, read straight from the ledger. */
class CompanyStatements
{
    private const TIMEZONE = 'Africa/Lagos';

    /** The month asked for, or this month for anything unusable or still to come. */
    public function month(?string $value): CarbonImmutable
    {
        $now = CarbonImmutable::now(self::TIMEZONE)->startOfMonth();

        if (! is_string($value) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return $now;
        }

        $month = CarbonImmutable::createFromFormat('!Y-m', $value, self::TIMEZONE);

        return $month->greaterThan($now) ? $now : $month;
    }

    /** @return array{month: CarbonImmutable, opening: float, closing: float, money_in: float, money_out: float, entries: Collection} */
    public function forMonth(Company $company, CarbonImmutable $month): array
    {
        $start = Carbon::instance($month->startOfMonth())->setTimezone(config('app.timezone'));
        $end = Carbon::instance($month->addMonth()->startOfMonth())->setTimezone(config('app.timezone'));

        $opening = (float) ($company->walletEntries()
            ->where('created_at', '<', $start)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        $entries = $company->walletEntries()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->orderBy('id')
            ->get();

        return [
            'month' => $month,
            'opening' => $opening,
            'closing' => $entries->isEmpty() ? $opening : (float) $entries->last()->balance_after,
            'money_in' => (float) $entries->where('amount', '>', 0)->sum('amount'),
            'money_out' => abs((float) $entries->where('amount', '<', 0)->sum('amount')),
            'entries' => $entries,
        ];
    }

    /** @return array<string, string> "2026-11" => "November 2026", newest first, back to the first entry */
    public function availableMonths(Company $company): array
    {
        $first = $company->walletEntries()->orderBy('id')->value('created_at');
        $current = CarbonImmutable::now(self::TIMEZONE)->startOfMonth();
        $oldest = $first ? CarbonImmutable::parse($first)->setTimezone(self::TIMEZONE)->startOfMonth() : $current;
        $months = [];

        for ($month = $current; $month->greaterThanOrEqualTo($oldest); $month = $month->subMonth()) {
            $months[$month->format('Y-m')] = $month->format('F Y');
        }

        return $months;
    }
}

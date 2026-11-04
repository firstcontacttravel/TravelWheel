<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone a company books for often. Shared across the company.
 *
 * The passport is encrypted and never leaves the server: pages show only
 * maskedPassport(), and the full details are added when a booking is submitted.
 * See CompanyTravellers.
 */
class CompanyTraveller extends Model
{
    /** Airlines commonly want this long left on a passport after travel. */
    private const PASSPORT_MONTHS_AFTER_TRAVEL = 6;

    protected $guarded = ['id'];

    protected $hidden = ['passport', 'dob', 'frequent_flyer_number'];

    protected function casts(): array
    {
        return [
            'dob' => 'encrypted',
            'frequent_flyer_number' => 'encrypted',
            'passport' => EncryptedJson::class,
            'passport_expires_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fullName(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->join(' ');
    }

    public function hasPassport(): bool
    {
        return filled($this->passport_last4);
    }

    public function maskedPassport(): ?string
    {
        return $this->hasPassport() ? 'ending '.$this->passport_last4 : null;
    }

    /** A warning for this trip, or null if the passport looks fine or there is none. */
    public function passportWarning(CarbonInterface $travelDate): ?string
    {
        if (! $this->passport_expires_on) {
            return null;
        }

        if ($this->passport_expires_on->lte($travelDate)) {
            return 'This passport will have expired by the travel date. Enter a new one for this trip.';
        }

        if ($this->passport_expires_on->lt($travelDate->copy()->addMonths(self::PASSPORT_MONTHS_AFTER_TRAVEL))) {
            return 'This passport expires within 6 months of travel, which some airlines and countries refuse.';
        }

        return null;
    }
}

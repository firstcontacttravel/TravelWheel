<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaTraveler extends Model
{
    protected $fillable = ['visa_application_id', 'reference', 'traveler_type', 'applicant_type', 'position', 'title', 'first_name', 'middle_name', 'last_name', 'sex', 'date_of_birth', 'place_of_birth', 'nationality_country_id', 'email', 'phone', 'home_address', 'passport_number', 'passport_type', 'passport_issued_at', 'passport_expires_at', 'passport_issuing_country_id'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'passport_issued_at' => 'date', 'passport_expires_at' => 'date', 'passport_number' => 'encrypted'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VisaApplication::class, 'visa_application_id');
    }
}

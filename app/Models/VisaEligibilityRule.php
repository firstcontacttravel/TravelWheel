<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaEligibilityRule extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'rule_type', 'country_id', 'country_group_id', 'conditions', 'public_message', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'is_active' => 'boolean'];
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function countryGroup(): BelongsTo
    {
        return $this->belongsTo(CountryGroup::class);
    }
}

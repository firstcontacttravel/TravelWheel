<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['alpha2', 'alpha3', 'code', 'name', 'region', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(CountryGroup::class);
    }

    public function visaProducts(): HasMany
    {
        return $this->hasMany(VisaProduct::class, 'destination_country_id');
    }
}

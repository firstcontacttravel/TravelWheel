<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaDestination extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_visa_destination');
    }

    public function products(): HasMany
    {
        return $this->hasMany(VisaProduct::class);
    }
}

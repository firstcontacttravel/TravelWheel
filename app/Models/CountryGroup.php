<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CountryGroup extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'version', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One customer's search, for parallel search. The id is an unguessable UUID:
 * it is the only thing that ties the session-free per-API requests to it.
 */
class FlightSearch extends Model
{
    use Prunable;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'criteria', 'created_at', 'expires_at'];

    protected $casts = [
        'criteria' => 'array',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(FlightSearchResult::class);
    }

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }
}

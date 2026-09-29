<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One API's answer to one FlightSearch. Read and written only through
 * App\Services\Flights\FlightSearchStore, which handles the compression.
 */
class FlightSearchResult extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $fillable = ['flight_search_id', 'supplier', 'flights', 'meta', 'flight_count', 'created_at', 'expires_at'];

    protected $casts = [
        'meta' => 'array',
        'flight_count' => 'integer',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Pruned on their own expiry too: the foreign key cascades when a search
     * is pruned, but not every database enforces it.
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }
}

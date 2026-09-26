<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change to a flight API's switch or position: the history the admin
 * screen shows. Written only by FlightSupplierControl.
 */
class FlightSupplierEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'supplier_key',
        'action',
        'reason',
        'user_id',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

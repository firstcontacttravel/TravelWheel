<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelFlexInstalmentPayment extends Model
{
    protected $fillable = [
        'travel_flex_application_id',
        'instalment_index',
        'due_date',
        'label',
        'amount',
        'paid_on',
        'payment_reference',
        'note',
        'recorded_by',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'instalment_index' => 'integer',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_on' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(TravelFlexApplication::class, 'travel_flex_application_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }
}

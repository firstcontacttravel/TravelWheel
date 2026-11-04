<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A company adding money to its wallet, by bank transfer or card. */
class CompanyWalletTopUp extends Model
{
    public const REFERENCE_PREFIX = 'TWTOPUP';

    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'card' => 'Card',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'rejected' => 'Rejected',
        'failed' => 'Failed',
    ];

    public const STATUS_COLORS = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'rejected' => 'danger',
        'failed' => 'gray',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'total_charged' => 'decimal:2',
            'decided_at' => 'datetime',
            'gateway_response' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class, 'requested_by_company_user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function isCard(): bool
    {
        return $this->method === 'card';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

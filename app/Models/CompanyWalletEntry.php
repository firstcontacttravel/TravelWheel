<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of a company's wallet ledger. Append-only: a mistake is put right
 * with a new entry, never by changing an old one. Only CompanyWallet writes here.
 */
class CompanyWalletEntry extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'top_up' => 'Top-up',
        'booking_debit' => 'Flight booking',
        'booking_return' => 'Returned from booking',
        'refund' => 'Refund',
        'payout' => 'Paid out',
        'adjustment' => 'Adjustment',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Wallet entries cannot be deleted.'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function topUp(): BelongsTo
    {
        return $this->belongsTo(CompanyWalletTopUp::class, 'company_wallet_top_up_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

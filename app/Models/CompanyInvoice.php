<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * An invoice or credit note. Issued documents never change: a correction is a
 * new document. Only CompanyInvoices writes here.
 */
class CompanyInvoice extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'invoice' => 'Invoice',
        'credit_note' => 'Credit note',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'lines' => 'array',
            'company_details' => 'array',
            'booking_details' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Issued documents cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Issued documents cannot be deleted.'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function creditedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'credited_invoice_id');
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

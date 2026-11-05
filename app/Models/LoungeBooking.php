<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class LoungeBooking extends Model
{
    use HasWorkItem;

    protected $table = 'lounge_service';

    protected $casts = [
        'travel_date' => 'date',
    ];

    protected $fillable = [
        'lounge_id',
        'lounge_name',
        'provider',
        'provider_url',
        'payment_option',
        'fullname',
        'service',
        'email',
        'phone_no',
        'terminal',
        'nop',
        'noa',
        'noc',
        'noi',
        'travel_date',
        'ticket_no',
        'd_time',
        'amount',
        'amountA',
        'amountC',
        'vat',
        'status',
        'trans_id',
        'ref_id',
        'fulfilment_status',
    ];

    /**
     * Every other booking calls these full_name, phone_number and payment_reference.
     * PHP ignores case in method names, so this also answers for the fullname column.
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->attributes['fullname'] ?? null;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->phone_no;
    }

    public function getPaymentReferenceAttribute(): ?string
    {
        return $this->trans_id;
    }

    /** The status column in the words every other booking's payment_status uses. */
    public function getPaymentStatusAttribute(): string
    {
        return $this->status === 'Successful' ? 'paid' : strtolower((string) $this->status);
    }

    /** VAT is kept apart from amount here, so this is what the customer pays. */
    public function totalAmount(): float
    {
        return (float) $this->amount + (float) $this->vat;
    }

    public function lounge()
    {
        return $this->belongsTo(Lounge::class, 'lounge_id');
    }

    public function requiresManualProviderBooking(): bool
    {
        return $this->provider === 'loungepair' && filled($this->provider_url);
    }
}

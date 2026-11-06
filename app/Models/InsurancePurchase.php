<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class InsurancePurchase extends Model
{
    use HasWorkItem;

    protected $table = 'insurance_purchases';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['booking_form' => 'array'];
    }

    /** Every other booking calls these full_name, phone_number and payment_reference. */
    public function getFullNameAttribute(): string
    {
        return trim($this->surname.' '.$this->firstname);
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
}

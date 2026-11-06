<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class AirCargoModel extends Model
{
    use HasWorkItem;

    protected $table = 'aircargo';
    protected $guarded = [];

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
        return $this->phone;
    }

    public function getPaymentReferenceAttribute(): ?string
    {
        return $this->transaction_ref;
    }
}

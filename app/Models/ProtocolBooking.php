<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class ProtocolBooking extends Model
{
    use HasWorkItem;

    protected $table = 'protocol_bookings';

    protected $casts = [
        'fullname'        => 'array',
        'reservationCode' => 'array',
        'eTicketNo'       => 'array',
        'noOfBags'        => 'array',
        'travel_date'     => 'date',
    ];

    /** Every other booking calls these phone_number and payment_reference. */
    public function getPhoneNumberAttribute(): ?string
    {
        return $this->phone;
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

    protected $fillable = [
        'paymentoption',
        'fullname',
        'package',
        'service',
        'passenger',
        'email',
        'phone',
        'travel_date',
        'state',
        'airline',
        'airport',
        'd_time',
        'service_type',
        'status',
        'amount',
        'vat',
        'optional_request',
        'optionalRequestOption',
        'optionalRequestAddress',
        'reservationCode',
        'eTicketNo',
        'noOfBags',
        'nextOfKin_fullname',
        'nextOfKin_phone',
        'means_id',
        'trans_id',
        'ref_id',
        'fulfilment_status',
    ];
}

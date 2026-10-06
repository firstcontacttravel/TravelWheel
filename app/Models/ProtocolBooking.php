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

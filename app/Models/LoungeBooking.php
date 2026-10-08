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

    public function lounge()
    {
        return $this->belongsTo(Lounge::class, 'lounge_id');
    }

    public function requiresManualProviderBooking(): bool
    {
        return $this->provider === 'loungepair' && filled($this->provider_url);
    }
}

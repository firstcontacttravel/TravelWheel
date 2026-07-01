<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaNotificationEvent extends Model
{
    protected $fillable = ['visa_application_id', 'event_type', 'recipient', 'subject', 'payload', 'status', 'queued_at', 'resent_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'queued_at' => 'datetime', 'resent_at' => 'datetime'];
    }
}

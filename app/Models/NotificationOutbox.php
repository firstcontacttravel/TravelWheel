<?php

namespace App\Models;

use App\Casts\EncryptedJson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationOutbox extends Model
{
    protected $fillable = [
        'kind',
        'recipient',
        'cc',
        'related_type',
        'related_id',
        'payload',
        'unique_key',
        'status',
        'attempts',
        'available_at',
        'last_attempted_at',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'cc' => 'array',
            'payload' => EncryptedJson::class,
            'available_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}

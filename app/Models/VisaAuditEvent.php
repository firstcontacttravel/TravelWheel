<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaAuditEvent extends Model
{
    protected $fillable = ['visa_application_id', 'user_id', 'event_type', 'summary', 'before', 'after', 'metadata'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

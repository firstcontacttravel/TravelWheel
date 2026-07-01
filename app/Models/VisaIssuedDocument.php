<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaIssuedDocument extends Model
{
    protected $fillable = ['visa_application_id', 'version', 'disk', 'path', 'original_name', 'mime_type', 'size', 'issued_by', 'issued_at', 'superseded_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'superseded_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VisaApplication::class, 'visa_application_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}

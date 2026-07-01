<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaApplicationDocument extends Model
{
    protected $fillable = ['visa_application_id', 'visa_traveler_id', 'visa_requirement_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VisaApplication::class, 'visa_application_id');
    }

    public function traveler(): BelongsTo
    {
        return $this->belongsTo(VisaTraveler::class, 'visa_traveler_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(VisaRequirement::class, 'visa_requirement_id');
    }
}

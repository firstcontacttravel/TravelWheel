<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaAdditionalDocumentRequest extends Model
{
    protected $fillable = ['visa_application_id', 'visa_traveler_id', 'visa_requirement_id', 'title', 'instructions', 'status', 'due_at', 'requested_by', 'disk', 'path', 'original_name', 'mime_type', 'size', 'submitted_at', 'resolved_at', 'reviewed_by', 'review_note'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'submitted_at' => 'datetime', 'resolved_at' => 'datetime'];
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

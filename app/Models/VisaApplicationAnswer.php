<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaApplicationAnswer extends Model
{
    protected $fillable = ['visa_application_id', 'visa_traveler_id', 'visa_question_id', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(VisaQuestion::class, 'visa_question_id');
    }
}

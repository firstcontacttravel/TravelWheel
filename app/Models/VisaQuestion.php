<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaQuestion extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'key', 'section', 'label', 'help_text', 'input_type', 'scope', 'is_required', 'options', 'validation_rules', 'conditions', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'options' => 'array', 'validation_rules' => 'array', 'conditions' => 'array', 'is_active' => 'boolean'];
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }
}

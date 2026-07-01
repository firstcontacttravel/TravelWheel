<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaRequirement extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'optional_service_code', 'name', 'category', 'scope', 'requirement_state', 'description', 'conditions', 'accepted_mime_types', 'maximum_file_size_kb', 'minimum_validity_days', 'guidance', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'accepted_mime_types' => 'array', 'is_active' => 'boolean'];
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaApplicationServiceSelection extends Model
{
    protected $fillable = ['visa_application_id', 'visa_optional_service_id', 'selected', 'configuration'];

    protected function casts(): array
    {
        return ['selected' => 'boolean', 'configuration' => 'array'];
    }
}

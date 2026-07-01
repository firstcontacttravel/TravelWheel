<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaApplicationStatusHistory extends Model
{
    protected $table = 'visa_application_status_history';

    protected $fillable = ['visa_application_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'reason', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}

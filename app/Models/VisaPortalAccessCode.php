<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaPortalAccessCode extends Model
{
    protected $fillable = ['visa_application_id', 'email', 'code_hash', 'attempts', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }
}

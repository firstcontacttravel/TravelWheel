<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingAlert extends Model
{
    protected $fillable = [
        'fingerprint', 'type', 'severity', 'product', 'metric', 'observed_value',
        'expected_value', 'message', 'detected_at', 'resolved_at',
        'acknowledged_by', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'observed_value' => 'decimal:2',
            'expected_value' => 'decimal:2',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }
}

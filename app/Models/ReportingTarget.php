<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingTarget extends Model
{
    protected $fillable = [
        'created_by', 'label', 'product', 'metric', 'period_start', 'period_end', 'target_value',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'target_value' => 'decimal:2',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingSyncRun extends Model
{
    protected $fillable = [
        'status', 'row_count', 'product_counts', 'errors', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'product_counts' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}

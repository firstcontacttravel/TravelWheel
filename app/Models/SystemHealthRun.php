<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemHealthRun extends Model
{
    protected $fillable = [
        'user_id',
        'overall_status',
        'healthy_count',
        'warning_count',
        'failed_count',
        'duration_ms',
        'results',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'results' => 'array',
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

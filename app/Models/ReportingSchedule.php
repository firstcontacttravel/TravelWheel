<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingSchedule extends Model
{
    protected $fillable = [
        'user_id', 'name', 'report_key', 'format', 'frequency', 'recipients',
        'filters', 'is_active', 'last_sent_at', 'next_send_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'filters' => 'array',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
            'next_send_at' => 'datetime',
        ];
    }
}

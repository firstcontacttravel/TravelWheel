<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingExportAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'report_key', 'format', 'filters', 'row_count',
        'ip_address', 'user_agent', 'exported_at',
    ];

    protected function casts(): array
    {
        return ['filters' => 'array', 'exported_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

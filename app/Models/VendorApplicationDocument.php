<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file a vendor uploaded with their application. Stored on the private
 * `local` disk; staff download it through the admin-only route.
 */
class VendorApplicationDocument extends Model
{
    public const STATUSES = [
        'pending' => 'Not checked',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'vendor_application_id', 'service', 'type', 'label', 'disk', 'path',
        'original_name', 'mime_type', 'size', 'expires_on', 'status', 'review_note',
    ];

    protected function casts(): array
    {
        return ['expires_on' => 'date'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VendorApplication::class, 'vendor_application_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }
}

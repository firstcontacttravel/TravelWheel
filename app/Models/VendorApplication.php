<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A supplier's answers to the digital vendor / partner registration form,
 * and the team's review of it. The questions themselves live in
 * config/vendor_onboarding.php.
 */
class VendorApplication extends Model
{
    public const STATUSES = [
        'received' => 'Application received',
        'pending' => 'Awaiting more information',
        'documents_verified' => 'Documents verified',
        'compliance_completed' => 'Compliance completed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const STATUS_COLORS = [
        'received' => 'info',
        'pending' => 'warning',
        'documents_verified' => 'primary',
        'compliance_completed' => 'primary',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    public const RISK_RATINGS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'business_types' => 'array',
            'services' => 'array',
            'service_details' => 'array',
            'booking_channels' => 'array',
            'approved_services' => 'array',
            'works_with_other_platforms' => 'boolean',
            // Bank details are only readable through the app
            'bank_name' => 'encrypted',
            'account_name' => 'encrypted',
            'account_number' => 'encrypted',
            'declared_at' => 'datetime',
            'documents_verified_at' => 'datetime',
            'compliance_completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'next_review_on' => 'date',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorApplicationDocument::class);
    }

    public function documentsVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_verified_by');
    }

    public function complianceCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compliance_completed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public static function newReference(): string
    {
        do {
            $reference = 'VND-'.strtoupper(bin2hex(random_bytes(4)));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /** The Vendor ID an approved vendor is known by, e.g. TWV-0007. */
    public function assignVendorCode(): string
    {
        return $this->vendor_code ??= 'TWV-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isDecided(): bool
    {
        return in_array($this->status, ['approved', 'rejected'], true);
    }

    /** @return array<string, string> service key => label, for the services this vendor chose */
    public function serviceLabels(?array $keys = null): array
    {
        $all = config('vendor_onboarding.services');

        return collect($keys ?? $this->services ?? [])
            ->mapWithKeys(fn (string $key) => [$key => $all[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key))])
            ->all();
    }

    public function businessTypeLabels(): array
    {
        $types = config('vendor_onboarding.business_types');
        $labels = array_map(fn (string $key) => $types[$key] ?? $key, $this->business_types ?? []);

        if (filled($this->business_type_other)) {
            $labels[] = 'Other: '.$this->business_type_other;
        }

        return $labels;
    }
}

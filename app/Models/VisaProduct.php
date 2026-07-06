<?php

namespace App\Models;

use App\Enums\VisaEligibilityMode;
use App\Enums\VisaProductFamily;
use App\Enums\VisaPublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisaProduct extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'family' => 'standard',
        'entry_type' => 'single',
        'publication_status' => 'draft',
        'eligibility_mode' => 'all',
        'version' => 1,
    ];

    protected $fillable = [
        'destination_country_id', 'visa_destination_id', 'visa_vendor_id', 'name', 'slug', 'family', 'category', 'entry_type',
        'publication_status', 'eligibility_mode', 'validity_days', 'maximum_stay_days',
        'summary', 'description', 'processing_disclaimer', 'issuance_disclaimer',
        'important_notes', 'form_configuration', 'effective_from', 'effective_until', 'published_at',
        'version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'family' => VisaProductFamily::class,
            'publication_status' => VisaPublicationStatus::class,
            'eligibility_mode' => VisaEligibilityMode::class,
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'published_at' => 'datetime',
            'form_configuration' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VisaProduct $product): void {
            $product->created_by ??= auth()->id();
            $product->updated_by ??= auth()->id();
        });

        static::updating(function (VisaProduct $product): void {
            $product->updated_by = auth()->id();
            if ($product->isDirty() && ! $product->isDirty('version')) {
                $product->version = max(1, (int) $product->getOriginal('version') + 1);
            }
        });
    }

    public function scopeCurrentlyPublished(Builder $query): Builder
    {
        return $query
            ->where('publication_status', VisaPublicationStatus::Published->value)
            ->where(fn (Builder $query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(fn (Builder $query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()));
    }

    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination_country_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VisaVendor::class, 'visa_vendor_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(VisaDestination::class, 'visa_destination_id');
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(VisaEligibilityRule::class)->orderBy('sort_order');
    }

    public function processingOptions(): HasMany
    {
        return $this->hasMany(VisaProcessingOption::class)->orderBy('sort_order');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(VisaFeeComponent::class)->orderBy('sort_order');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VisaRequirement::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(VisaQuestion::class)->orderBy('sort_order');
    }

    public function optionalServices(): HasMany
    {
        return $this->hasMany(VisaOptionalService::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaApplication extends Model
{
    protected $fillable = ['reference', 'resume_token_hash', 'visa_product_id', 'visa_processing_option_id', 'product_version', 'status', 'assigned_to', 'assigned_at', 'decision_date', 'decision_reference', 'issued_at', 'visa_valid_from', 'visa_valid_until', 'no_document_reason', 'current_step', 'completed_step', 'nationality_country_id', 'residence_country_id', 'destination_country_id', 'visa_destination_id', 'arrival_date', 'departure_date', 'adult_count', 'child_count', 'infant_count', 'contact_email', 'declaration_accepted', 'declaration_accepted_at', 'search_snapshot', 'product_snapshot', 'form_configuration', 'last_activity_at', 'expires_at'];

    protected function casts(): array
    {
        return ['arrival_date' => 'date', 'departure_date' => 'date', 'assigned_at' => 'datetime', 'decision_date' => 'date', 'issued_at' => 'datetime', 'visa_valid_from' => 'date', 'visa_valid_until' => 'date', 'declaration_accepted' => 'boolean', 'declaration_accepted_at' => 'datetime', 'search_snapshot' => 'array', 'product_snapshot' => 'array', 'form_configuration' => 'array', 'last_activity_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class, 'visa_product_id');
    }

    public function processingOption(): BelongsTo
    {
        return $this->belongsTo(VisaProcessingOption::class, 'visa_processing_option_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(VisaDestination::class, 'visa_destination_id');
    }

    public function travelers(): HasMany
    {
        return $this->hasMany(VisaTraveler::class)->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(VisaApplicationAnswer::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VisaApplicationDocument::class);
    }

    public function serviceSelections(): HasMany
    {
        return $this->hasMany(VisaApplicationServiceSelection::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(VisaApplicationStatusHistory::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(VisaQuote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VisaPayment::class);
    }

    public function additionalDocumentRequests(): HasMany
    {
        return $this->hasMany(VisaAdditionalDocumentRequest::class);
    }

    public function notificationEvents(): HasMany
    {
        return $this->hasMany(VisaNotificationEvent::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(VisaInternalNote::class)->latest();
    }

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(VisaIssuedDocument::class)->orderByDesc('version');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(VisaAuditEvent::class)->latest();
    }
}

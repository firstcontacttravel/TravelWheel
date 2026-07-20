<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelFlexApplication extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'booking_ref',
        'unique_id',
        'applicant_type',
        'applicant_details',
        'bvn_metadata',
        'identity_details',
        'employment_details',
        'bank_details',
        'next_of_kin_details',
        'company_details',
        'representative_details',
        'document_paths',
        'agreement_acceptance',
        'repayment_plan',
        'down_payment',
        'down_percent',
        'grand_total',
        'total_interest',
        'payment_method',
        'payment_status',
        'application_status',
        'financing_status',
        'deposit_status',
        'deposit_reference',
        'deposit_paid_at',
        'provider_status',
        'provider_email_sent_at',
        'provider_email_error',
        'reviewed_at',
        'approved_at',
        'approval_expires_at',
        'pricing_revalidated_at',
        'rejected_at',
        'reviewed_by',
        'admin_note',
    ];

    protected $casts = [
        'applicant_details' => 'array',
        'bvn_metadata' => 'array',
        'identity_details' => 'array',
        'employment_details' => 'array',
        'bank_details' => 'array',
        'next_of_kin_details' => 'array',
        'company_details' => 'array',
        'representative_details' => 'array',
        'document_paths' => 'array',
        'agreement_acceptance' => 'array',
        'repayment_plan' => 'array',
        'down_payment' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'provider_email_sent_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'approval_expires_at' => 'datetime',
        'pricing_revalidated_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

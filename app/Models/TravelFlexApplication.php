<?php

namespace App\Models;

use App\Casts\EncryptedJson;
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
        'generated_application_path',
        'generated_application_sha256',
        'generated_application_version',
        'generated_application_at',
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
        'applicant_details' => EncryptedJson::class,
        'bvn_metadata' => EncryptedJson::class,
        'identity_details' => EncryptedJson::class,
        'employment_details' => EncryptedJson::class,
        'bank_details' => EncryptedJson::class,
        'next_of_kin_details' => EncryptedJson::class,
        'company_details' => EncryptedJson::class,
        'representative_details' => EncryptedJson::class,
        'document_paths' => EncryptedJson::class,
        'generated_application_at' => 'datetime',
        'agreement_acceptance' => EncryptedJson::class,
        'repayment_plan' => EncryptedJson::class,
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

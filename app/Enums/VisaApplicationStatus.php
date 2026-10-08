<?php

namespace App\Enums;

/**
 * The visa application statuses, with the one name staff see and the one
 * name the applicant sees. VisaApplication::status stays a plain string;
 * use labelFor()/customerLabelFor() wherever a status is shown.
 */
enum VisaApplicationStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ActionRequired = 'action_required';
    case Processing = 'processing';
    case Approved = 'approved';
    case Issued = 'issued';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::AwaitingPayment => 'Awaiting payment',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::ActionRequired => 'Action required',
            self::Processing => 'Processing',
            self::Approved => 'Approved',
            self::Issued => 'Issued',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    /** Softer wording for the applicant: "Not approved" rather than "Rejected". */
    public function customerLabel(): string
    {
        return match ($this) {
            self::UnderReview => 'In review',
            self::Issued => 'Visa issued',
            self::Rejected => 'Not approved',
            default => $this->label(),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Issued, self::Approved => 'success',
            self::Rejected, self::Cancelled, self::Expired => 'danger',
            self::ActionRequired, self::AwaitingPayment => 'warning',
            self::UnderReview, self::Processing => 'info',
            default => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }

    public static function labelFor(?string $status): string
    {
        return self::tryFrom((string) $status)?->label() ?? self::fallback($status);
    }

    public static function customerLabelFor(?string $status): string
    {
        return self::tryFrom((string) $status)?->customerLabel() ?? self::fallback($status);
    }

    public static function colorFor(?string $status): string
    {
        return self::tryFrom((string) $status)?->color() ?? 'gray';
    }

    private static function fallback(?string $status): string
    {
        return filled($status) ? str($status)->replace('_', ' ')->headline()->toString() : '-';
    }
}

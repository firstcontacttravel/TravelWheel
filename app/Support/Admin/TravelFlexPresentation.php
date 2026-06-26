<?php

namespace App\Support\Admin;

use App\Models\TravelFlexApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class TravelFlexPresentation
{
    public static function workspaceSummary(TravelFlexApplication $application): HtmlString
    {
        $applicant = data_get($application->applicant_details, 'full_name') ?: 'Applicant';
        $email = data_get($application->applicant_details, 'email') ?: '-';
        $status = self::label($application->application_status);
        $provider = self::label($application->provider_status);
        $payment = self::label($application->payment_status);

        $html = '<div class="tw-booking-hero">';
        $html .= '<div class="tw-booking-hero-main">';
        $html .= '<div class="tw-booking-eyebrow">TravelFlex application</div>';
        $html .= '<div class="tw-booking-title">' . e($application->booking_ref ?: 'TravelFlex') . '</div>';
        $html .= '<div class="tw-booking-route">' . e($applicant) . '</div>';
        $html .= '<div class="tw-booking-meta">';
        $html .= '<span>' . e($email) . '</span>';
        $html .= '<span>' . e(self::label($application->payment_method)) . '</span>';
        $html .= '<span>' . e(($application->down_percent ?: '-') . '% down') . '</span>';
        $html .= '</div></div>';
        $html .= '<div class="tw-booking-hero-side">';
        $html .= '<div class="tw-booking-price">' . e(self::money($application->grand_total)) . '</div>';
        $html .= '<div class="tw-booking-pill-row">';
        $html .= self::statusPill('Application', $application->application_status);
        $html .= self::statusPill('Provider', $application->provider_status);
        $html .= self::statusPill('Payment', $application->payment_status);
        $html .= '</div></div>';
        $html .= '<div class="tw-booking-timeline">';
        $html .= self::timelineItem('Submitted', self::watDateTime($application->created_at), true);
        $html .= self::timelineItem('Reviewed', filled($application->reviewed_at) ? self::watDateTime($application->reviewed_at) : $status, filled($application->reviewed_at));
        $html .= self::timelineItem('Decision', filled($application->approved_at ?: $application->rejected_at) ? self::watDateTime($application->approved_at ?: $application->rejected_at) : $status, in_array($application->application_status, ['approved', 'rejected'], true));
        $html .= self::timelineItem('Provider', filled($application->provider_email_sent_at) ? self::watDateTime($application->provider_email_sent_at) : $provider, $application->provider_status === 'sent');
        $html .= self::timelineItem('Payment', $payment, $application->payment_status === 'paid');
        $html .= '</div></div>';

        return new HtmlString($html);
    }

    public static function applicant(TravelFlexApplication $application): HtmlString
    {
        return self::card([
            'Full name' => data_get($application->applicant_details, 'full_name'),
            'Email' => data_get($application->applicant_details, 'email'),
            'Home address' => data_get($application->applicant_details, 'home_address'),
            'BVN last four' => data_get($application->bvn_metadata, 'last_four'),
            'BVN captured' => data_get($application->bvn_metadata, 'captured_at'),
        ]);
    }

    public static function employment(TravelFlexApplication $application): HtmlString
    {
        return self::card([
            'Employer' => data_get($application->employment_details, 'employer_name'),
            'Employer address' => data_get($application->employment_details, 'employer_address'),
            'Occupation' => data_get($application->employment_details, 'occupation'),
            'Staff number' => data_get($application->employment_details, 'staff_number'),
            'Job description' => data_get($application->employment_details, 'job_description'),
        ]);
    }

    public static function plan(TravelFlexApplication $application): HtmlString
    {
        $plan = $application->repayment_plan ?? [];
        $html = '<div class="space-y-4">';
        $html .= self::definitionGrid([
            'Down payment' => self::money($application->down_payment),
            'Down percent' => $application->down_percent ? $application->down_percent . '%' : null,
            'Grand total' => self::money($application->grand_total),
            'Total interest' => self::money($application->total_interest),
            'Repayment plan' => data_get($plan, 'repayment_plan'),
            'Payment method' => $application->payment_method,
        ]);

        $schedule = data_get($plan, 'schedule', []);
        if (is_array($schedule) && $schedule !== []) {
            $html .= '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">';
            $html .= '<table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">';
            $html .= '<thead><tr>';
            foreach (['Due date', 'Amount', 'Label'] as $header) {
                $html .= '<th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e($header) . '</th>';
            }
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-white/10">';
            foreach ($schedule as $row) {
                $html .= '<tr>';
                $html .= '<td class="px-3 py-2">' . e(data_get($row, 'date', data_get($row, 'due_date', '-'))) . '</td>';
                $html .= '<td class="px-3 py-2">' . e(self::money(data_get($row, 'amount'))) . '</td>';
                $html .= '<td class="px-3 py-2">' . e(data_get($row, 'label', data_get($row, 'title', '-'))) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        return new HtmlString($html . '</div>');
    }

    public static function documents(TravelFlexApplication $application): HtmlString
    {
        $documents = $application->document_paths ?? [];

        if (! is_array($documents) || $documents === []) {
            return self::empty('No documents stored.');
        }

        $required = ['valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements'];
        $stored = collect($documents)->filter(fn ($path): bool => filled($path));
        $available = $stored->filter(fn ($path): bool => Storage::disk('local')->exists((string) $path));

        $html = '<div class="space-y-4">';
        $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">';
        $html .= '<div>';
        $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">Document package</div>';
        $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e($available->count()) . ' of ' . e(count($required)) . ' required files available on local storage.</div>';
        $html .= '</div>';
        $html .= '<div class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">' . e($available->count() === count($required) ? 'Complete' : 'Needs attention') . '</div>';
        $html .= '</div></div>';
        $html .= '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">';

        foreach ($required as $key) {
            $path = $documents[$key] ?? null;
            $exists = $path && Storage::disk('local')->exists($path);
            $html .= '<div class="flex min-h-40 flex-col rounded-lg border ' . ($exists ? 'border-gray-200' : 'border-danger-200') . ' bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="flex items-start justify-between gap-3">';
            $html .= '<div>';
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">' . e(self::documentLabel($key)) . '</div>';
            $html .= '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e(self::documentDescription($key)) . '</div>';
            $html .= '</div>';
            $html .= '<span class="rounded-full px-2 py-1 text-[11px] font-semibold ' . ($exists ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700') . '">' . e($exists ? 'Ready' : 'Missing') . '</span>';
            $html .= '</div>';
            $html .= '<div class="mt-4 rounded-lg bg-gray-50 p-3 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">';
            $html .= $exists ? 'Uploaded document is available for secure review.' : 'Applicant has not provided this document.';
            $html .= '</div>';
            $html .= '<div class="mt-auto pt-4 text-xs text-gray-500 dark:text-gray-400">' . e($exists ? self::fileSize($path) . ' uploaded' : 'Upload not found') . '</div>';
            if ($exists) {
                $html .= '<a class="mt-3 inline-flex w-fit rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500" href="' . e(route('admin.travelflex.documents.download', [$application, $key])) . '" target="_blank">Download document</a>';
            }
            $html .= '</div>';
        }

        return new HtmlString($html . '</div></div>');
    }

    public static function providerHandoff(TravelFlexApplication $application): HtmlString
    {
        $paymentReady = $application->payment_status === 'paid';
        $providerSent = $application->provider_status === 'sent';
        $providerFailed = $application->provider_status === 'failed';
        $documents = is_array($application->document_paths) ? $application->document_paths : [];
        $required = ['valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements'];
        $documentsReady = collect($required)->every(fn (string $key): bool => filled($documents[$key] ?? null));

        $nextAction = match (true) {
            $providerFailed => 'Provider email failed. Review the error, confirm documents, then resend the provider package.',
            $providerSent => 'Provider package has been sent. Monitor provider decision and ticketing status.',
            ! $paymentReady => 'Waiting for down payment verification before provider handoff.',
            ! $documentsReady => 'Documents are incomplete. Resolve missing files before provider handoff.',
            default => 'Ready to send provider package with applicant details, repayment plan, and documents.',
        };

        $html = '<div class="space-y-4">';
        $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
        $html .= '<div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">';
        $html .= '<div>';
        $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">Provider handoff</div>';
        $html .= '<div class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">' . e($nextAction) . '</div>';
        $html .= '</div>';
        $html .= '<div class="flex flex-wrap gap-2">';
        $html .= self::smallState('Payment', $paymentReady ? 'Verified' : self::label($application->payment_status), $paymentReady);
        $html .= self::smallState('Documents', $documentsReady ? 'Complete' : 'Needs attention', $documentsReady);
        $html .= self::smallState('Provider', self::label($application->provider_status), $providerSent, $providerFailed);
        $html .= '</div></div>';
        $html .= '<div class="mt-4 grid gap-3 md:grid-cols-3">';
        $html .= self::handoffFact('Provider status', self::label($application->provider_status), filled($application->provider_email_sent_at) ? 'Sent ' . self::watDateTime($application->provider_email_sent_at) : 'Not sent yet');
        $html .= self::handoffFact('Payment status', self::label($application->payment_status), 'Down payment ' . self::money($application->down_payment));
        $html .= self::handoffFact('Application status', self::label($application->application_status), filled($application->reviewed_at) ? 'Reviewed ' . self::watDateTime($application->reviewed_at) : 'Awaiting review');
        $html .= '</div>';

        if (filled($application->provider_email_error)) {
            $html .= '<div class="mt-4 rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">';
            $html .= '<div class="font-semibold">Provider email error</div>';
            $html .= '<div class="mt-1 break-words">' . e($application->provider_email_error) . '</div>';
            $html .= '</div>';
        }

        return new HtmlString($html . '</div></div>');
    }

    private static function card(array $items): HtmlString
    {
        return new HtmlString('<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">' . self::definitionGrid($items) . '</div>');
    }

    private static function definitionGrid(array $items): string
    {
        $html = '<dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">';

        foreach ($items as $label => $value) {
            $html .= '<div>';
            $html .= '<dt class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e((string) $label) . '</dt>';
            $html .= '<dd class="mt-1 break-words text-sm text-gray-950 dark:text-white">' . e(blank($value) ? '-' : (string) $value) . '</dd>';
            $html .= '</div>';
        }

        return $html . '</dl>';
    }

    private static function money(mixed $amount): string
    {
        return $amount === null || $amount === '' ? '-' : 'NGN ' . number_format((float) $amount, 2);
    }

    private static function statusPill(string $label, ?string $status): string
    {
        $tone = match ($status) {
            'approved', 'reviewed', 'sent', 'paid' => 'good',
            'submitted', 'pending', 'awaiting_bank_transfer', 'not_sent' => 'warn',
            'rejected', 'failed' => 'bad',
            default => 'neutral',
        };

        return '<span class="tw-status-pill tw-status-' . e($tone) . '"><span>' . e($label) . '</span><strong>' . e(self::label($status)) . '</strong></span>';
    }

    private static function timelineItem(string $label, ?string $value, bool $isComplete): string
    {
        return '<div class="tw-timeline-item ' . ($isComplete ? 'is-complete' : '') . '">' .
            '<span class="tw-timeline-dot"></span>' .
            '<div><div class="tw-timeline-label">' . e($label) . '</div>' .
            '<div class="tw-timeline-value">' . e($value ?: '-') . '</div></div>' .
            '</div>';
    }

    private static function handoffFact(string $label, string $value, string $description): string
    {
        return '<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">' .
            '<div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">' . e($label) . '</div>' .
            '<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">' . e($value) . '</div>' .
            '<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e($description) . '</div>' .
            '</div>';
    }

    private static function smallState(string $label, string $value, bool $good, bool $bad = false): string
    {
        $classes = $bad
            ? 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300'
            : ($good ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300');

        return '<span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold ' . $classes . '">' .
            e($label) . ': ' . e($value) .
            '</span>';
    }

    private static function watDateTime(mixed $value, string $format = 'd M Y, H:i'): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            $formatted = \Carbon\Carbon::parse($value)->timezone('Africa/Lagos')->format($format);

            return $formatted;
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str((string) $value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function documentLabel(string $key): string
    {
        return [
            'valid_id' => 'Valid government ID',
            'passport_photo' => 'Passport photograph',
            'work_id_card' => 'Work ID card',
            'employment_letter' => 'Employment letter',
            'bank_statements' => '6-month bank statement',
        ][$key] ?? str($key)->headline()->toString();
    }

    private static function documentDescription(string $key): string
    {
        return [
            'valid_id' => 'Identity verification document',
            'passport_photo' => 'Applicant profile photograph',
            'work_id_card' => 'Employment identity proof',
            'employment_letter' => 'Employment confirmation document',
            'bank_statements' => 'Financial review document',
        ][$key] ?? 'Uploaded applicant document';
    }

    private static function fileSize(string $path): string
    {
        try {
            $bytes = Storage::disk('local')->size($path);
        } catch (\Throwable) {
            return 'Size unavailable';
        }

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        return number_format(max(1, $bytes / 1024), 0) . ' KB';
    }

    private static function empty(string $message): HtmlString
    {
        return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">' . e($message) . '</div>');
    }
}

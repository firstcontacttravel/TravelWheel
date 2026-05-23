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
        $html .= self::timelineItem('Submitted', optional($application->created_at)->format('d M Y, H:i'), true);
        $html .= self::timelineItem('Reviewed', optional($application->reviewed_at)->format('d M Y, H:i') ?: $status, filled($application->reviewed_at));
        $html .= self::timelineItem('Decision', optional($application->approved_at ?: $application->rejected_at)->format('d M Y, H:i') ?: $status, in_array($application->application_status, ['approved', 'rejected'], true));
        $html .= self::timelineItem('Provider', optional($application->provider_email_sent_at)->format('d M Y, H:i') ?: $provider, $application->provider_status === 'sent');
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

        $html = '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">';

        foreach ($documents as $key => $path) {
            $exists = $path && Storage::disk('local')->exists($path);
            $html .= '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">';
            $html .= '<div class="text-sm font-semibold text-gray-950 dark:text-white">' . e(str((string) $key)->headline()) . '</div>';
            $html .= '<div class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">' . e($path ?: '-') . '</div>';
            $html .= '<div class="mt-3 text-xs ' . ($exists ? 'text-success-600' : 'text-danger-600') . '">' . e($exists ? 'File available on local disk' : 'File missing') . '</div>';
            if ($exists) {
                $html .= '<a class="mt-3 inline-flex rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500" href="' . e(route('admin.travelflex.documents.download', [$application, $key])) . '" target="_blank">Download</a>';
            }
            $html .= '</div>';
        }

        return new HtmlString($html . '</div>');
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

    private static function label(?string $value): string
    {
        return filled($value) ? str((string) $value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function empty(string $message): HtmlString
    {
        return new HtmlString('<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">' . e($message) . '</div>');
    }
}

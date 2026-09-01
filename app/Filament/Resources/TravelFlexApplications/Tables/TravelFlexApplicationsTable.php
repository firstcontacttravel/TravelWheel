<?php

namespace App\Filament\Resources\TravelFlexApplications\Tables;

use App\Models\TravelFlexApplication;
use App\Services\TravelFlexApplicationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Throwable;

class TravelFlexApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('TravelFlex Applications')
            ->description('Review queue for installment applications, provider handoff, and payment state.')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->striped()
            ->columns([
                TextColumn::make('queue')
                    ->label('Queue')
                    ->state(fn (TravelFlexApplication $record): string => self::queueLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Submitted' => 'warning',
                        'Provider failed' => 'danger',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('booking_ref')
                    ->label('Booking')
                    ->copyable()
                    ->searchable()
                    ->placeholder('-')
                    ->weight('bold')
                    ->description(fn (TravelFlexApplication $record): string => $record->unique_id ?: 'No UniqueID'),
                TextColumn::make('applicant_details.full_name')
                    ->label('Applicant')
                    ->searchable()
                    ->placeholder('-')
                    ->description(fn (TravelFlexApplication $record): string => data_get($record->applicant_details, 'email') ?: '-'),
                TextColumn::make('documents_status')
                    ->label('Documents')
                    ->state(fn (TravelFlexApplication $record): string => self::documentsStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => $state === '5/5 uploaded' ? 'success' : 'warning'),
                TextColumn::make('down_payment')
                    ->label('Down payment')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn (TravelFlexApplication $record): string => 'Grand total: NGN ' . number_format((float) $record->grand_total, 2)),
                TextColumn::make('application_status')
                    ->label('Application')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->sortable(),
                TextColumn::make('financing_status')
                    ->label('Fast Credit')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->sortable(),
                TextColumn::make('provider_status')
                    ->label('Provider')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'awaiting_bank_transfer', 'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->sortable(),
                TextColumn::make('deposit_status')
                    ->label('Deposit')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::label($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->description(fn (TravelFlexApplication $record): string => self::watDateTime($record->created_at))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('review_queue')
                    ->label('Review queue')
                    ->options([
                        'submitted' => 'Submitted',
                        'provider_failed' => 'Provider failed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'submitted' => $query->where('application_status', 'submitted'),
                            'provider_failed' => $query->where('provider_status', 'failed'),
                            'approved' => $query->where('application_status', 'approved'),
                            'rejected' => $query->where('application_status', 'rejected'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('application_status')
                    ->options([
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('provider_status')
                    ->options([
                        'not_sent' => 'Not sent',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'awaiting_bank_transfer' => 'Awaiting bank transfer',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Open'),
                ActionGroup::make([
                    self::markReviewedAction(),
                    self::approveAction(),
                    self::rejectAction(),
                    self::resendProviderEmailAction(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray'),
            ]);
    }

    private static function queueLabel(TravelFlexApplication $record): string
    {
        if ($record->provider_status === 'failed') {
            return 'Provider failed';
        }

        return match ($record->application_status) {
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => self::label($record->application_status),
        };
    }

    private static function documentsStatus(TravelFlexApplication $record): string
    {
        $required = ['valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements'];
        $documents = is_array($record->document_paths) ? $record->document_paths : [];
        $uploaded = collect($required)
            ->filter(fn (string $key): bool => filled($documents[$key] ?? null))
            ->count();

        return $uploaded . '/' . count($required) . ' uploaded';
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str((string) $value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function actionContext(TravelFlexApplication $record, string $title): HtmlString
    {
        return new HtmlString(
            '<div class="tw-action-context">' .
                '<div>' .
                    '<div class="tw-action-context-kicker">' . e($title) . '</div>' .
                    '<div class="tw-action-context-title">' . e($record->booking_ref ?: 'TravelFlex') . '</div>' .
                    '<div class="tw-action-context-sub">' . e(data_get($record->applicant_details, 'full_name') ?: 'Applicant') . '</div>' .
                '</div>' .
                '<dl>' .
                    '<div><dt>Email</dt><dd>' . e(data_get($record->applicant_details, 'email') ?: '-') . '</dd></div>' .
                    '<div><dt>Down payment</dt><dd>NGN ' . e(number_format((float) $record->down_payment, 2)) . '</dd></div>' .
                    '<div><dt>Application</dt><dd>' . e(self::label($record->application_status)) . '</dd></div>' .
                    '<div><dt>Provider</dt><dd>' . e(self::label($record->provider_status)) . '</dd></div>' .
                '</dl>' .
            '</div>',
        );
    }

    public static function markReviewedAction(): Action
    {
        return Action::make('markReviewed')
            ->label('Mark reviewed')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('info')
            ->visible(fn (TravelFlexApplication $record): bool => $record->application_status === 'submitted')
            ->modalHeading(fn (TravelFlexApplication $record): string => 'Mark TravelFlex reviewed for ' . ($record->booking_ref ?: 'application'))
            ->modalDescription('Use this after checking the applicant details and uploaded documents. This does not approve or reject the application.')
            ->modalIcon('heroicon-o-clipboard-document-check')
            ->modalIconColor('info')
            ->modalSubmitActionLabel('Mark reviewed')
            ->form(fn (TravelFlexApplication $record): array => [
                Placeholder::make('application_context')->hiddenLabel()->content(fn () => self::actionContext($record, 'Review application')),
                Textarea::make('admin_note')
                    ->label('Review note')
                    ->helperText('Record what was checked and any follow-up needed.')
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (TravelFlexApplication $record, array $data): void {
                $record->update([
                    'application_status' => 'reviewed',
                    'financing_status' => 'under_review',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                    'admin_note' => $data['admin_note'] ?? $record->admin_note,
                ]);
                self::tryNotifyCustomer($record->fresh(['booking']), 'reviewed', $data['admin_note'] ?? null);

                Notification::make()->title('TravelFlex application reviewed')->success()->send();
            });
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (TravelFlexApplication $record): bool => $record->application_status !== 'approved')
            ->requiresConfirmation()
            ->modalHeading(fn (TravelFlexApplication $record): string => 'Approve TravelFlex application ' . ($record->booking_ref ?: ''))
            ->modalDescription('Approval opens the down-payment step only while the airline hold has at least two hours remaining. It does not ticket the flight.')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalSubmitActionLabel('Approve application')
            ->form(fn (TravelFlexApplication $record): array => [
                Placeholder::make('application_context')->hiddenLabel()->content(fn () => self::actionContext($record, 'Approve application')),
                Textarea::make('admin_note')->label('Approval note')->rows(4)->maxLength(2000),
            ])
            ->action(function (TravelFlexApplication $record, array $data): void {
                $record->loadMissing('booking');
                $paymentDeadline = $record->booking?->tkt_time_limit?->copy()->subHours(2);
                $requiresRebooking = ! $paymentDeadline || $paymentDeadline->isPast();

                $record->update([
                    'application_status' => 'approved',
                    'financing_status' => 'approved',
                    // The payment method is chosen after approval. Keep the deposit
                    // not due until the customer actually starts a payment so bank
                    // transfer can distinguish "not started" from "reference sent".
                    'deposit_status' => $requiresRebooking ? 'rebooking_required' : 'not_due',
                    'approved_at' => now(),
                    'approval_expires_at' => $requiresRebooking ? null : $paymentDeadline,
                    'rejected_at' => null,
                    'reviewed_at' => $record->reviewed_at ?: now(),
                    'reviewed_by' => auth()->id(),
                    'admin_note' => $data['admin_note'] ?? $record->admin_note,
                ]);
                $record->booking?->update([
                    'booking_status' => $requiresRebooking ? 'awaiting_rebooking' : 'awaiting_deposit',
                ]);

                self::tryNotifyCustomer($record->fresh(['booking']), 'approved', $data['admin_note'] ?? null);

                Notification::make()
                    ->title($requiresRebooking ? 'Approved - rebooking required' : 'TravelFlex application approved')
                    ->body($requiresRebooking ? 'Fast Credit approval was recorded, but the airline hold must be revalidated or replaced before collecting a deposit.' : null)
                    ->{$requiresRebooking ? 'warning' : 'success'}()
                    ->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (TravelFlexApplication $record): bool => $record->application_status !== 'rejected')
            ->requiresConfirmation()
            ->modalHeading(fn (TravelFlexApplication $record): string => 'Reject TravelFlex application ' . ($record->booking_ref ?: ''))
            ->modalDescription('A rejection reason is required and will be retained on the application.')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Reject application')
            ->form(fn (TravelFlexApplication $record): array => [
                Placeholder::make('application_context')->hiddenLabel()->content(fn () => self::actionContext($record, 'Reject application')),
                Textarea::make('admin_note')
                    ->label('Rejection reason')
                    ->required()
                    ->helperText('Be specific enough for another admin to understand the decision.')
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (TravelFlexApplication $record, array $data): void {
                $record->update([
                    'application_status' => 'rejected',
                    'financing_status' => 'rejected',
                    'deposit_status' => $record->deposit_status === 'paid' ? 'refund_pending' : 'not_due',
                    'rejected_at' => now(),
                    'approved_at' => null,
                    'reviewed_at' => $record->reviewed_at ?: now(),
                    'reviewed_by' => auth()->id(),
                    'admin_note' => $data['admin_note'],
                ]);
                $record->loadMissing('booking');
                if ($record->booking && ! $record->booking->ticket_ordered) {
                    $record->booking->update([
                        'booking_status' => 'on_hold',
                        'payment_method' => null,
                        'payment_flow' => null,
                    ]);
                }

                self::tryNotifyCustomer($record->fresh(['booking']), 'rejected', $data['admin_note'] ?? null);

                Notification::make()->title('TravelFlex application rejected')->success()->send();
            });
    }

    public static function resendProviderEmailAction(): Action
    {
        return Action::make('resendProviderEmail')
            ->label('Resend provider email')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(fn (TravelFlexApplication $record): string => 'Resend provider email for ' . ($record->booking_ref ?: 'application'))
            ->modalDescription('This sends the application details and uploaded documents to the configured TravelFlex provider address.')
            ->modalIcon('heroicon-o-paper-airplane')
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Resend provider email')
            ->form(fn (TravelFlexApplication $record): array => [
                Placeholder::make('application_context')->hiddenLabel()->content(fn () => self::actionContext($record, 'Provider handoff')),
            ])
            ->action(function (TravelFlexApplication $record): void {
                try {
                    app(TravelFlexApplicationService::class)->sendProviderEmail($record);
                } catch (Throwable $exception) {
                    $record->update([
                        'provider_status' => 'failed',
                        'provider_email_error' => $exception->getMessage(),
                    ]);

                    Notification::make()
                        ->title('Provider email failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()->title('Provider email sent')->success()->send();
            });
    }

    private static function watDateTime(mixed $value, string $format = 'd M Y, H:i'): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            $formatted = \Carbon\Carbon::parse($value)->timezone('Africa/Lagos')->format($format);

            return $formatted;
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private static function tryNotifyCustomer(TravelFlexApplication $record, string $status, ?string $note = null): void
    {
        try {
            $sent = app(TravelFlexApplicationService::class)->notifyCustomerStatus($record, $status, $note);

            if (! $sent) {
                Notification::make()
                    ->title('Customer email not sent')
                    ->body('No customer email address is available on this TravelFlex application.')
                    ->warning()
                    ->send();
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Customer email failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}

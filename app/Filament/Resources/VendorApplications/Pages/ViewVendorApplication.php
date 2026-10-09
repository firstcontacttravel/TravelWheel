<?php

namespace App\Filament\Resources\VendorApplications\Pages;

use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Models\VendorApplication;
use App\Services\VendorOnboardingService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

/**
 * @property VendorApplication $record
 */
class ViewVendorApplication extends ViewRecord
{
    protected static string $resource = VendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->verifyDocumentsAction(),
            $this->complianceAction(),
            $this->approveAction(),
            $this->requestInformationAction(),
            $this->rejectAction(),
            ActionGroup::make([
                $this->addDocumentAction(),
                $this->notesAction(),
            ])->label('More')->icon('heroicon-o-ellipsis-vertical')->button()->color('gray'),
        ];
    }

    private function verifyDocumentsAction(): Action
    {
        return Action::make('verifyDocuments')
            ->label('Verify documents')
            ->icon('heroicon-o-document-check')
            ->visible(fn () => in_array($this->record->status, ['received', 'pending'], true))
            ->modalDescription('Untick any document that is missing, unreadable, expired or wrong. If all are ticked, the application moves to "Documents verified".')
            ->form([
                CheckboxList::make('verified')
                    ->label('Documents that are in order')
                    ->options(fn () => $this->record->documents->mapWithKeys(fn ($doc) => [
                        $doc->id => $doc->label.' — '.$doc->original_name.($doc->isExpired() ? ' (EXPIRED)' : ''),
                    ])->all())
                    ->default(fn () => $this->record->documents->reject->isExpired()->pluck('id')->all())
                    ->bulkToggleable(),
                Textarea::make('note')->label('What is wrong with the unticked documents?')->rows(3)
                    ->helperText('Saved on each unticked document. Use "Request more information" to ask the vendor for replacements.'),
            ])
            ->action(function (array $data) {
                $rejected = app(VendorOnboardingService::class)->verifyDocuments($this->record, array_map('intval', $data['verified'] ?? []), $data['note'] ?? null, auth()->user());

                $rejected === 0
                    ? Notification::make()->success()->title('All documents verified')->send()
                    : Notification::make()->warning()->title("$rejected document(s) not in order")->body('Use "Request more information" to ask the vendor for replacements.')->send();
                $this->refreshRecord();
            });
    }

    private function complianceAction(): Action
    {
        return Action::make('compliance')
            ->label('Complete compliance')
            ->icon('heroicon-o-shield-check')
            ->visible(fn () => $this->record->status === 'documents_verified')
            ->modalDescription('Record the outcome of your checks: registry search, references, licences and the like.')
            ->form([
                Select::make('risk_rating')->label('Risk rating')->options(VendorApplication::RISK_RATINGS)->required(),
                DatePicker::make('next_review_on')->label('Next review date')
                    ->default(fn () => $this->suggestedReviewDate())
                    ->helperText('Defaults to the earliest document expiry, or one year from today.'),
                Textarea::make('note')->label('Compliance notes (internal)')->rows(3),
            ])
            ->action(function (array $data) {
                app(VendorOnboardingService::class)->completeCompliance($this->record, $data['risk_rating'], $data['next_review_on'] ?? null, $data['note'] ?? null, auth()->user());
                Notification::make()->success()->title('Compliance completed')->send();
                $this->refreshRecord();
            });
    }

    private function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn () => $this->record->status === 'compliance_completed')
            ->modalDescription('Gives the vendor a Vendor ID and emails them. An approved visa vendor is also added to Visa Vendors.')
            ->form([
                CheckboxList::make('services')
                    ->label('Approved services')
                    ->options(fn () => $this->record->serviceLabels())
                    ->default(fn () => $this->record->services)
                    ->required(),
                DatePicker::make('next_review_on')->label('Next review date')->default(fn () => $this->record->next_review_on ?? $this->suggestedReviewDate()),
                Textarea::make('note')->label('Message to the vendor (optional)')->rows(3),
            ])
            ->action(function (array $data) {
                $sent = app(VendorOnboardingService::class)->approve($this->record, $data['services'], $data['note'] ?? null, $data['next_review_on'] ?? null, auth()->user());
                $this->notifyDecision('Approved as '.$this->record->fresh()->vendor_code, $sent);
                $this->refreshRecord();
            });
    }

    private function requestInformationAction(): Action
    {
        return Action::make('requestInformation')
            ->label('Request more information')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('warning')
            ->visible(fn () => ! $this->record->isDecided())
            ->form([
                Textarea::make('request')->label('What do you need from the vendor?')->required()->rows(5)
                    ->helperText('Emailed to the vendor exactly as written. They reply by email; add any documents they send with "More → Add document".'),
            ])
            ->action(function (array $data) {
                $sent = app(VendorOnboardingService::class)->requestInformation($this->record, $data['request']);
                $this->notifyDecision('Request sent; application is awaiting more information', $sent);
                $this->refreshRecord();
            });
    }

    private function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => ! $this->record->isDecided())
            ->requiresConfirmation()
            ->modalDescription('The vendor is emailed the reason. This cannot be undone.')
            ->form([
                Textarea::make('reason')->label('Reason (sent to the vendor)')->required()->rows(4),
            ])
            ->action(function (array $data) {
                $sent = app(VendorOnboardingService::class)->reject($this->record, $data['reason'], auth()->user());
                $this->notifyDecision('Application rejected', $sent);
                $this->refreshRecord();
            });
    }

    private function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add document')
            ->icon('heroicon-o-paper-clip')
            ->modalDescription('Attach a document the vendor sent by email.')
            ->form([
                TextInput::make('label')->label('Document')->required()->datalist(collect(config('vendor_onboarding.company_documents'))->pluck('label')->all()),
                FileUpload::make('file')->disk('local')->directory(fn () => 'vendor-applications/'.$this->record->reference)
                    ->storeFileNamesIn('original_name')->maxSize(10240)->required(),
                DatePicker::make('expires_on')->label('Expiry date (if any)'),
            ])
            ->action(function (array $data) {
                $this->record->documents()->create([
                    'type' => 'added_by_staff',
                    'label' => $data['label'],
                    'disk' => 'local',
                    'path' => $data['file'],
                    'original_name' => $data['original_name'] ?? basename($data['file']),
                    'mime_type' => Storage::disk('local')->mimeType($data['file']) ?: null,
                    'size' => Storage::disk('local')->size($data['file']),
                    'expires_on' => $data['expires_on'] ?? null,
                ]);
                Notification::make()->success()->title('Document added')->send();
                $this->refreshRecord();
            });
    }

    private function notesAction(): Action
    {
        return Action::make('notes')
            ->label('Edit notes & review date')
            ->icon('heroicon-o-pencil-square')
            ->fillForm(fn () => $this->record->only(['risk_rating', 'next_review_on', 'internal_notes']))
            ->form([
                Select::make('risk_rating')->label('Risk rating')->options(VendorApplication::RISK_RATINGS),
                DatePicker::make('next_review_on')->label('Next review date'),
                Textarea::make('internal_notes')->label('Internal notes')->rows(6),
            ])
            ->action(function (array $data) {
                $this->record->update($data);
                Notification::make()->success()->title('Saved')->send();
                $this->refreshRecord();
            });
    }

    private function suggestedReviewDate(): string
    {
        $earliest = $this->record->documents->whereNotNull('expires_on')->min('expires_on');

        return ($earliest && $earliest->isFuture() ? $earliest : now()->addYear())->toDateString();
    }

    private function notifyDecision(string $title, bool $emailSent): void
    {
        $emailSent
            ? Notification::make()->success()->title($title)->body('The vendor has been emailed.')->send()
            : Notification::make()->warning()->title($title)->body('The email to the vendor could not be sent. Please contact them directly.')->send();
    }

    private function refreshRecord(): void
    {
        $this->record->refresh()->load('documents');
    }
}

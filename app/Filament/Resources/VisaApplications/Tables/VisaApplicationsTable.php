<?php

namespace App\Filament\Resources\VisaApplications\Tables;

use App\Enums\VisaApplicationStatus;
use App\Filament\Workflow\WorkItemTable;
use App\Models\VisaApplication;
use App\Models\VisaPayment;
use App\Services\VisaApplicationTransitionService;
use App\Services\VisaOperationsService;
use App\Services\VisaVendorDispatchService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisaApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->heading('Visa application queue')->description('One operational queue for standard visas and Nigerian Business Visa applications.')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['additionalDocumentRequests as open_actions_count' => fn (Builder $q) => $q->whereIn('status', ['open', 'replacement_requested'])])
                ->addSelect(['latest_payment_status' => VisaPayment::query()->select('status')->whereColumn('visa_application_id', 'visa_applications.id')->latest()->latest('id')->limit(1)]))
            ->defaultSort('created_at', 'desc')->defaultPaginationPageOption(25)->persistSearchInSession()->persistFiltersInSession()->striped()
            ->columns([
                TextColumn::make('reference')->copyable()->searchable()->weight('bold')->description(fn (VisaApplication $record) => $record->contact_email ?: 'No contact email'),
                TextColumn::make('product.name')->label('Visa')->searchable()->description(fn (VisaApplication $record) => ucfirst((string) $record->product?->family?->value)),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => VisaApplicationStatus::labelFor($state))->color(fn ($state) => VisaApplicationStatus::colorFor($state))->sortable(),
                TextColumn::make('assignee.name')->label('Officer')->placeholder('Shared queue')->sortable(),
                TextColumn::make('travelers_count')->counts('travelers')->label('Travellers'),
                TextColumn::make('open_actions_count')->label('Actions')->badge()->color(fn ($state) => $state ? 'warning' : 'gray'),
                TextColumn::make('latest_payment_status')->label('Payment')->default('none')->badge()->color(fn ($state) => $state === 'paid' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                TextColumn::make('created_at')->label('Started')->since()->sortable(),
            ])
            ->filters([
                WorkItemTable::filter(),
                SelectFilter::make('status')->multiple()->options(VisaApplicationStatus::options()),
                SelectFilter::make('visa_product_id')->label('Visa product')->relationship('product', 'name')->searchable()->preload(),
                SelectFilter::make('assigned_to')->label('Officer')->relationship('assignee', 'name')->searchable()->preload()->placeholder('All officers'),
                Filter::make('unassigned')->query(fn (Builder $query) => $query->whereNull('assigned_to'))->toggle(),
                SelectFilter::make('payment_status')->options(['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'])->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->whereHas('payments', fn ($q) => $q->where('status', $data['value'])) : $query),
                Filter::make('created_at')->form([DatePicker::make('from'), DatePicker::make('until')])->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            // Ownership and notes live in the Work panel (WorkItemActions), which
            // keeps the officer in step and respects the heads-only reassign rule.
            ->recordActions([ViewAction::make()->label('Review'), ActionGroup::make([self::sendToVendorAction(), self::requestDocumentAction(), self::transitionAction()])->icon('heroicon-o-ellipsis-horizontal')]);
    }

    public static function sendToVendorAction(): Action
    {
        $activeVendor = fn (VisaApplication $record) => $record->product?->vendor?->is_active ? $record->product->vendor : null;

        return Action::make('sendToVendor')
            ->label('Send to vendor')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->visible(fn () => auth()->user()?->canOperateVisas() ?? false)
            ->modalHeading('Send application to vendor')
            ->modalDescription('Emails the application details and every uploaded file. Each address gets its own copy.')
            ->modalWidth('lg')
            ->form(fn (VisaApplication $record) => [
                Toggle::make('include_vendor')
                    ->label(fn () => ($vendor = $activeVendor($record)) ? "Send to {$vendor->name} ({$vendor->email})" : 'Send to the product vendor')
                    ->helperText(fn () => $activeVendor($record) ? 'The address the vendor is registered with.' : 'This product has no active vendor. Add an address below.')
                    ->default(fn () => $activeVendor($record) !== null)
                    ->disabled(fn () => $activeVendor($record) === null)
                    ->live(),
                TagsInput::make('other_recipients')
                    ->label('Other email addresses')
                    ->placeholder('name@example.com')
                    ->helperText('Type an address and press Enter. Use this for another desk at the vendor, or a different processor.')
                    ->nestedRecursiveRules(['email:rfc'])
                    ->required(fn (Get $get) => ! $get('include_vendor')),
            ])
            ->modalSubmitActionLabel('Queue email')
            ->action(function (VisaApplication $record, array $data) use ($activeVendor): void {
                $recipients = array_merge(
                    ($data['include_vendor'] ?? false) && ($vendor = $activeVendor($record)) ? [$vendor->email] : [],
                    $data['other_recipients'] ?? [],
                );
                $sent = app(VisaVendorDispatchService::class)->send($record, auth()->user(), $recipients);
                Notification::make()->title('Application queued')->body('Sending to '.implode(', ', $sent).'.')->success()->send();
            });
    }

    public static function requestDocumentAction(): Action
    {
        return Action::make('requestDocument')->label('Request document')->icon('heroicon-o-document-plus')->color('warning')->visible(fn (VisaApplication $record) => (auth()->user()?->canOperateVisas() ?? false) && in_array($record->status, ['under_review', 'processing', 'action_required'], true))->form(fn (VisaApplication $record) => [
            Select::make('visa_traveler_id')->label('Traveller')->options($record->travelers->mapWithKeys(fn ($t) => [$t->id => trim("{$t->first_name} {$t->last_name}").' ('.self::label($t->traveler_type).')']))->nullable()->helperText('Leave empty for an application-level document.'),
            Select::make('visa_requirement_id')->label('Catalogue requirement')->options($record->product->requirements()->where('is_active', true)->pluck('name', 'id'))->nullable()->searchable(),
            TextInput::make('title')->required()->maxLength(255), Textarea::make('instructions')->required()->rows(4)->maxLength(3000), DatePicker::make('due_at')->label('Deadline')->minDate(now()->toDateString()),
        ])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->requestDocument($record, auth()->user(), $data);
            Notification::make()->title('Document request sent')->success()->send();
        });
    }

    public static function reviewDocumentAction(): Action
    {
        return Action::make('reviewDocument')->label('Review application document')->icon('heroicon-o-document-check')->visible(fn (VisaApplication $record) => (auth()->user()?->canOperateVisas() ?? false) && $record->documents()->exists())->form(fn (VisaApplication $record) => [
            Select::make('document_id')->label('Document')->options($record->documents()->with('requirement')->get()->mapWithKeys(fn ($d) => [$d->id => ($d->requirement?->name ?: 'Document').' — '.$d->original_name]))->required()->searchable(),
            Select::make('status')->options(['accepted' => 'Accept', 'rejected' => 'Reject'])->required(), Textarea::make('note')->rows(3)->maxLength(2000),
        ])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->reviewApplicationDocument($record->documents()->findOrFail($data['document_id']), auth()->user(), $data['status'], $data['note'] ?? null);
            Notification::make()->title('Document review saved')->success()->send();
        });
    }

    public static function reviewRequestAction(): Action
    {
        return Action::make('reviewRequestedDocument')->label('Review requested upload')->icon('heroicon-o-clipboard-document-check')->visible(fn (VisaApplication $record) => (auth()->user()?->canOperateVisas() ?? false) && $record->additionalDocumentRequests()->where('status', 'submitted')->exists())->form(fn (VisaApplication $record) => [
            Select::make('request_id')->label('Submitted request')->options($record->additionalDocumentRequests()->where('status', 'submitted')->pluck('title', 'id'))->required(),
            Select::make('status')->options(['accepted' => 'Accept', 'replacement_requested' => 'Request replacement'])->required(), Textarea::make('note')->required()->rows(3)->maxLength(2000),
        ])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->reviewRequestedDocument($record->additionalDocumentRequests()->findOrFail($data['request_id']), auth()->user(), $data['status'], $data['note']);
            Notification::make()->title('Requested document reviewed')->success()->send();
        });
    }

    public static function transitionAction(): Action
    {
        return Action::make('transition')->label('Change status')->icon('heroicon-o-arrows-right-left')->color('primary')->visible(fn (VisaApplication $record) => (auth()->user()?->canOperateVisas() ?? false) && app(VisaApplicationTransitionService::class)->allowedTargets($record, auth()->user()) !== [])->form(fn (VisaApplication $record) => [
            Select::make('status')->label('New status')->options(collect(app(VisaApplicationTransitionService::class)->allowedTargets($record, auth()->user()))->mapWithKeys(fn ($status) => [$status => VisaApplicationStatus::labelFor($status)]))->required(),
            Textarea::make('public_note')->label('Applicant-visible note')->rows(3)->maxLength(2000), Textarea::make('internal_note')->label('Internal reason/note')->rows(3)->maxLength(3000),
            DatePicker::make('decision_date')->label('Decision date'), TextInput::make('decision_reference')->label('Authority decision reference')->maxLength(255), Textarea::make('no_document_reason')->label('Authorized no-document reason')->rows(2),
        ])->action(function (VisaApplication $record, array $data) {
            app(VisaApplicationTransitionService::class)->transition($record, $data['status'], auth()->user(), $data);
            Notification::make()->title('Application status updated')->success()->send();
        });
    }

    public static function issueAction(): Action
    {
        return Action::make('issue')->label('Upload and issue visa')->icon('heroicon-o-shield-check')->color('success')->visible(fn (VisaApplication $record) => (auth()->user()?->canOperateVisas() ?? false) && $record->status === 'approved')->form([
            FileUpload::make('document_path')->label('Issued visa document')->disk('local')->directory(fn (VisaApplication $record) => "visa-applications/{$record->reference}/issued")->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(10240)->storeFileNamesIn('document_name')->required(),
            DatePicker::make('valid_from')->label('Valid from')->required(), DatePicker::make('valid_until')->label('Valid until')->required()->afterOrEqual('valid_from'), TextInput::make('decision_reference')->label('Visa/decision reference')->maxLength(255), Textarea::make('internal_note')->label('Internal issuance note')->rows(2),
        ])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->issue($record, auth()->user(), $data);
            app(VisaApplicationTransitionService::class)->transition($record->fresh(), 'issued', auth()->user(), $data + ['public_note' => 'Your issued visa document is ready for download.']);
            Notification::make()->title('Visa issued')->success()->send();
        });
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str($value)->replace('_', ' ')->headline()->toString() : '-';
    }
}

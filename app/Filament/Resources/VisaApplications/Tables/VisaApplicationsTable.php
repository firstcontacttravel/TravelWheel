<?php

namespace App\Filament\Resources\VisaApplications\Tables;

use App\Models\User;
use App\Models\VisaApplication;
use App\Services\VisaApplicationTransitionService;
use App\Services\VisaOperationsService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisaApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->heading('Visa application queue')->description('One operational queue for standard visa and VOA applications.')
            ->defaultSort('created_at', 'desc')->defaultPaginationPageOption(25)->persistSearchInSession()->persistFiltersInSession()->striped()
            ->columns([
                TextColumn::make('reference')->copyable()->searchable()->weight('bold')->description(fn (VisaApplication $record) => $record->contact_email ?: 'No contact email'),
                TextColumn::make('product.name')->label('Visa')->searchable()->description(fn (VisaApplication $record) => ucfirst((string) $record->product?->family?->value)),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => self::label($state))->color(fn ($state) => self::statusColor($state))->sortable(),
                TextColumn::make('assignee.name')->label('Officer')->placeholder('Shared queue')->sortable(),
                TextColumn::make('travelers_count')->counts('travelers')->label('Travellers'),
                TextColumn::make('open_actions')->label('Actions')->state(fn (VisaApplication $record) => $record->additionalDocumentRequests()->whereIn('status', ['open', 'replacement_requested'])->count())->badge()->color(fn ($state) => $state ? 'warning' : 'gray'),
                TextColumn::make('latest_payment')->label('Payment')->state(fn (VisaApplication $record) => $record->payments()->latest()->value('status') ?: 'none')->badge()->color(fn ($state) => $state === 'paid' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                TextColumn::make('created_at')->label('Started')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()->options(self::statuses()),
                SelectFilter::make('visa_product_id')->label('Visa product')->relationship('product', 'name')->searchable()->preload(),
                SelectFilter::make('assigned_to')->label('Officer')->relationship('assignee', 'name')->searchable()->preload()->placeholder('All officers'),
                Filter::make('unassigned')->query(fn (Builder $query) => $query->whereNull('assigned_to'))->toggle(),
                SelectFilter::make('payment_status')->options(['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'])->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->whereHas('payments', fn ($q) => $q->where('status', $data['value'])) : $query),
                Filter::make('created_at')->form([DatePicker::make('from'), DatePicker::make('until')])->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()->label('Review'), ActionGroup::make([self::assignAction(), self::requestDocumentAction(), self::transitionAction()])->icon('heroicon-o-ellipsis-horizontal')]);
    }

    public static function assignAction(): Action
    {
        return Action::make('assign')->icon('heroicon-o-user-plus')->visible(fn () => auth()->user()?->canOperateVisas() ?? false)->form([
            Select::make('assigned_to')->label('Assigned officer')->options(fn () => User::query()->where(fn ($q) => $q->where('is_admin', true)->orWhereIn('visa_role', ['administrator', 'visa_officer']))->orderBy('name')->pluck('name', 'id'))->searchable()->nullable()->helperText('Leave empty to return this application to the shared queue.'),
        ])->fillForm(fn (VisaApplication $record) => ['assigned_to' => $record->assigned_to])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->assign($record, filled($data['assigned_to']) ? User::find($data['assigned_to']) : null, auth()->user());
            Notification::make()->title('Assignment updated')->success()->send();
        });
    }

    public static function addNoteAction(): Action
    {
        return Action::make('addNote')->label('Add internal note')->icon('heroicon-o-chat-bubble-left-ellipsis')->visible(fn () => auth()->user()?->canOperateVisas() ?? false)->form([Textarea::make('body')->label('Internal note')->required()->rows(5)->maxLength(5000)])->action(function (VisaApplication $record, array $data) {
            app(VisaOperationsService::class)->addNote($record, auth()->user(), $data['body']);
            Notification::make()->title('Internal note added')->success()->send();
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
            Select::make('status')->label('New status')->options(collect(app(VisaApplicationTransitionService::class)->allowedTargets($record, auth()->user()))->mapWithKeys(fn ($status) => [$status => self::label($status)]))->required(),
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

    private static function statuses(): array
    {
        return collect(['draft', 'awaiting_payment', 'submitted', 'under_review', 'action_required', 'processing', 'approved', 'issued', 'rejected', 'cancelled', 'expired'])->mapWithKeys(fn ($s) => [$s => self::label($s)])->all();
    }

    private static function label(?string $value): string
    {
        return filled($value) ? str($value)->replace('_', ' ')->headline()->toString() : '-';
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            'issued','approved' => 'success', 'rejected','cancelled','expired' => 'danger', 'action_required','awaiting_payment' => 'warning', 'under_review','processing' => 'info', default => 'gray'
        };
    }
}

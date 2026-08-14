<?php

namespace App\Filament\Resources\VisaProducts\Pages;

use App\Enums\VisaPublicationStatus;
use App\Filament\Resources\VisaProducts\VisaProductResource;
use App\Services\VisaCataloguePublicationService;
use App\Services\VisaFormWorkflow;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditVisaProduct extends EditRecord
{
    protected static string $resource = VisaProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['form_configuration'] = app(VisaFormWorkflow::class)->normalize($data['form_configuration'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
            Action::make('publish')
                ->label('Validate and publish')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->publication_status !== VisaPublicationStatus::Published)
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        app(VisaCataloguePublicationService::class)->publish($this->record);
                    } catch (ValidationException $exception) {
                        Notification::make()->title('Product is not ready to publish')->body(collect($exception->errors())->flatten()->map(fn ($error) => '• '.$error)->implode("\n"))->danger()->persistent()->send();

                        return;
                    }
                    Notification::make()->title('Visa product published')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('unpublish')
                ->label('Return to draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => $this->record->publication_status === VisaPublicationStatus::Published)
                ->requiresConfirmation()
                ->action(function (): void {
                    app(VisaCataloguePublicationService::class)->unpublish($this->record);
                    Notification::make()->title('Visa product returned to draft')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            DeleteAction::make(),
        ];
    }
}

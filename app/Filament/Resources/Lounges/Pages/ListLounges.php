<?php

namespace App\Filament\Resources\Lounges\Pages;

use App\Filament\Resources\Lounges\LoungeResource;
use App\Models\AppSetting;
use App\Models\Lounge;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLounges extends ListRecords
{
    protected static string $resource = LoungeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Flat markup added on top of every LoungePair (API) lounge price,
            // after converting it to naira. Local lounges use their own
            // per-lounge Markup field instead.
            Action::make('loungePairMarkup')
                ->label('LoungePair markup')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->modalDescription('Added to every LoungePair lounge price after converting it to naira. Your own lounges use the Markup field on each lounge instead.')
                ->fillForm(fn (): array => [
                    'nigeria' => Lounge::providerMarkupNigeria(),
                    'international' => Lounge::providerMarkupInternational(),
                ])
                ->form([
                    TextInput::make('nigeria')
                        ->label('Nigerian airports')
                        ->numeric()->minValue(0)->required()->prefix('₦'),
                    TextInput::make('international')
                        ->label('Airports outside Nigeria')
                        ->numeric()->minValue(0)->required()->prefix('₦'),
                ])
                ->action(function (array $data): void {
                    AppSetting::set(Lounge::PROVIDER_MARKUP_NIGERIA_KEY, (float) $data['nigeria']);
                    AppSetting::set(Lounge::PROVIDER_MARKUP_INTERNATIONAL_KEY, (float) $data['international']);
                    Notification::make()->title('LoungePair markup updated')->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}

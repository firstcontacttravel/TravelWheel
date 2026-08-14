<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasBackHeaderAction
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }
}

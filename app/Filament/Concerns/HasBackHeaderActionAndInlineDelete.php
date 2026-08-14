<?php

namespace App\Filament\Concerns;

use Filament\Actions\DeleteAction;

/**
 * Puts Delete alongside Save/Cancel in the form's action row instead of the
 * page header, and replaces the header action with a plain Back button.
 */
trait HasBackHeaderActionAndInlineDelete
{
    use HasBackHeaderAction;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            DeleteAction::make(),
        ];
    }
}

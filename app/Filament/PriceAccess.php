<?php

namespace App\Filament;

use App\Filament\Concerns\EditableByPricingStaff;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

/**
 * Filament's own create, edit and delete buttons don't ask the resource's
 * canCreate() and friends, so a page guarded by EditableByPricingStaff would
 * still offer them. Registered on every action (AppServiceProvider); a hidden
 * action is also refused if someone calls it anyway.
 */
class PriceAccess
{
    public static function blocks(Action $action): bool
    {
        if (! $action instanceof CreateAction && ! $action instanceof EditAction
            && ! $action instanceof DeleteAction && ! $action instanceof DeleteBulkAction) {
            return false;
        }

        $livewire = $action->getLivewire();
        $resource = is_object($livewire) && method_exists($livewire, 'getResource') ? $livewire::getResource() : null;

        return $resource
            && in_array(EditableByPricingStaff::class, class_uses_recursive($resource), true)
            && ! $resource::canEditPrices();
    }
}

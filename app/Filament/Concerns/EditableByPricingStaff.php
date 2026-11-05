<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * For a resource holding prices or rates customers pay: the CEO, Operations
 * and Finance change it, every other member of staff only views the list.
 * A resource's own stricter canCreate() or canDelete() still takes priority.
 */
trait EditableByPricingStaff
{
    public static function canEditPrices(): bool
    {
        return auth()->user()?->canEditPrices() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canEditPrices();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canEditPrices();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEditPrices();
    }

    public static function canDeleteAny(): bool
    {
        return static::canEditPrices();
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingRevenueTrend;
use App\Filament\Widgets\BookingsNeedingAttention;
use App\Filament\Widgets\OperationsTriage;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Operations Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    public function getWidgets(): array
    {
        return [
            // Broken, then waiting, then money. Replaces the two
            // stats-overview widgets whose seventeen identical tiles made a
            // failed payment and a month's revenue look the same.
            OperationsTriage::class,
            BookingsNeedingAttention::class,
            BookingRevenueTrend::class,
        ];
    }

    public function getColumns(): int|array
    {
        // One column. The order is the design — putting what is broken beside
        // what is merely waiting says they are equally urgent.
        return 1;
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingRevenueTrend;
use App\Filament\Widgets\BookingsNeedingAttention;
use App\Filament\Widgets\PaymentOperationsOverview;
use App\Filament\Widgets\VisaOperationsOverview;
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
            PaymentOperationsOverview::class,
            VisaOperationsOverview::class,
            BookingRevenueTrend::class,
            BookingsNeedingAttention::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}

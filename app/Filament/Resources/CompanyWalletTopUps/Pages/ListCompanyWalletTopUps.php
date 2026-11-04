<?php

namespace App\Filament\Resources\CompanyWalletTopUps\Pages;

use App\Filament\Resources\CompanyWalletTopUps\CompanyWalletTopUpResource;
use App\Models\CompanyWalletTopUp;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCompanyWalletTopUps extends ListRecords
{
    protected static string $resource = CompanyWalletTopUpResource::class;

    public function getTabs(): array
    {
        return [
            'to_confirm' => Tab::make('To confirm')
                ->badge(fn (): int => CompanyWalletTopUp::query()->where('status', 'pending')->where('method', 'bank_transfer')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')->where('method', 'bank_transfer')),
            'all' => Tab::make('All'),
        ];
    }
}

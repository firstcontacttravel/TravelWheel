<?php

namespace App\Filament\Resources\VendorApplications\Tables;

use App\Models\VendorApplication;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('registered_name')->label('Company')->searchable(['registered_name', 'trading_name'])
                    ->description(fn (VendorApplication $record) => $record->trading_name ?: null),
                TextColumn::make('services')->label('Services')
                    ->state(fn (VendorApplication $record) => array_values($record->serviceLabels()))
                    ->badge()->color('gray')->limitList(3)->expandableLimitedList(),
                TextColumn::make('country')->toggleable(),
                TextColumn::make('contact_name')->label('Contact')->searchable(['contact_name', 'contact_email'])
                    ->description(fn (VendorApplication $record) => $record->contact_email),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => VendorApplication::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => VendorApplication::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('vendor_code')->label('Vendor ID')->placeholder('-')->searchable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime('j M Y, g:i A')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(VendorApplication::STATUSES),
                SelectFilter::make('service')
                    ->options(collect(config('vendor_onboarding.services'))->map(fn ($service) => $service['label'])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereJsonContains('services', $data['value'])
                        : $query),
            ])
            ->recordActions([ViewAction::make()]);
    }
}

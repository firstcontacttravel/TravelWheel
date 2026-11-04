<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyUser;
use App\Models\CompanyWalletEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Company')->columns(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('cac_number')->label('CAC number')->copyable(),
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => Company::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => Company::STATUS_COLORS[$state] ?? 'gray'),
                TextEntry::make('created_at')->label('Added')->dateTime('j M Y, g:i A', 'Africa/Lagos'),
                TextEntry::make('status_reason')->label('Reason (internal)')->columnSpanFull()
                    ->visible(fn (Company $record): bool => filled($record->status_reason)),
            ]),

            Section::make('Named contact')->columns(3)->schema([
                TextEntry::make('contact_name')->label('Name'),
                TextEntry::make('contact_email')->label('Email')->copyable(),
                TextEntry::make('contact_phone')->label('Phone')->placeholder('-'),
            ]),

            Section::make('Wallet')->schema([
                TextEntry::make('wallet_balance')->label('Balance')->money('NGN')->weight('bold'),
                RepeatableEntry::make('recentWalletEntries')->label('Latest 50 entries')->columns(5)
                    ->placeholder('Nothing yet.')
                    ->schema([
                        TextEntry::make('created_at')->label('When')->dateTime('j M Y, g:i A', 'Africa/Lagos'),
                        TextEntry::make('type')->state(fn (CompanyWalletEntry $record): string => $record->typeLabel()),
                        TextEntry::make('amount')->money('NGN'),
                        TextEntry::make('balance_after')->label('Balance after')->money('NGN'),
                        TextEntry::make('reason')->placeholder('-'),
                    ]),
            ]),

            Section::make('People')->schema([
                RepeatableEntry::make('users')->hiddenLabel()->columns(4)
                    ->placeholder('Nobody has joined yet.')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('role')->formatStateUsing(fn (string $state): string => CompanyUser::ROLES[$state] ?? $state),
                        TextEntry::make('deactivated_at')->label('Status')
                            ->state(fn (CompanyUser $record): string => $record->isDeactivated() ? 'Deactivated' : 'Active'),
                    ]),
            ]),

            Section::make('Open invitations')->schema([
                RepeatableEntry::make('pendingInvitations')->hiddenLabel()->columns(3)
                    ->placeholder('No open invitations.')
                    ->schema([
                        TextEntry::make('email'),
                        TextEntry::make('role')->formatStateUsing(fn (string $state): string => CompanyUser::ROLES[$state] ?? $state),
                        TextEntry::make('expires_at')->label('Expires')
                            ->state(fn (CompanyInvitation $record): string => $record->expires_at->timezone('Africa/Lagos')->format('j M Y, g:i A')),
                    ]),
            ]),
        ]);
    }
}

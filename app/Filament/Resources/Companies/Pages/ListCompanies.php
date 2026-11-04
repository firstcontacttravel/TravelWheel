<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Services\Corporate\CorporateAccounts;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function getSubheading(): ?string
    {
        return 'Public request form: '.route('business.request.create');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addCompany')
                ->label('Add company')
                ->icon('heroicon-o-plus')
                ->modalDescription('Adds the company and emails its named contact a single-use invitation to become its Company Admin.')
                ->visible(fn (): bool => CompanyResource::canManageAccounts())
                ->authorize(fn (): bool => CompanyResource::canManageAccounts())
                ->form([
                    TextInput::make('name')->label('Company name')->required()->maxLength(150),
                    TextInput::make('cac_number')->label('CAC number')->required()->maxLength(30)
                        ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (Company::open()->where('cac_number', Company::normalizeCacNumber((string) $value))->exists()) {
                                $fail('A company with this CAC number is already on file.');
                            }
                        }]),
                    TextInput::make('contact_name')->label('Contact name')->required()->maxLength(120),
                    TextInput::make('contact_email')->label('Contact email')->email()->required()->maxLength(190)
                        ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (app(CorporateAccounts::class)->emailIsTaken((string) $value)) {
                                $fail('This email already has a corporate account.');
                            }
                        }]),
                    TextInput::make('contact_phone')->label('Contact phone')->tel()->maxLength(30),
                ])
                ->action(function (array $data): void {
                    [, $sent] = app(CorporateAccounts::class)->addCompany($data, auth()->user());

                    $sent
                        ? Notification::make()->success()->title('Company added and invitation sent')->send()
                        : Notification::make()->warning()->title('Company added, but the invitation email could not be sent')->body('Open the company and use "Send invitation".')->send();
                }),
        ];
    }
}

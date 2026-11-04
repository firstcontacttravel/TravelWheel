<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Schemas\CorporateRatesForm;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Services\Corporate\CompanyWallet;
use App\Services\Corporate\CorporateAccounts;
use App\Services\Corporate\CorporateRates;
use App\Services\Corporate\InsufficientWalletBalance;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

/**
 * @property Company $record
 */
class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->rejectAction(),
            $this->inviteAction(),
            $this->revokeInvitationAction(),
            $this->ratesAction(),
            $this->adjustWalletAction(),
            $this->payoutAction(),
            $this->deleteTravellersAction(),
            $this->reactivateAction(),
            $this->suspendAction(),
        ];
    }

    private function approveAction(): Action
    {
        return $this->managed('approve')
            ->label('Approve and invite')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (): bool => $this->record->hasStatus('requested'))
            ->modalDescription('Check the CAC number first. This emails the named contact a single-use invitation to become the Company Admin.')
            ->fillForm(fn (): array => ['name' => $this->record->contact_name, 'email' => $this->record->contact_email])
            ->form($this->contactFields())
            ->action(function (array $data): void {
                $sent = app(CorporateAccounts::class)->approve($this->record, $data['name'], $data['email'], auth()->user());
                $this->notifyInvite('Company approved', $sent);
                $this->record->refresh();
            });
    }

    private function rejectAction(): Action
    {
        return $this->managed('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => $this->record->hasStatus('requested'))
            ->modalDescription('No email is sent. If the company should be told, please contact them directly.')
            ->form([
                Textarea::make('reason')->label('Reason (internal)')->required()->rows(3),
            ])
            ->action(function (array $data): void {
                app(CorporateAccounts::class)->reject($this->record, $data['reason']);
                Notification::make()->success()->title('Request rejected')->send();
                $this->record->refresh();
            });
    }

    private function inviteAction(): Action
    {
        return $this->managed('invite')
            ->label('Send invitation')
            ->icon('heroicon-o-envelope')
            ->visible(fn (): bool => $this->record->hasStatus('invited', 'active'))
            ->modalDescription('Emails a single-use link to become a Company Admin. Sending again to the same email cancels the earlier link.')
            ->fillForm(fn (): array => $this->record->hasStatus('invited')
                ? ['name' => $this->record->contact_name, 'email' => $this->record->contact_email]
                : [])
            ->form($this->contactFields())
            ->action(function (array $data): void {
                $sent = app(CorporateAccounts::class)->invite($this->record, $data['email'], $data['name'], 'admin', auth()->user());
                $this->notifyInvite('Invitation saved', $sent);
                $this->record->refresh();
            });
    }

    private function revokeInvitationAction(): Action
    {
        return $this->managed('revokeInvitation')
            ->label('Revoke invitation')
            ->icon('heroicon-o-no-symbol')
            ->color('gray')
            ->visible(fn (): bool => $this->record->hasStatus('invited', 'active') && $this->record->pendingInvitations()->exists())
            ->form([
                Select::make('invitation')->label('Invitation')->required()
                    ->options(fn (): array => $this->record->pendingInvitations()->pluck('email', 'id')->all()),
            ])
            ->action(function (array $data): void {
                $invitation = $this->record->pendingInvitations()->findOrFail($data['invitation']);
                assert($invitation instanceof CompanyInvitation);

                app(CorporateAccounts::class)->revoke($invitation);
                Notification::make()->success()->title('Invitation revoked')->send();
                $this->record->refresh();
            });
    }

    private function ratesAction(): Action
    {
        return $this->managed('rates')
            ->label('Set rates')
            ->icon('heroicon-o-banknotes')
            ->color('gray')
            ->visible(fn (): bool => ! $this->record->hasStatus('rejected'))
            ->modalWidth('5xl')
            ->modalDescription('Charged per passenger on this company\'s fares from its next search. Leave a rate blank to use the amount shown in grey, which is the default corporate rate or, if there is none, the public rate.')
            ->fillForm(fn (): array => ['rates' => app(CorporateRates::class)->saved($this->record)])
            ->form(fn (): array => CorporateRatesForm::sections($this->record))
            ->action(function (array $data): void {
                CorporateRatesForm::notifySaved(app(CorporateRates::class)->save($this->record, $data['rates'] ?? []));
            });
    }

    /** A correction goes on the ledger as its own entry. Finance's call, not only the CEO's. */
    private function adjustWalletAction(): Action
    {
        return Action::make('adjustWallet')
            ->label('Adjust wallet')
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->authorize(fn (): bool => auth()->user()?->canHandleMoney() ?? false)
            ->visible(fn (): bool => (auth()->user()?->canHandleMoney() ?? false) && ! $this->record->hasStatus('requested', 'rejected'))
            ->modalDescription('For correcting a mistake. The company sees this entry and its reason in its wallet history. Use a minus sign to take money out.')
            ->form([
                TextInput::make('amount')->label('Amount')->numeric()->required()->prefix('₦')
                    ->notIn(['0'])
                    ->helperText('For example 5000 to add, or -5000 to take out.'),
                Textarea::make('reason')->label('Reason (shown to the company)')->required()->maxLength(500)->rows(2),
            ])
            ->action(function (array $data): void {
                try {
                    app(CompanyWallet::class)->post($this->record, 'adjustment', (float) $data['amount'], 'adjustment:'.Str::uuid(), [
                        'user_id' => auth()->id(),
                        'reason' => $data['reason'],
                    ]);
                } catch (InsufficientWalletBalance) {
                    Notification::make()->danger()->title('The balance is too low for that')->send();

                    return;
                }

                Notification::make()->success()->title('Wallet adjusted')->send();
                $this->record->refresh();
            });
    }

    /** Records money sent back to the company's bank, which is done outside this system. */
    private function payoutAction(): Action
    {
        return Action::make('payoutWallet')
            ->label('Record payout')
            ->icon('heroicon-o-building-library')
            ->color('gray')
            ->authorize(fn (): bool => auth()->user()?->canHandleMoney() ?? false)
            ->visible(fn (): bool => (auth()->user()?->canHandleMoney() ?? false) && (float) $this->record->wallet_balance > 0)
            ->modalDescription('Send the money from the bank first, then record it here. This takes it out of the wallet.')
            ->form([
                TextInput::make('amount')->label('Amount paid out')->numeric()->minValue(0.01)->required()->prefix('₦'),
                TextInput::make('bank_reference')->label('Bank reference')->required()->maxLength(100),
            ])
            ->action(function (array $data): void {
                try {
                    app(CompanyWallet::class)->post($this->record, 'payout', -(float) $data['amount'], 'payout:'.Str::uuid(), [
                        'user_id' => auth()->id(),
                        'reason' => 'Paid to your bank, reference '.$data['bank_reference'],
                    ]);
                } catch (InsufficientWalletBalance) {
                    Notification::make()->danger()->title('The balance is too low for that')->send();

                    return;
                }

                Notification::make()->success()->title('Payout recorded')->send();
                $this->record->refresh();
            });
    }

    /**
     * For a company that has left: its saved passports shouldn't outlive it.
     * Bookings keep their own encrypted record of who flew.
     */
    private function deleteTravellersAction(): Action
    {
        return $this->managed('deleteTravellers')
            ->label('Delete saved travellers')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (): bool => $this->record->hasStatus('suspended') && $this->record->travellers()->exists())
            ->requiresConfirmation()
            ->modalDescription('Permanently deletes this company\'s saved travellers and their passports. This cannot be undone.')
            ->action(function (): void {
                $count = $this->record->travellers()->count();
                $this->record->travellers()->delete();

                ActivityLog::record('corporate.travellers_deleted', 'Deleted '.$count.' saved travellers for '.$this->record->name, $this->record);

                Notification::make()->success()->title('Saved travellers deleted')->send();
            });
    }

    private function suspendAction(): Action
    {
        return $this->managed('suspend')
            ->label('Suspend')
            ->icon('heroicon-o-pause-circle')
            ->color('danger')
            ->visible(fn (): bool => $this->record->hasStatus('invited', 'active'))
            ->requiresConfirmation()
            ->modalDescription('Everyone at this company is signed out at once and can\'t sign in or use an invitation until it is reactivated.')
            ->form([
                Textarea::make('reason')->label('Reason (internal)')->required()->rows(3),
            ])
            ->action(function (array $data): void {
                app(CorporateAccounts::class)->suspend($this->record, $data['reason']);
                Notification::make()->success()->title('Company suspended')->send();
                $this->record->refresh();
            });
    }

    private function reactivateAction(): Action
    {
        return $this->managed('reactivate')
            ->label('Reactivate')
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->visible(fn (): bool => $this->record->hasStatus('suspended'))
            ->requiresConfirmation()
            ->action(function (): void {
                app(CorporateAccounts::class)->reactivate($this->record);
                Notification::make()->success()->title('Company reactivated')->send();
                $this->record->refresh();
            });
    }

    /**
     * An action only the CEO can run. Refused on the server as well as hidden.
     * Each one is on the activity log through ActivityRecorder.
     */
    private function managed(string $name): Action
    {
        return Action::make($name)->authorize(fn (): bool => CompanyResource::canManageAccounts());
    }

    private function contactFields(): array
    {
        return [
            TextInput::make('name')->label('Contact name')->required()->maxLength(120),
            TextInput::make('email')->label('Contact email')->email()->required()->maxLength(190)
                ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(CorporateAccounts::class)->emailIsTaken((string) $value)) {
                        $fail('This email already has a corporate account.');
                    }
                }]),
        ];
    }

    private function notifyInvite(string $title, bool $sent): void
    {
        $sent
            ? Notification::make()->success()->title($title)->body('The invitation has been emailed.')->send()
            : Notification::make()->warning()->title($title)->body('The email could not be sent. Please use "Send invitation" to try again.')->send();
    }
}

<?php

namespace App\Filament\Resources\VendorApplications\Pages;

use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Mail\VendorInvitationMail;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ListVendorApplications extends ListRecords
{
    protected static string $resource = VendorApplicationResource::class;

    public function getSubheading(): ?string
    {
        return 'Registration form link to send vendors: '.route('partners.register');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openForm')
                ->label('Open form')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('partners.register'))
                ->openUrlInNewTab(),

            Action::make('invite')
                ->label('Invite a vendor')
                ->icon('heroicon-o-envelope')
                ->modalDescription('Emails the vendor a link to the registration form.')
                ->form([
                    TextInput::make('email')->label('Vendor email')->email()->required(),
                    TextInput::make('contact_name')->label('Contact name'),
                    TextInput::make('company_name')->label('Company name'),
                    Textarea::make('message')->label('Personal message (optional)')->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::to($data['email'])->send(new VendorInvitationMail($data['contact_name'] ?? null, $data['company_name'] ?? null, $data['message'] ?? null));
                    } catch (\Throwable $e) {
                        Log::error('Vendor invitation email failed', ['email' => $data['email'], 'error' => $e->getMessage()]);
                        Notification::make()->danger()->title('The invitation could not be sent')->body('The mail server did not accept it. Try again shortly, or send the form link yourself.')->send();

                        return;
                    }

                    Notification::make()->success()->title('Invitation sent to '.$data['email'])->send();
                }),
        ];
    }
}

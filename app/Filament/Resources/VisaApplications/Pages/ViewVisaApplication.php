<?php

namespace App\Filament\Resources\VisaApplications\Pages;

use App\Filament\Resources\VisaApplications\Tables\VisaApplicationsTable;
use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewVisaApplication extends ViewRecord
{
    protected static string $resource = VisaApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([VisaApplicationsTable::assignAction(), VisaApplicationsTable::addNoteAction()])->label('Ownership')->icon('heroicon-o-user-group')->button(),
            VisaApplicationsTable::sendToVendorAction()->button(),
            ActionGroup::make([VisaApplicationsTable::requestDocumentAction(), VisaApplicationsTable::reviewDocumentAction(), VisaApplicationsTable::reviewRequestAction()])->label('Documents')->icon('heroicon-o-document-check')->button(),
            ActionGroup::make([VisaApplicationsTable::transitionAction(), VisaApplicationsTable::issueAction()])->label('Decision')->icon('heroicon-o-arrows-right-left')->color('primary')->button(),
        ];
    }
}

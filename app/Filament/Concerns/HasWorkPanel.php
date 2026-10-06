<?php

namespace App\Filament\Concerns;

use App\Filament\Workflow\EscalationActions;
use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemActions;
use App\Support\Admin\WorkItemPresentation;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * For a service booking's view page: the Work panel above the booking's own
 * details, and the Progress, Work and Escalation menus in the header.
 *
 * Flight bookings and visa applications place their Work section inside their
 * own infolists instead; this is for the pages that have none to extend.
 */
trait HasWorkPanel
{
    /** ViewRecord::content() with the Work section in front. */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Work')
                ->description('Who owns this booking, which step it is at, and everything done to it.')
                ->schema([
                    Html::make(fn () => WorkItemPresentation::panel($this->getRecord()))->columnSpanFull(),
                ])
                ->collapsible(),
            $this->hasInfolist() ? $this->getInfolistContentComponent() : $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    /** @return array<int, mixed> */
    protected function workHeaderActions(): array
    {
        return [
            FulfilmentActions::group(),
            WorkItemActions::group(),
            EscalationActions::responseGroup(),
        ];
    }
}

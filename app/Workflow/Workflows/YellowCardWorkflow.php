<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\SupportYellowCards\SupportYellowCardResource;
use App\Models\SupportYellowCard;

class YellowCardWorkflow extends SupportRequestWorkflow
{
    public function service(): string
    {
        return 'yellow_card';
    }

    public function label(): string
    {
        return 'Yellow Card';
    }

    public function subjectClass(): string
    {
        return SupportYellowCard::class;
    }

    protected function resource(): string
    {
        return SupportYellowCardResource::class;
    }
}

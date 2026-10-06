<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\SupportExtraLuggages\SupportExtraLuggageResource;
use App\Models\SupportExtraLuggage;

class ExtraLuggageWorkflow extends SupportRequestWorkflow
{
    public function service(): string
    {
        return 'extra_luggage';
    }

    public function label(): string
    {
        return 'Extra Luggage';
    }

    public function subjectClass(): string
    {
        return SupportExtraLuggage::class;
    }

    protected function resource(): string
    {
        return SupportExtraLuggageResource::class;
    }
}

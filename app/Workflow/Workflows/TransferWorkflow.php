<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\Transfers\TransferResource;
use App\Models\Transfer;

class TransferWorkflow extends GroundTransportWorkflow
{
    public function service(): string
    {
        return 'transfers';
    }

    public function label(): string
    {
        return 'Transfers';
    }

    public function subjectClass(): string
    {
        return Transfer::class;
    }

    protected function resource(): string
    {
        return TransferResource::class;
    }
}

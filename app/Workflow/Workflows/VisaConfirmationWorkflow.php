<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\SupportVisaConfirmations\SupportVisaConfirmationResource;
use App\Models\SupportVisaConfirmation;

class VisaConfirmationWorkflow extends SupportRequestWorkflow
{
    public function service(): string
    {
        return 'visa_confirmation';
    }

    public function label(): string
    {
        return 'Visa Confirmation';
    }

    public function subjectClass(): string
    {
        return SupportVisaConfirmation::class;
    }

    protected function resource(): string
    {
        return SupportVisaConfirmationResource::class;
    }
}

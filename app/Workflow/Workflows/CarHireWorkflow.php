<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\CarHires\CarHireResource;
use App\Models\CarHire;

class CarHireWorkflow extends GroundTransportWorkflow
{
    public function service(): string
    {
        return 'car_hire';
    }

    public function label(): string
    {
        return 'Car Hire';
    }

    public function subjectClass(): string
    {
        return CarHire::class;
    }

    protected function resource(): string
    {
        return CarHireResource::class;
    }
}

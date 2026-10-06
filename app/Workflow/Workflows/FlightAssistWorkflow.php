<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\SupportFlightAssists\SupportFlightAssistResource;
use App\Models\SupportFlightAssist;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Flight assist can be paid on its own or billed with a flight booking
 * ("billed_with_main_fee"); either counts as paid. The travel date is the
 * deadline.
 */
class FlightAssistWorkflow extends SupportRequestWorkflow
{
    public function service(): string
    {
        return 'flight_assist';
    }

    public function label(): string
    {
        return 'Flight Assist';
    }

    public function subjectClass(): string
    {
        return SupportFlightAssist::class;
    }

    protected function resource(): string
    {
        return SupportFlightAssistResource::class;
    }

    public function serviceDate(Model $subject): ?CarbonInterface
    {
        return $this->parseDate($this->raw($subject, 'travel_date_oneway') ?: $this->raw($subject, 'departure_date'));
    }
}

<?php

namespace App\Workflow;

use App\Workflow\Workflows\AirCargoWorkflow;
use App\Workflow\Workflows\CarHireWorkflow;
use App\Workflow\Workflows\ExtraLuggageWorkflow;
use App\Workflow\Workflows\FlightAssistWorkflow;
use App\Workflow\Workflows\FlightWorkflow;
use App\Workflow\Workflows\InsuranceWorkflow;
use App\Workflow\Workflows\LoungeWorkflow;
use App\Workflow\Workflows\ProtocolWorkflow;
use App\Workflow\Workflows\TransferWorkflow;
use App\Workflow\Workflows\VisaConfirmationWorkflow;
use App\Workflow\Workflows\VisaWorkflow;
use App\Workflow\Workflows\YellowCardWorkflow;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Every service that has a workflow, in the order My Work lists them.
 *
 * TravelFlex has none of its own on purpose: its review and deposit are
 * stages of the flight booking it finances, so one customer journey is one
 * work item.
 */
class WorkflowRegistry
{
    /** @var list<class-string<Workflow>> */
    private const WORKFLOWS = [
        FlightWorkflow::class,
        VisaWorkflow::class,
        CarHireWorkflow::class,
        TransferWorkflow::class,
        LoungeWorkflow::class,
        ProtocolWorkflow::class,
        AirCargoWorkflow::class,
        YellowCardWorkflow::class,
        ExtraLuggageWorkflow::class,
        FlightAssistWorkflow::class,
        VisaConfirmationWorkflow::class,
        InsuranceWorkflow::class,
    ];

    /** @var array<string, Workflow>|null */
    private ?array $byService = null;

    /** @return array<string, Workflow> */
    public function all(): array
    {
        if ($this->byService === null) {
            $this->byService = [];
            foreach (self::WORKFLOWS as $class) {
                $workflow = app($class);
                $this->byService[$workflow->service()] = $workflow;
            }
        }

        return $this->byService;
    }

    public function forService(string $service): Workflow
    {
        return $this->all()[$service] ?? throw new InvalidArgumentException("No workflow for service [{$service}].");
    }

    public function forSubject(Model $subject): ?Workflow
    {
        foreach ($this->all() as $workflow) {
            if ($subject instanceof ($workflow->subjectClass())) {
                return $workflow;
            }
        }

        return null;
    }
}

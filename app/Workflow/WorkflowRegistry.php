<?php

namespace App\Workflow;

use App\Workflow\Workflows\FlightWorkflow;
use App\Workflow\Workflows\VisaWorkflow;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Every service that has a workflow. Phase 4 adds the rest here.
 */
class WorkflowRegistry
{
    /** @var list<class-string<Workflow>> */
    private const WORKFLOWS = [
        FlightWorkflow::class,
        VisaWorkflow::class,
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

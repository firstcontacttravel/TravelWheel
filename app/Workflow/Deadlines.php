<?php

namespace App\Workflow;

use App\Models\AppSetting;
use App\Models\WorkItem;
use Carbon\CarbonInterface;

/**
 * How long each step may take, set by the CEO on Workflow Settings and kept
 * in AppSetting so a change needs no deploy.
 *
 * A step's due time is the earlier of two things: when its time allowance
 * runs out, counted from when the booking entered the step, and any hard
 * deadline the booking itself carries (an airline's ticketing limit, a
 * pickup time). Only steps where staff must act ("open") have allowances;
 * waiting on a customer or a supplier is not on the clock.
 */
class Deadlines
{
    public const SETTING = 'workflow.deadlines';

    /**
     * Starting allowances in minutes, used until the CEO changes them.
     * Chosen to be achievable: a deadline that is always missed is a
     * deadline everyone learns to ignore.
     */
    public const DEFAULT_STEPS = [
        'flights' => [
            'awaiting_transfer' => 120,
            'travelflex_review' => 240,
            'hold_expired' => 60,
            'ready_to_ticket' => 60,
            'ticketing_failed' => 30,
        ],
        'visas' => [
            'submitted' => 1440,
            'under_review' => 2880,
            'approved' => 240,
        ],
        'car_hire' => ['new' => 720],
        'transfers' => ['new' => 720],
        'lounge' => ['new' => 240],
        'protocol' => ['new' => 720],
        'air_cargo' => ['new' => 1440, 'received' => 1440],
        'yellow_card' => ['new' => 1440, 'in_progress' => 2880],
        'extra_luggage' => ['new' => 1440, 'in_progress' => 2880],
        'flight_assist' => ['new' => 720, 'in_progress' => 1440],
        'visa_confirmation' => ['new' => 1440, 'in_progress' => 2880],
        'insurance' => ['issue_failed' => 240],
    ];

    public const DEFAULT_WARN_PERCENT = 75;

    public const DEFAULT_CEO_AFTER_MINUTES = 120;

    public function __construct(private readonly WorkflowRegistry $registry) {}

    /**
     * @return array{steps: array<string, array<string, int|null>>, warn_percent: int, ceo_after_minutes: int}
     */
    public function settings(): array
    {
        $saved = json_decode((string) AppSetting::get(self::SETTING, ''), true) ?: [];

        $steps = self::DEFAULT_STEPS;
        foreach ((array) ($saved['steps'] ?? []) as $service => $stages) {
            foreach ((array) $stages as $stage => $minutes) {
                $steps[$service][$stage] = $minutes === null ? null : (int) $minutes;
            }
        }

        return [
            'steps' => $steps,
            'warn_percent' => (int) ($saved['warn_percent'] ?? self::DEFAULT_WARN_PERCENT),
            'ceo_after_minutes' => (int) ($saved['ceo_after_minutes'] ?? self::DEFAULT_CEO_AFTER_MINUTES),
        ];
    }

    /** @param array{steps: array<string, array<string, int|null>>, warn_percent: int, ceo_after_minutes: int} $settings */
    public function save(array $settings): void
    {
        AppSetting::set(self::SETTING, json_encode($settings));
    }

    /** Minutes allowed for this step, or null when it has no deadline. */
    public function allowance(string $service, string $stage): ?int
    {
        $minutes = $this->settings()['steps'][$service][$stage] ?? null;

        return $minutes && $minutes > 0 ? $minutes : null;
    }

    /** The earlier of the step's allowance and the booking's own deadline. */
    public function dueAt(WorkItem $item, ?CarbonInterface $bookingDeadline): ?CarbonInterface
    {
        $definition = $this->registry->forService($item->service)->stages()[$item->stage] ?? null;
        if (($definition['state'] ?? null) !== WorkItem::STATE_OPEN) {
            return $bookingDeadline;
        }

        $minutes = $this->allowance($item->service, $item->stage);
        $allowance = $minutes ? ($item->stage_entered_at ?? now())->copy()->addMinutes($minutes) : null;

        return match (true) {
            $allowance === null => $bookingDeadline,
            $bookingDeadline === null => $allowance,
            default => $allowance->lessThan($bookingDeadline) ? $allowance : $bookingDeadline,
        };
    }
}

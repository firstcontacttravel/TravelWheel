<?php

namespace App\Console\Commands;

use App\Workflow\WorkflowRegistry;
use App\Workflow\WorkItemService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Creates the work item for every booking that existed before workflows did,
 * and re-syncs any that drifted. Bookings saved from now on sync themselves,
 * so this is a one-off after deploying, safe to repeat.
 */
class BackfillWorkItems extends Command
{
    protected $signature = 'workflow:backfill {--service= : Only this service (flights, visas)}';

    protected $description = 'Create or re-sync work items for existing bookings and applications';

    public function handle(WorkflowRegistry $registry, WorkItemService $items): int
    {
        $workflows = $this->option('service')
            ? [$registry->forService((string) $this->option('service'))]
            : $registry->all();

        foreach ($workflows as $workflow) {
            $synced = 0;
            $failed = 0;

            try {
                $workflow->backfillQuery()->chunkById(200, function ($subjects) use ($items, &$synced, &$failed): void {
                    foreach ($subjects as $subject) {
                        try {
                            $items->sync($subject);
                            $synced++;
                        } catch (Throwable $e) {
                            $failed++;
                            report($e);
                        }
                    }
                });
            } catch (Throwable $e) {
                // One service's table being unreadable must not stop the rest.
                report($e);
                $this->error("{$workflow->label()}: skipped, {$e->getMessage()}");

                continue;
            }

            $this->info("{$workflow->label()}: {$synced} synced".($failed ? ", {$failed} failed (see the log)" : '').'.');
        }

        return self::SUCCESS;
    }
}

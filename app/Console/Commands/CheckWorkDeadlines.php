<?php

namespace App\Console\Commands;

use App\Workflow\DeadlineMonitor;
use Illuminate\Console\Command;

/**
 * Sends deadline alerts: due soon, missed, and still missed (to the CEO).
 * Scheduled every five minutes; each alert goes out once per step.
 */
class CheckWorkDeadlines extends Command
{
    protected $signature = 'workflow:check-deadlines';

    protected $description = 'Warn about work nearing or past its deadline, and tell the CEO about work long overdue';

    public function handle(DeadlineMonitor $monitor): int
    {
        $counts = $monitor->run();

        $this->info("Due soon: {$counts['warned']}. Newly overdue: {$counts['missed']}. Sent to the CEO: {$counts['ceo']}.");

        return self::SUCCESS;
    }
}

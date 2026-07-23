<?php

namespace App\Console\Commands;

use App\Services\Reporting\ReportingSynchronizer;
use Illuminate\Console\Command;

class SyncReportingFacts extends Command
{
    protected $signature = 'reports:sync';
    protected $description = 'Synchronize all TravelWheel products into the normalized reporting fact table';

    public function handle(ReportingSynchronizer $synchronizer): int
    {
        $run = $synchronizer->sync();
        $this->info("Reporting sync {$run->status}: {$run->row_count} rows.");

        if ($run->errors) {
            foreach ($run->errors as $source => $error) {
                $this->warn("{$source}: {$error}");
            }
        }

        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LoungePairCatalogueSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncLoungePairCatalogue extends Command
{
    protected $signature = 'loungepair:sync {iata : Three-letter IATA code, e.g. SYD}';

    protected $description = 'Sync LoungePair lounges into the local lounges catalogue';

    public function handle(LoungePairCatalogueSyncService $sync): int
    {
        try {
            $result = $sync->sync($this->argument('iata'));
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("LoungePair catalogue synced: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped.");

        return self::SUCCESS;
    }
}

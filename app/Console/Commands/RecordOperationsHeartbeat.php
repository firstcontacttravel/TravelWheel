<?php

namespace App\Console\Commands;

use App\Models\SystemHeartbeat;
use Illuminate\Console\Command;

class RecordOperationsHeartbeat extends Command
{
    protected $signature = 'operations:heartbeat {name=scheduler}';

    protected $description = 'Record that a supervised TravelWheel operations process is alive';

    public function handle(): int
    {
        SystemHeartbeat::query()->updateOrCreate(
            ['name' => (string) $this->argument('name')],
            [
                'last_seen_at' => now(),
                'metadata' => [
                    'environment' => app()->environment(),
                    'host' => gethostname() ?: null,
                ],
            ],
        );

        return self::SUCCESS;
    }
}

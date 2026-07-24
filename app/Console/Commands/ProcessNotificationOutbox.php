<?php

namespace App\Console\Commands;

use App\Services\DurableMailService;
use Illuminate\Console\Command;

class ProcessNotificationOutbox extends Command
{
    protected $signature = 'notifications:process-outbox {--limit=100}';

    protected $description = 'Retry durable emails that were not delivered immediately';

    public function handle(DurableMailService $mail): int
    {
        $result = $mail->processPending(max(1, (int) $this->option('limit')));

        $this->info("Durable email outbox: {$result['sent']} sent, {$result['failed']} still pending.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

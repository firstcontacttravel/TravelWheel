<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Linear\LinearClient;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Throwable;

/**
 * Checks the Linear connection and how close the workspace is to the free
 * plan's cap on active issues. Run by hand to test a new API key; scheduled
 * daily with --warn, which tells the CEO in the admin once the count passes
 * the threshold.
 *
 * Escalations archive their issue when they close, so in steady state the
 * count only holds what is genuinely open.
 */
class LinearStatus extends Command
{
    protected $signature = 'linear:status {--warn : Notify the admin if active issues are near the free plan cap}';

    protected $description = 'Check the Linear connection and active issue count';

    public function handle(LinearClient $linear): int
    {
        if (! $linear->isConfigured()) {
            $this->warn('LINEAR_API_KEY is not set; escalations are not sent to Linear.');

            return self::SUCCESS;
        }

        try {
            $me = $linear->whoAmI();
            $usage = $linear->activeIssueCount();
            $teams = collect(config('services.linear.teams'))->map(fn (string $key) => $linear->team($key)['name'].' ('.$key.')');
        } catch (Throwable $e) {
            $this->error('Linear is not reachable: '.$e->getMessage());

            return self::FAILURE;
        }

        $count = $usage['count'].($usage['more'] ? '+' : '');
        $this->info("Connected to {$me['organization']} as {$me['name']}.");
        $this->line('Teams: '.$teams->implode(', '));
        $this->line("Active issues: {$count}");

        $threshold = (int) config('services.linear.issue_warning_threshold');
        if ($this->option('warn') && ($usage['more'] || $usage['count'] >= $threshold)) {
            Notification::make()
                ->title("Linear has {$count} active issues")
                ->body("The free plan caps active issues. Archive finished issues in Linear, or new escalations may fail to reach it. (Warning starts at {$threshold}.)")
                ->warning()
                ->sendToDatabase(User::query()->where('is_admin', true)->whereNull('deactivated_at')->get());

            $this->warn('Admin notified.');
        }

        return self::SUCCESS;
    }
}

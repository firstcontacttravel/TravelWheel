<?php

namespace App\Services;

use App\Models\SystemHeartbeat;
use App\Models\SystemHealthRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemHealthService
{
    private bool $production;

    public function run(bool $includeConnectivity = true): array
    {
        $startedAt = microtime(true);
        $this->production = app()->environment('production');

        $checks = [
            $this->check('application', 'Application', 'Deployment configuration', fn () => $this->deploymentConfiguration()),
            $this->check('runtime', 'Application', 'PHP runtime and extensions', fn () => $this->runtime()),
            $this->check('web_surface', 'Application', 'Public routes and frontend assets', fn () => $this->webSurface()),
            $this->check('database', 'Data layer', 'Database connection', fn () => $this->databaseConnection()),
            $this->check('migrations', 'Data layer', 'Database migrations', fn () => $this->migrations()),
            $this->check('product_modules', 'Products', 'Product module tables', fn () => $this->productModules()),
            $this->check('pricing', 'Products', 'Exchange rates and flight charges', fn () => $this->pricing()),
            $this->check('privacy', 'Security', 'Sensitive data protection', fn () => $this->privacy()),
            $this->check('cache', 'Infrastructure', 'Cache and locks', fn () => $this->cache()),
            $this->check('storage', 'Infrastructure', 'Storage and critical files', fn () => $this->storage()),
            $this->check('scheduler', 'Background work', 'Scheduler heartbeat', fn () => $this->scheduler()),
            $this->check('queue', 'Background work', 'Queue backlog and failures', fn () => $this->queue()),
            $this->check('email', 'Background work', 'Email delivery', fn () => $this->email($includeConnectivity)),
            $this->check('flight_supplier', 'Integrations', 'Flight supplier', fn () => $this->flightSupplier($includeConnectivity)),
            $this->check('seerbit', 'Integrations', 'SeerBit payments', fn () => $this->seerbit($includeConnectivity)),
            $this->check('flight_operations', 'Operations', 'Flight payment and ticketing exceptions', fn () => $this->flightOperations()),
            $this->check('reporting', 'Operations', 'Reporting synchronization', fn () => $this->reporting()),
        ];

        $counts = collect($checks)->countBy('status');
        $failed = (int) ($counts['failed'] ?? 0);
        $warnings = (int) ($counts['warning'] ?? 0);
        $healthy = (int) ($counts['healthy'] ?? 0);
        $overall = $failed > 0 ? 'failed' : ($warnings > 0 ? 'warning' : 'healthy');

        return [
            'overall_status' => $overall,
            'healthy_count' => $healthy,
            'warning_count' => $warnings,
            'failed_count' => $failed,
            'total_count' => count($checks),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
            'groups' => collect($checks)->groupBy('group')->map->values()->all(),
            'context' => [
                'environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'connectivity_included' => $includeConnectivity,
            ],
        ];
    }

    public function runAndStore(?User $user = null, bool $includeConnectivity = true): array
    {
        $report = $this->run($includeConnectivity);

        $run = SystemHealthRun::query()->create([
            'user_id' => $user?->id,
            'overall_status' => $report['overall_status'],
            'healthy_count' => $report['healthy_count'],
            'warning_count' => $report['warning_count'],
            'failed_count' => $report['failed_count'],
            'duration_ms' => $report['duration_ms'],
            'results' => $report['checks'],
            'context' => $report['context'],
        ]);

        $report['run_id'] = $run->id;

        return $report;
    }

    private function check(string $id, string $group, string $name, callable $callback): array
    {
        $startedAt = microtime(true);

        try {
            $result = $callback();
            $status = in_array($result['status'] ?? null, ['healthy', 'warning', 'failed'], true)
                ? $result['status']
                : 'failed';

            return [
                'id' => $id,
                'group' => $group,
                'name' => $name,
                'status' => $status,
                'summary' => (string) ($result['summary'] ?? 'No summary was provided.'),
                'details' => (array) ($result['details'] ?? []),
                'action' => $result['action'] ?? null,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        } catch (\Throwable $exception) {
            Log::warning('System health check could not complete.', [
                'check' => $id,
                'error_type' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'id' => $id,
                'group' => $group,
                'name' => $name,
                'status' => 'failed',
                'summary' => 'The check could not be completed.',
                'details' => ['Error type' => class_basename($exception)],
                'action' => 'Review the application log for the full diagnostic error.',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        }
    }

    private function deploymentConfiguration(): array
    {
        $issues = [];
        $warnings = [];
        $url = (string) config('app.url');
        $https = parse_url($url, PHP_URL_SCHEME) === 'https';

        if ($this->production) {
            if (config('app.debug')) {
                $issues[] = 'Debug mode is enabled.';
            }
            if (! $https) {
                $issues[] = 'The application URL is not HTTPS.';
            }
            if (! app()->configurationIsCached()) {
                $issues[] = 'Laravel configuration is not cached.';
            }
            if (config('session.secure') !== true) {
                $issues[] = 'Secure session cookies are disabled.';
            }
            if (config('session.encrypt') !== true) {
                $issues[] = 'Session encryption is disabled.';
            }
        } else {
            $warnings[] = 'Production-only safeguards are not enforced in the current environment.';
        }

        if (! filled(config('app.key'))) {
            $issues[] = 'The application encryption key is missing.';
        }

        return [
            'status' => $issues !== [] ? 'failed' : ($warnings !== [] ? 'warning' : 'healthy'),
            'summary' => $issues !== []
                ? count($issues).' deployment configuration issue(s) need attention.'
                : ($warnings !== [] ? 'Development environment detected.' : 'Production safeguards are configured.'),
            'details' => [
                'Environment' => app()->environment(),
                'Debug mode' => config('app.debug') ? 'Enabled' : 'Disabled',
                'HTTPS URL' => $https ? 'Yes' : 'No',
                'Configuration cached' => app()->configurationIsCached() ? 'Yes' : 'No',
                'Maintenance mode' => app()->isDownForMaintenance() ? 'Enabled' : 'Disabled',
                'Issues' => $issues,
                'Notes' => $warnings,
            ],
            'action' => $issues !== [] ? 'Correct the failed production settings and rebuild the configuration cache.' : null,
        ];
    }

    private function runtime(): array
    {
        $required = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];
        $missing = collect($required)->reject(fn (string $extension): bool => extension_loaded($extension))->values()->all();
        $warnings = [];

        if (! extension_loaded('gd')) {
            $warnings[] = 'GD is unavailable; some remote airline logos may be omitted from PDFs.';
        }

        $supported = version_compare(PHP_VERSION, '8.2.0', '>=');

        return [
            'status' => (! $supported || $missing !== []) ? 'failed' : ($warnings !== [] ? 'warning' : 'healthy'),
            'summary' => (! $supported || $missing !== [])
                ? 'The PHP runtime is missing a required capability.'
                : ($warnings !== [] ? 'The required runtime is available with one optional limitation.' : 'PHP and required extensions are available.'),
            'details' => [
                'PHP version' => PHP_VERSION,
                'Required version' => '8.2 or newer',
                'Missing extensions' => $missing,
                'Optional notes' => $warnings,
            ],
            'action' => (! $supported || $missing !== []) ? 'Update PHP or enable the missing extensions in the server control panel.' : null,
        ];
    }

    private function databaseConnection(): array
    {
        $startedAt = microtime(true);
        DB::connection()->getPdo();
        DB::select('SELECT 1');
        $latency = (int) round((microtime(true) - $startedAt) * 1000);

        return [
            'status' => $latency > 1000 ? 'warning' : 'healthy',
            'summary' => $latency > 1000 ? 'The database responded slowly.' : 'The database accepted a query.',
            'details' => [
                'Connection' => (string) config('database.default'),
                'Response time' => $latency.' ms',
            ],
            'action' => $latency > 1000 ? 'Check database load, network latency, and slow queries.' : null,
        ];
    }

    private function webSurface(): array
    {
        $routes = [
            'Flight search' => 'air.flight-s',
            'Flight confirmation' => 'flights.confirmation',
            'TravelFlex confirmation' => 'flights.travelflex.confirmation',
            'Visa discovery' => 'air.visa',
        ];
        $details = [];
        $issues = [];

        foreach ($routes as $label => $route) {
            $exists = Route::has($route);
            $details[$label] = $exists ? 'Registered' : "Missing route: {$route}";
            if (! $exists) {
                $issues[] = $label;
            }
        }

        $manifestPath = public_path('build/manifest.json');
        $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
        $assetsReady = is_array($manifest) && $manifest !== [];
        $details['Production asset manifest'] = $assetsReady ? count($manifest).' entries' : 'Missing or invalid';
        if (! $assetsReady) {
            $issues[] = 'Production frontend assets';
        }

        return [
            'status' => $issues === [] ? 'healthy' : 'failed',
            'summary' => $issues === [] ? 'Critical customer routes and compiled frontend assets are available.' : count($issues).' customer-facing web requirement(s) failed.',
            'details' => $details,
            'action' => $issues !== [] ? 'Restore the missing route or run npm run build before accepting traffic.' : null,
        ];
    }

    private function migrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = $migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        return [
            'status' => $pending === [] ? 'healthy' : 'failed',
            'summary' => $pending === [] ? 'All database migrations have run.' : count($pending).' migration(s) are pending.',
            'details' => [
                'Applied migrations' => count($ran),
                'Pending migrations' => $pending,
            ],
            'action' => $pending !== [] ? 'Deploy the pending migrations with php artisan migrate --force.' : null,
        ];
    }

    private function productModules(): array
    {
        $modules = [
            'Flights' => 'flight_bookings',
            'TravelFlex' => 'travel_flex_applications',
            'Visa' => 'visa_applications',
            'Lounge' => 'lounge_service',
            'Protocol' => 'protocol_bookings',
            'Cargo' => 'aircargo',
            'Insurance' => 'insurance_purchases',
            'Car hire' => 'car_hires',
            'Transfers' => 'transfers',
            'Support requests' => 'support_flight_assists',
            'Reporting' => 'reporting_facts',
        ];
        $details = [];
        $missing = [];

        foreach ($modules as $module => $table) {
            $exists = Schema::hasTable($table);
            $details[$module] = $exists ? 'Available' : "Missing table: {$table}";
            if (! $exists) {
                $missing[] = $module;
            }
        }

        return [
            'status' => $missing === [] ? 'healthy' : 'failed',
            'summary' => $missing === [] ? 'All product modules have their required primary table.' : count($missing).' product module(s) are unavailable.',
            'details' => $details,
            'action' => $missing !== [] ? 'Restore the missing product tables or run their migrations before accepting traffic.' : null,
        ];
    }

    private function pricing(): array
    {
        $issues = [];
        $rates = [];

        if (! Schema::hasTable('exchange_rates')) {
            $issues[] = 'The shared exchange rate table is missing.';
        } else {
            foreach (['USD', 'GBP', 'EUR'] as $currency) {
                $rate = (float) DB::table('exchange_rates')->where('currency', $currency)->value('rate');
                $rates[$currency.'/NGN'] = $rate > 0 ? number_format($rate, 4) : 'Missing';
                if ($rate <= 0) {
                    $issues[] = "{$currency}/NGN does not have a positive rate.";
                }
            }
        }

        $chargeCount = Schema::hasTable('flight_service_charges')
            ? DB::table('flight_service_charges')->where('amount', '>=', 0)->count()
            : 0;
        if ($chargeCount !== 12) {
            $issues[] = "Expected 12 flight service charge combinations; found {$chargeCount}.";
        }

        return [
            'status' => $issues === [] ? 'healthy' : 'failed',
            'summary' => $issues === [] ? 'Shared exchange rates and all flight charges are configured.' : count($issues).' pricing configuration issue(s) found.',
            'details' => [
                'Exchange rates' => $rates,
                'Flight charge combinations' => "{$chargeCount} of 12",
                'Issues' => $issues,
            ],
            'action' => $issues !== [] ? 'Update Exchange Rates or Flight Service Charges in the Operations menu.' : null,
        ];
    }

    private function privacy(): array
    {
        $issues = [];

        if (Schema::hasTable('flight_bookings')) {
            $plaintextPassengers = DB::table('flight_bookings')
                ->whereNotNull('passengers_snapshot')
                ->where('passengers_snapshot', 'not like', '%__travelwheel_encrypted%')
                ->exists();
            if ($plaintextPassengers) {
                $issues[] = 'Some flight passenger snapshots are still stored in plaintext.';
            }
        }

        if (Schema::hasTable('travel_flex_applications')) {
            $fields = [
                'applicant_details', 'bvn_metadata', 'identity_details', 'employment_details',
                'bank_details', 'next_of_kin_details', 'company_details', 'representative_details',
                'document_paths', 'agreement_acceptance', 'repayment_plan',
            ];
            $plaintextApplications = DB::table('travel_flex_applications')
                ->where(function ($query) use ($fields): void {
                    foreach ($fields as $field) {
                        if (Schema::hasColumn('travel_flex_applications', $field)) {
                            $query->orWhere(function ($fieldQuery) use ($field): void {
                                $fieldQuery->whereNotNull($field)->where($field, 'not like', '%__travelwheel_encrypted%');
                            });
                        }
                    }
                })
                ->exists();
            if ($plaintextApplications) {
                $issues[] = 'Some TravelFlex application fields are still stored in plaintext.';
            }
        }

        return [
            'status' => $issues === [] ? 'healthy' : 'failed',
            'summary' => $issues === [] ? 'Sensitive flight and TravelFlex snapshots are encrypted.' : 'Unencrypted sensitive records were detected.',
            'details' => ['Issues' => $issues],
            'action' => $issues !== [] ? 'Run php artisan privacy:encrypt-sensitive-data and verify the result.' : null,
        ];
    }

    private function cache(): array
    {
        $driver = (string) config('cache.default');
        $key = 'system-health:'.Str::uuid();
        $value = Str::random(24);
        $startedAt = microtime(true);

        try {
            Cache::put($key, $value, now()->addMinute());
            $roundTrip = Cache::get($key) === $value;
        } finally {
            Cache::forget($key);
        }

        $latency = (int) round((microtime(true) - $startedAt) * 1000);
        $persistent = ! in_array($driver, ['array', 'null'], true);
        $status = ! $roundTrip ? 'failed' : ($persistent ? 'healthy' : 'warning');

        return [
            'status' => $status,
            'summary' => ! $roundTrip
                ? 'The cache write/read probe failed.'
                : ($persistent ? 'Cache reads, writes, and distributed locks are available.' : 'The cache works but is not persistent.'),
            'details' => [
                'Store' => $driver,
                'Round-trip' => $roundTrip ? 'Passed' : 'Failed',
                'Response time' => $latency.' ms',
            ],
            'action' => ! $persistent ? 'Use a database or Redis cache store for production locks and deduplication.' : null,
        ];
    }

    private function storage(): array
    {
        $targets = [
            'Framework storage' => storage_path('framework'),
            'Application logs' => storage_path('logs'),
            'Public storage' => storage_path('app/public'),
        ];
        $details = [];
        $issues = [];

        foreach ($targets as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $details[$label] = $writable ? 'Writable' : 'Missing or not writable';
            if (! $writable) {
                $issues[] = $label;
            }
        }

        $formPath = public_path('assets/fast_creadit.pdf');
        $validPdf = is_file($formPath) && is_readable($formPath) && $this->startsWithPdfSignature($formPath);
        $details['Fast Credit PDF'] = $validPdf ? 'Available and readable' : 'Missing or invalid';
        if (! $validPdf) {
            $issues[] = 'Fast Credit PDF';
        }

        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());
        if (is_float($free) || is_int($free)) {
            $details['Free disk space'] = $this->bytes((float) $free);
            if ($free < 250 * 1024 * 1024) {
                $issues[] = 'Critically low disk space';
            }
        }
        if ((is_float($total) || is_int($total)) && $total > 0 && $free !== false) {
            $details['Disk free percentage'] = number_format(((float) $free / (float) $total) * 100, 1).'%';
        }

        return [
            'status' => $issues === [] ? 'healthy' : 'failed',
            'summary' => $issues === [] ? 'Required storage locations and PDF assets are available.' : count($issues).' storage requirement(s) failed.',
            'details' => $details,
            'action' => $issues !== [] ? 'Restore the missing asset or correct server directory permissions and disk capacity.' : null,
        ];
    }

    private function scheduler(): array
    {
        if (! Schema::hasTable('system_heartbeats')) {
            return [
                'status' => 'failed',
                'summary' => 'The heartbeat table is missing.',
                'action' => 'Run the outstanding database migrations.',
            ];
        }

        $heartbeat = SystemHeartbeat::query()->where('name', 'scheduler')->first()?->last_seen_at;
        $age = $heartbeat ? (int) $heartbeat->diffInMinutes(now()) : null;
        $healthy = $age !== null && $age <= 3;
        $status = $healthy ? 'healthy' : ($this->production ? 'failed' : 'warning');

        return [
            'status' => $status,
            'summary' => $healthy ? 'The scheduler checked in recently.' : 'The scheduler heartbeat is missing or stale.',
            'details' => [
                'Last heartbeat' => $heartbeat?->toIso8601String() ?? 'Never',
                'Age' => $age === null ? 'Unknown' : $age.' minute(s)',
                'Expected interval' => 'Every minute',
            ],
            'action' => ! $healthy ? 'Verify the server cron runs php artisan schedule:run every minute.' : null,
        ];
    }

    private function queue(): array
    {
        $driver = (string) config('queue.default');
        $details = ['Connection' => $driver];
        $warnings = [];
        $failures = [];

        if ($driver === 'sync') {
            if ($this->production) {
                $failures[] = 'The queue is using the synchronous driver.';
            } else {
                $warnings[] = 'The queue is using the synchronous driver.';
            }
        }

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            $oldest = DB::table('jobs')->min('created_at');
            $age = $oldest ? (int) floor((now()->timestamp - (int) $oldest) / 60) : 0;
            $details['Pending jobs'] = $pending;
            $details['Oldest pending job'] = $oldest ? $age.' minute(s)' : 'None';

            if ($pending > 0 && $age > 30) {
                $failures[] = 'The oldest queue job has waited more than 30 minutes.';
            } elseif ($pending > 0 && $age > 5) {
                $warnings[] = 'The queue has jobs waiting more than five minutes.';
            }
        } else {
            $failures[] = 'The jobs table is missing.';
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')->count();
            $details['Failed jobs'] = $failedJobs;
            if ($failedJobs > 0) {
                $warnings[] = "{$failedJobs} failed queue job(s) need review.";
            }
        } else {
            $failures[] = 'The failed_jobs table is missing.';
        }

        if (Schema::hasTable('notification_outboxes')) {
            $pendingMail = DB::table('notification_outboxes')->whereNull('sent_at')->count();
            $failedMail = DB::table('notification_outboxes')->whereNotNull('failed_at')->whereNull('sent_at')->count();
            $oldestMail = DB::table('notification_outboxes')->whereNull('sent_at')->min('created_at');
            $mailAge = $oldestMail ? now()->diffInMinutes($oldestMail) : 0;
            $details['Pending email outbox'] = $pendingMail;
            $details['Failed email outbox'] = $failedMail;
            $details['Oldest pending email'] = $oldestMail ? $mailAge.' minute(s)' : 'None';

            if ($failedMail > 0 || ($pendingMail > 0 && $mailAge > 30)) {
                $failures[] = 'Email delivery has failed or has been delayed for more than 30 minutes.';
            } elseif ($pendingMail > 0 && $mailAge > 5) {
                $warnings[] = 'Some emails have been waiting more than five minutes.';
            }
        }

        return [
            'status' => $failures !== [] ? 'failed' : ($warnings !== [] ? 'warning' : 'healthy'),
            'summary' => $failures !== []
                ? count($failures).' background processing failure(s) found.'
                : ($warnings !== [] ? count($warnings).' queue item(s) need attention.' : 'Queue and durable email backlogs are clear.'),
            'details' => array_merge($details, ['Failures' => $failures, 'Warnings' => $warnings]),
            'action' => ($failures !== [] || $warnings !== []) ? 'Review Email Delivery and failed jobs, then confirm the scheduled queue worker is running.' : null,
        ];
    }

    private function email(bool $includeConnectivity): array
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport", $mailer);
        $addresses = [
            'From address' => config('mail.from.address'),
            'Support address' => config('mail.support_address'),
            'TravelFlex provider' => config('mail.travelflex_provider'),
        ];
        $invalid = collect($addresses)->filter(fn ($address): bool => ! filter_var($address, FILTER_VALIDATE_EMAIL))->keys()->all();
        $issues = [];
        $warnings = [];
        $details = ['Mailer' => $mailer, 'Transport' => $transport];

        if ($invalid !== []) {
            $issues[] = 'One or more required email addresses are invalid.';
        }
        if (in_array($transport, ['array', 'log'], true)) {
            if ($this->production) {
                $issues[] = 'The configured mailer does not deliver real email.';
            } else {
                $warnings[] = 'The configured mailer does not deliver real email.';
            }
        }

        if ($includeConnectivity && $transport === 'smtp') {
            $host = (string) config("mail.mailers.{$mailer}.host");
            $port = (int) config("mail.mailers.{$mailer}.port", 587);
            $details['SMTP authentication'] = filled(config("mail.mailers.{$mailer}.username"))
                && filled(config("mail.mailers.{$mailer}.password"))
                    ? 'Configured'
                    : 'Not configured';
            if ($host === '' || $port <= 0) {
                $issues[] = 'The SMTP host or port is invalid.';
            }
            $probe = $this->socketProbe($host, $port);
            $details['SMTP connection'] = $probe['message'];
            if (! $probe['reachable']) {
                if ($this->production) {
                    $issues[] = 'The SMTP server could not be reached.';
                } else {
                    $warnings[] = 'The SMTP server could not be reached.';
                }
            }
        } elseif (! $includeConnectivity) {
            $details['Connectivity probe'] = 'Skipped';
        }

        return [
            'status' => $issues !== [] ? 'failed' : ($warnings !== [] ? 'warning' : 'healthy'),
            'summary' => $issues !== []
                ? 'Email delivery is not ready.'
                : ($warnings !== [] ? 'Email configuration needs attention.' : 'Email delivery configuration is ready.'),
            'details' => array_merge($details, [
                'Required addresses' => collect($addresses)->map(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL) ? 'Valid' : 'Invalid')->all(),
                'Issues' => $issues,
                'Warnings' => $warnings,
            ]),
            'action' => ($issues !== [] || $warnings !== []) ? 'Correct the mail transport settings and verify the server can reach the provider.' : null,
        ];
    }

    private function flightSupplier(bool $includeConnectivity): array
    {
        $required = ['user_id', 'password', 'access', 'ip'];
        $missing = collect($required)->filter(fn (string $key): bool => ! filled(config("services.travelnext.{$key}")))->values()->all();
        $details = [
            'Credentials configured' => $missing === [] ? 'Yes' : 'No',
            'Missing settings' => $missing,
        ];
        $reachable = null;

        if ($includeConnectivity && $missing === []) {
            $probe = $this->socketProbe('travelnext.works', 443, true);
            $reachable = $probe['reachable'];
            $details['Network connection'] = $probe['message'];
        } elseif (! $includeConnectivity) {
            $details['Network connection'] = 'Skipped';
        }

        $failed = $missing !== [] || $reachable === false;

        return [
            'status' => $failed ? ($this->production ? 'failed' : 'warning') : 'healthy',
            'summary' => $missing !== []
                ? 'Flight supplier credentials are incomplete.'
                : ($reachable === false ? 'The flight supplier host is unreachable.' : 'Flight supplier configuration and network path are available.'),
            'details' => $details,
            'action' => $failed ? 'Verify TravelNext credentials, outbound HTTPS access, DNS, and firewall settings.' : null,
        ];
    }

    private function seerbit(bool $includeConnectivity): array
    {
        $required = ['public_key', 'secret_key'];
        $missing = collect($required)->filter(fn (string $key): bool => ! filled(config("services.seerbit.{$key}")))->values()->all();
        $baseUrl = (string) config('services.seerbit.base_url');
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        $details = [
            'Base URL' => $host !== '' ? $host : 'Invalid',
            'Credentials configured' => $missing === [] ? 'Yes' : 'No',
            'Missing settings' => $missing,
        ];
        $reachable = null;

        if ($includeConnectivity && $missing === [] && $host !== '') {
            $probe = $this->socketProbe($host, 443, true);
            $reachable = $probe['reachable'];
            $details['Network connection'] = $probe['message'];
        } elseif (! $includeConnectivity) {
            $details['Network connection'] = 'Skipped';
        }

        $failed = $missing !== [] || $host === '' || $reachable === false;

        return [
            'status' => $failed ? ($this->production ? 'failed' : 'warning') : 'healthy',
            'summary' => $missing !== []
                ? 'SeerBit credentials are incomplete.'
                : ($reachable === false ? 'The SeerBit host is unreachable.' : 'SeerBit configuration and network path are available.'),
            'details' => $details,
            'action' => $failed ? 'Verify SeerBit keys, base URL, outbound HTTPS access, DNS, and firewall settings.' : null,
        ];
    }

    private function flightOperations(): array
    {
        if (! Schema::hasTable('flight_bookings')) {
            return [
                'status' => 'failed',
                'summary' => 'Flight booking data is unavailable.',
                'action' => 'Restore the flight_bookings table.',
            ];
        }

        $paidUnticketed = DB::table('flight_bookings')
            ->where('payment_status', 'paid')
            ->where('ticket_ordered', false)
            ->count();
        $ticketingFailed = DB::table('flight_bookings')
            ->where('payment_status', 'paid')
            ->whereIn('booking_status', ['failed', 'ticketing_failed'])
            ->count();
        $expiredHolds = DB::table('flight_bookings')
            ->whereIn('booking_status', ['on_hold', 'confirmed'])
            ->where('ticket_ordered', false)
            ->whereNotNull('tkt_time_limit')
            ->where('tkt_time_limit', '<', now())
            ->count();
        $stuckPayments = Schema::hasColumn('flight_bookings', 'payment_initializing_at')
            ? DB::table('flight_bookings')
                ->whereNotNull('payment_initializing_at')
                ->whereNull('payment_verified_at')
                ->where('payment_initializing_at', '<', now()->subMinutes(15))
                ->count()
            : 0;

        $failures = $ticketingFailed + $expiredHolds;
        $warnings = $paidUnticketed + $stuckPayments;

        return [
            'status' => $failures > 0 ? 'failed' : ($warnings > 0 ? 'warning' : 'healthy'),
            'summary' => $failures > 0
                ? 'Flight exceptions require immediate attention.'
                : ($warnings > 0 ? 'Some flight transactions require review.' : 'No unresolved flight payment or ticketing exceptions were found.'),
            'details' => [
                'Paid but not ticketed' => $paidUnticketed,
                'Paid with ticketing failure' => $ticketingFailed,
                'Expired active holds' => $expiredHolds,
                'Payment initialization older than 15 minutes' => $stuckPayments,
            ],
            'action' => ($failures + $warnings) > 0 ? 'Open Flight Bookings and resolve the affected payment, hold, or ticketing records.' : null,
        ];
    }

    private function reporting(): array
    {
        if (! Schema::hasTable('reporting_sync_runs')) {
            return [
                'status' => 'failed',
                'summary' => 'Reporting synchronization tables are missing.',
                'action' => 'Run the reporting platform migration.',
            ];
        }

        $latest = DB::table('reporting_sync_runs')->latest('id')->first();
        if (! $latest) {
            return [
                'status' => 'warning',
                'summary' => 'Reporting has not completed its first synchronization.',
                'details' => ['Last run' => 'Never'],
                'action' => 'Run php artisan reports:sync or wait for the five-minute scheduler.',
            ];
        }

        $completedAt = $latest->completed_at ? Carbon::parse($latest->completed_at) : null;
        $age = $completedAt ? (int) $completedAt->diffInMinutes(now()) : null;
        $failed = $latest->status === 'failed';
        $stale = $age === null || $age > 15;

        return [
            'status' => $failed ? 'failed' : ($stale ? 'warning' : 'healthy'),
            'summary' => $failed
                ? 'The latest reporting synchronization failed.'
                : ($stale ? 'Reporting data has not refreshed within 15 minutes.' : 'Reporting data is current.'),
            'details' => [
                'Last status' => $latest->status,
                'Rows synchronized' => (int) $latest->row_count,
                'Completed at' => $completedAt?->toIso8601String() ?? 'Not completed',
                'Age' => $age === null ? 'Unknown' : $age.' minute(s)',
            ],
            'action' => ($failed || $stale) ? 'Review the scheduler and the latest reporting sync error.' : null,
        ];
    }

    private function socketProbe(string $host, int $port, bool $tls = false): array
    {
        if ($host === '' || $port <= 0) {
            return ['reachable' => false, 'message' => 'Invalid host or port'];
        }

        $startedAt = microtime(true);
        $target = ($tls ? 'ssl://' : '').$host.':'.$port;
        $errorNumber = 0;
        $errorMessage = '';
        $socket = @stream_socket_client($target, $errorNumber, $errorMessage, 3, STREAM_CLIENT_CONNECT);
        $latency = (int) round((microtime(true) - $startedAt) * 1000);

        if (is_resource($socket)) {
            fclose($socket);

            return ['reachable' => true, 'message' => "Connected in {$latency} ms"];
        }

        return ['reachable' => false, 'message' => "Connection failed after {$latency} ms"];
    }

    private function startsWithPdfSignature(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! is_resource($handle)) {
            return false;
        }

        try {
            return fread($handle, 5) === '%PDF-';
        } finally {
            fclose($handle);
        }
    }

    private function bytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 1).' '.$units[$index];
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FlightReleaseCheck extends Command
{
    protected $signature = 'flights:release-check';

    protected $description = 'Validate production configuration required by the flight booking lifecycle';

    public function handle(): int
    {
        $errors = [];
        $warnings = [];

        $this->require($errors, app()->environment('production'), 'APP_ENV must be production.');
        $this->require($errors, ! config('app.debug'), 'APP_DEBUG must be false.');
        $this->require($errors, parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https', 'APP_URL must use HTTPS.');
        $this->require($errors, config('session.secure') === true, 'SESSION_SECURE_COOKIE must be true.');
        $this->require($errors, config('session.encrypt') === true, 'SESSION_ENCRYPT must be true.');
        $this->require($errors, app()->configurationIsCached(), 'Laravel configuration must be cached with php artisan config:cache.');

        foreach (['user_id', 'password', 'access', 'ip'] as $key) {
            $this->require($errors, filled(config("services.travelnext.{$key}")), "TRAVELNEXT_{$this->envKey($key)} is required.");
        }

        foreach (['public_key', 'secret_key'] as $key) {
            $this->require($errors, filled(config("services.seerbit.{$key}")), "SEERBIT_{$this->envKey($key)} is required.");
        }

        $this->require($errors, ! in_array(config('mail.default'), ['array', 'log'], true), 'MAIL_MAILER must deliver real email.');
        $this->require($errors, filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL), 'MAIL_FROM_ADDRESS must be valid.');
        $this->require($errors, filter_var(config('mail.support_address'), FILTER_VALIDATE_EMAIL), 'MAIL_SUPPORT_ADDRESS must be valid.');
        $this->require($errors, filter_var(config('mail.travelflex_provider'), FILTER_VALIDATE_EMAIL), 'MAIL_TRAVELFLEX_PROVIDER must be valid.');
        $this->require($errors, config('travelwheel.travelflex_bank_accounts', []) !== [], 'At least one complete TRAVELFLEX_BANK account is required.');
        $this->require($errors, config('queue.default') !== 'sync', 'QUEUE_CONNECTION must not be sync; run a persistent queue worker.');
        $this->require($errors, ! in_array(config('cache.default'), ['array', 'null'], true), 'CACHE_STORE must be persistent for locks and reminder deduplication.');
        $this->require(
            $errors,
            in_array('daily', config('logging.channels.stack.channels', []), true),
            'LOG_STACK must include daily log rotation.',
        );

        try {
            DB::connection()->getPdo();
            foreach (['flight_bookings', 'travel_flex_applications', 'exchange_rates', 'flight_service_charges', 'jobs', 'failed_jobs', 'notification_outboxes', 'system_heartbeats'] as $table) {
                $this->require($errors, Schema::hasTable($table), "Required table {$table} is missing.");
            }

            if (Schema::hasTable('exchange_rates')) {
                foreach (['USD', 'GBP', 'EUR'] as $currency) {
                    $this->require(
                        $errors,
                        DB::table('exchange_rates')->where('currency', $currency)->where('rate', '>', 0)->exists(),
                        "A positive {$currency}/NGN exchange rate is required.",
                    );
                }
            }

            if (Schema::hasTable('flight_service_charges')) {
                $this->require(
                    $errors,
                    DB::table('flight_service_charges')->where('amount', '>=', 0)->count() === 12,
                    'All 12 flight service charge combinations must be configured.',
                );
            }

            foreach (['itinerary_snapshot', 'payment_reference', 'payment_verified_at', 'ticket_ordered'] as $column) {
                $this->require($errors, Schema::hasColumn('flight_bookings', $column), "flight_bookings.{$column} is missing.");
            }

            if (Schema::hasTable('flight_bookings')) {
                $plaintextPassengers = DB::table('flight_bookings')
                    ->whereNotNull('passengers_snapshot')
                    ->where('passengers_snapshot', 'not like', '%__travelwheel_encrypted%')
                    ->exists();
                $this->require(
                    $errors,
                    ! $plaintextPassengers,
                    'Existing passenger snapshots must be encrypted with php artisan privacy:encrypt-sensitive-data.',
                );
            }

            if (Schema::hasTable('travel_flex_applications')) {
                $sensitiveFields = [
                    'applicant_details',
                    'bvn_metadata',
                    'identity_details',
                    'employment_details',
                    'bank_details',
                    'next_of_kin_details',
                    'company_details',
                    'representative_details',
                    'document_paths',
                    'agreement_acceptance',
                    'repayment_plan',
                ];
                $plaintextApplications = DB::table('travel_flex_applications')
                    ->where(function ($query) use ($sensitiveFields): void {
                        foreach ($sensitiveFields as $field) {
                            $query->orWhere(function ($fieldQuery) use ($field): void {
                                $fieldQuery->whereNotNull($field)
                                    ->where($field, 'not like', '%__travelwheel_encrypted%');
                            });
                        }
                    })
                    ->exists();
                $this->require(
                    $errors,
                    ! $plaintextApplications,
                    'Existing TravelFlex sensitive data must be encrypted with php artisan privacy:encrypt-sensitive-data.',
                );
            }

            if (Schema::hasTable('system_heartbeats')) {
                $heartbeat = DB::table('system_heartbeats')->where('name', 'scheduler')->value('last_seen_at');
                if (! $heartbeat || now()->diffInMinutes($heartbeat) > 3) {
                    $warnings[] = 'The scheduler heartbeat is missing or older than three minutes.';
                }
            }

            foreach (['financing_status', 'deposit_status', 'approval_expires_at', 'generated_application_path', 'generated_application_sha256'] as $column) {
                $this->require($errors, Schema::hasColumn('travel_flex_applications', $column), "travel_flex_applications.{$column} is missing.");
            }
        } catch (\Throwable $exception) {
            $errors[] = 'Database check failed: '.$exception->getMessage();
        }

        foreach ([storage_path('framework'), storage_path('logs')] as $path) {
            $this->require($errors, is_dir($path) && is_writable($path), "{$path} must exist and be writable.");
        }

        if (! extension_loaded('gd')) {
            $warnings[] = 'GD is not installed; some remote PNG/WebP airline logos may be omitted from PDFs.';
        }

        foreach ($warnings as $warning) {
            $this->warn('WARN: '.$warning);
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error('FAIL: '.$error);
            }

            $this->newLine();
            $this->error('Flight release check failed with '.count($errors).' blocking issue(s).');

            return self::FAILURE;
        }

        $this->info('Flight release check passed.');
        $this->line('Confirm the scheduler and queue worker are supervised before opening traffic.');

        return self::SUCCESS;
    }

    private function require(array &$errors, bool $condition, string $message): void
    {
        if (! $condition) {
            $errors[] = $message;
        }
    }

    private function envKey(string $key): string
    {
        return strtoupper($key);
    }
}

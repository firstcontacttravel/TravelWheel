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

        try {
            DB::connection()->getPdo();
            foreach (['flight_bookings', 'travel_flex_applications', 'jobs', 'failed_jobs'] as $table) {
                $this->require($errors, Schema::hasTable($table), "Required table {$table} is missing.");
            }

            foreach (['itinerary_snapshot', 'payment_reference', 'payment_verified_at', 'ticket_ordered'] as $column) {
                $this->require($errors, Schema::hasColumn('flight_bookings', $column), "flight_bookings.{$column} is missing.");
            }

            foreach (['financing_status', 'deposit_status', 'approval_expires_at'] as $column) {
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

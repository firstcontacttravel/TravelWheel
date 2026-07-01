<?php

namespace App\Console\Commands;

use App\Models\VisaProduct;
use App\Services\VisaCataloguePublicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class VisaReleaseCheck extends Command
{
    protected $signature = 'visa:release-check {--strict : Treat production credential warnings as failures}';

    protected $description = 'Validate visa schema, catalogue, storage, queues, payments, and release configuration';

    public function handle(VisaCataloguePublicationService $publication): int
    {
        $checks = [];
        $checks[] = ['Feature flag', config('visa.enabled') ? 'enabled' : 'disabled', true];
        $requiredTables = ['visa_products', 'visa_applications', 'visa_quotes', 'visa_payments', 'visa_funnel_events', 'visa_audit_events'];
        $missing = collect($requiredTables)->reject(fn ($table) => Schema::hasTable($table))->values()->all();
        $checks[] = ['Database schema', $missing ? 'missing: '.implode(', ', $missing) : 'ready', $missing === []];
        $published = VisaProduct::query()->currentlyPublished()->count();
        $checks[] = ['Published catalogue', $published.' product(s)', $published > 0];
        $invalid = VisaProduct::query()->where('publication_status', 'published')->get()->filter(fn ($product) => $publication->errors($product) !== [])->count();
        $checks[] = ['Catalogue validation', $invalid ? $invalid.' invalid published product(s)' : 'ready', $invalid === 0];
        $checks[] = ['Queue storage', config('queue.default').' / '.(Schema::hasTable('jobs') ? 'ready' : 'jobs table missing'), config('queue.default') !== 'database' || Schema::hasTable('jobs')];
        $path = 'visa-release-check/'.uniqid().'.txt';
        try {
            Storage::disk('local')->put($path, 'ok');
            $storageReady = Storage::disk('local')->exists($path);
            Storage::disk('local')->delete($path);
        } catch (\Throwable) {
            $storageReady = false;
        }
        $checks[] = ['Private storage', $storageReady ? 'writable' : 'not writable', $storageReady];
        $seerbitReady = filled(config('services.seerbit.public_key')) && filled(config('services.seerbit.secret_key'));
        $credentialRequired = app()->environment('production') || $this->option('strict');
        $checks[] = ['SeerBit credentials', $seerbitReady ? 'configured' : 'missing', $seerbitReady || ! $credentialRequired];
        $checks[] = ['Legacy import policy', config('visa.legacy_import_enabled') ? 'unsafe: enabled' : 'disabled by approved decision', ! config('visa.legacy_import_enabled')];

        $this->table(['Check', 'Result', 'Status'], collect($checks)->map(fn ($check) => [$check[0], $check[1], $check[2] ? 'PASS' : 'FAIL'])->all());

        return collect($checks)->every(fn ($check) => $check[2]) ? self::SUCCESS : self::FAILURE;
    }
}

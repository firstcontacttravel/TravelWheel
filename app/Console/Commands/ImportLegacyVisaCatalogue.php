<?php

namespace App\Console\Commands;

use App\Services\LegacyVisaCatalogueImporter;
use Illuminate\Console\Command;

class ImportLegacyVisaCatalogue extends Command
{
    protected $signature = 'visa:import-legacy-catalogue';

    protected $description = 'Import legacy standard visa and VOA configuration into the normalized visa catalogue';

    public function handle(LegacyVisaCatalogueImporter $importer): int
    {
        if (! config('visa.legacy_import_enabled')) {
            $this->error('Legacy visa import is disabled by the approved Phase 0 decision. Build and validate clean catalogue configurations instead.');

            return self::FAILURE;
        }

        $counts = $importer->import();
        $this->info("Imported {$counts['standard']} standard visa products and {$counts['voa']} VOA product.");

        return self::SUCCESS;
    }
}

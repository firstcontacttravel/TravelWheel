<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (['GBP' => 2000, 'EUR' => 1800] as $currency => $rate) {
            if (! DB::table('visa_exchange_rates')->where('source_currency', $currency)->where('target_currency', 'NGN')->exists()) {
                DB::table('visa_exchange_rates')->insert([
                    'source_currency' => $currency, 'target_currency' => 'NGN', 'rate' => $rate,
                    'source' => 'legacy_reference', 'effective_from' => $now, 'is_active' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('visa_exchange_rates')->where('source', 'legacy_reference')->whereIn('source_currency', ['GBP', 'EUR'])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table): void {
                $table->id();
                $table->char('currency', 3)->unique();
                $table->decimal('rate', 18, 6);
            });
        }

        $defaults = [
            'USD' => 1307.73,
            'GBP' => 2000,
            'EUR' => 1800,
        ];

        foreach ($defaults as $currency => $defaultRate) {
            if (DB::table('exchange_rates')->where('currency', $currency)->exists()) {
                continue;
            }

            $legacyRate = null;
            if (Schema::hasTable('visa_exchange_rates')) {
                $legacyRate = DB::table('visa_exchange_rates')
                    ->where('source_currency', $currency)
                    ->where('target_currency', 'NGN')
                    ->where('is_active', true)
                    ->where('effective_from', '<=', now())
                    ->where(function ($query): void {
                        $query->whereNull('effective_until')->orWhere('effective_until', '>=', now());
                    })
                    ->orderByDesc('effective_from')
                    ->value('rate');
            }

            DB::table('exchange_rates')->insert([
                'currency' => $currency,
                'rate' => $legacyRate ?? $defaultRate,
            ]);
        }
    }

    public function down(): void
    {
        // The consolidated rates are intentionally retained because other
        // TravelWheel products may already depend on values edited by an admin.
    }
};

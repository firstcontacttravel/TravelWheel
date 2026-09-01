<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lounges', function (Blueprint $table): void {
            $table->string('provider', 50)->nullable()->after('lounge_id');
            $table->string('provider_lounge_id', 100)->nullable()->after('provider');
            $table->string('provider_currency', 3)->nullable()->after('markup_price');
            $table->json('provider_images')->nullable()->after('provider_currency');
            $table->json('provider_payload')->nullable()->after('provider_images');
            $table->timestamp('provider_synced_at')->nullable()->after('provider_payload');
            $table->index(['provider', 'provider_lounge_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lounges', function (Blueprint $table): void {
            $table->dropIndex(['provider', 'provider_lounge_id']);
            $table->dropColumn([
                'provider',
                'provider_lounge_id',
                'provider_currency',
                'provider_images',
                'provider_payload',
                'provider_synced_at',
            ]);
        });
    }
};

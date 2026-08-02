<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_rates', function (Blueprint $table): void {
            $table->unsignedInteger('carhire_base_regular')->default(0)->after('transfer_admin_fee_percent');
            $table->unsignedInteger('carhire_base_standard')->default(0)->after('carhire_base_regular');
            $table->unsignedInteger('carhire_base_executive')->default(0)->after('carhire_base_standard');
        });

        // Car Hire and Transfer used to share one Base Fare per category.
        // Seed the new Car Hire fares from the existing (Transfer) ones so
        // pricing doesn't change until an admin deliberately splits them.
        DB::statement('
            UPDATE transport_rates
            SET carhire_base_regular = transfer_base_regular,
                carhire_base_standard = transfer_base_standard,
                carhire_base_executive = transfer_base_executive
        ');
    }

    public function down(): void
    {
        Schema::table('transport_rates', function (Blueprint $table): void {
            $table->dropColumn([
                'carhire_base_regular',
                'carhire_base_standard',
                'carhire_base_executive',
            ]);
        });
    }
};

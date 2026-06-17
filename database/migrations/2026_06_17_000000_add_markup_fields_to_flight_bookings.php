<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->decimal('supplier_price', 14, 2)->nullable()->after('currency');
            $table->decimal('markup_amount', 14, 2)->default(0)->after('supplier_price');
            $table->string('markup_category')->nullable()->after('markup_amount');
            $table->json('markup_details')->nullable()->after('markup_category');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_price',
                'markup_amount',
                'markup_category',
                'markup_details',
            ]);
        });
    }
};

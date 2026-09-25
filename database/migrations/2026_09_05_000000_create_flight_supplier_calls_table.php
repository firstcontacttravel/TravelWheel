<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_supplier_calls', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier', 30);
            $table->string('call_type', 20);
            $table->string('route', 40)->nullable();
            $table->string('cabin', 30)->nullable();
            $table->string('trip_type', 20)->nullable();
            $table->string('currency', 6)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->json('passenger_counts')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->string('search_id', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['supplier', 'call_type', 'created_at'], 'supplier_calls_supplier_type_created_idx');
            $table->index('search_id', 'supplier_calls_search_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_supplier_calls');
    }
};

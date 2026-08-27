<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aircargo', function (Blueprint $table): void {
            $table->id();
            $table->string('shipping_id')->unique();
            $table->string('fullname');
            $table->string('email');
            $table->string('phone');
            $table->string('shipping_to');
            $table->string('shipment_type');
            $table->string('shipment_preview')->nullable();
            $table->string('shipment_details')->nullable();
            $table->string('price')->nullable();
            $table->string('total_price')->nullable();
            $table->string('payment_status')->default('Pending');
            $table->string('transaction_id')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aircargo');
    }
};

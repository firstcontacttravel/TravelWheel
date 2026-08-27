<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yellow_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('service_type');
            $table->string('full_name');
            $table->string('email');
            $table->string('payment_option');
            $table->string('data_page');
            $table->string('home_address');
            $table->string('phone_number');
            $table->string('delivery_address');
            $table->string('payment_reference')->unique();
            $table->string('payment_status')->default('pending');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yellow_cards');
    }
};

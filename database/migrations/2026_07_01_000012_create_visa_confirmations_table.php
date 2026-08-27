<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('payment_option');
            $table->string('visa_file');
            $table->string('phone_number');
            $table->text('additional_info')->nullable();
            $table->string('payment_reference')->unique();
            $table->string('seerbit_ref')->nullable();
            $table->string('payment_status')->default('pending');
            $table->integer('amount')->default(50000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_confirmations');
    }
};

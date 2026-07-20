<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_extra_luggage_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('airline_category');
            $table->string('airline');
            $table->string('data_page');
            $table->string('ticket');
            $table->string('contact_number');
            $table->string('email');
            $table->string('payment_option');
            $table->string('payment_reference')->unique();
            $table->string('payment_status')->default('pending');
            $table->integer('amount')->default(25000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_extra_luggage_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voa_applications', function (Blueprint $table): void {
            $table->id();
            $table->decimal('single_entry_fee', 10, 2);
            $table->decimal('biometrics_fee', 10, 2);
            $table->decimal('service_charge', 10, 2);
            $table->decimal('payment_charge', 10, 2);
            $table->decimal('processing_charge', 10, 2);
            $table->decimal('total_fee', 10, 2);
            $table->integer('total_people');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('applicant', 250);
            $table->string('visa_to')->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Approved', 'Issued', 'Rejected'])->default('Pending');
            $table->string('visa_document_path')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voa_applications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('visa_products', function (Blueprint $table) {
            $table->foreignId('visa_vendor_id')->nullable()->after('destination_country_id')->constrained('visa_vendors')->nullOnDelete();
            $table->index(['visa_vendor_id', 'publication_status']);
        });
    }

    public function down(): void
    {
        Schema::table('visa_products', function (Blueprint $table) {
            $table->dropIndex(['visa_vendor_id', 'publication_status']);
            $table->dropConstrainedForeignId('visa_vendor_id');
        });

        Schema::dropIfExists('visa_vendors');
    }
};

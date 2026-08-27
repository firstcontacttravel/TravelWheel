<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voas', function (Blueprint $table): void {
            $table->id();
            // Indexed like a foreign key (name kept from the live table), but the
            // live database has no actual FK constraint on this column — mirrored as-is.
            $table->unsignedBigInteger('from_country_id');
            $table->index('from_country_id', 'voa_from_country_id_foreign');
            $table->decimal('visa_fee', 8, 2);
            $table->boolean('is_african_country')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voas');
    }
};

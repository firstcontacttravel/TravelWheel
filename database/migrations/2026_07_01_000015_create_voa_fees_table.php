<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voa_fees', function (Blueprint $table): void {
            $table->id();
            $table->enum('fee_type', [
                'biometrics', 'service', 'payment', 'processing', 'processing_adult', 'processing_np', 'processing_fp',
            ]);
            $table->decimal('amount_african', 8, 2);
            $table->decimal('amount_non_african', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voa_fees');
    }
};

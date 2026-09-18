<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lounge_service', function (Blueprint $table) {
            $table->renameColumn('airline', 'ticket_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lounge_service', function (Blueprint $table) {
            $table->renameColumn('ticket_no', 'airline');
        });
    }
};

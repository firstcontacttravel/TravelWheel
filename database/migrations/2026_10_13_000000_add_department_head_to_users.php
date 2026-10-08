<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Department heads, named by the CEO on each person's staff page. A
     * department can have more than one, so a deputy covers when the head is
     * away. Heads escalate, reassign and answer their department's
     * escalations; everyone else claims bookings and works the ones they own.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_department_head')->default(false)->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_department_head');
        });
    }
};

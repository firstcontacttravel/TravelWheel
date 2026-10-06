<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 0: staff belong to departments, and every action taken in
     * the admin leaves a receipt.
     *
     * The CEO stays the only is_admin user. Everyone else is staff in exactly
     * one department. linear_team says which of the two Linear teams the free
     * plan allows ("IT department" or "Travelwheel") an escalation to this
     * department lands in; the other departments are told apart by label.
     *
     * Existing staff are moved over from visa_role so nobody loses access when
     * this runs. visa_role itself is left in place and still honoured until
     * every account has a department.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('linear_team', 20)->default('travelwheel');
            $table->string('linear_label')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('visa_role')->constrained()->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable()->after('department_id');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // The department at the time, so moving someone later does not
            // rewrite who did what on behalf of which team.
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        $now = now();
        DB::table('departments')->insert([
            ['name' => 'Flights', 'slug' => 'flights', 'linear_team' => 'travelwheel', 'linear_label' => 'Flights', 'description' => 'Flight bookings, ticketing, reissues and the flight APIs.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Visas', 'slug' => 'visas', 'linear_team' => 'travelwheel', 'linear_label' => 'Visas', 'description' => 'Visa applications and visa confirmations.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Finance', 'slug' => 'finance', 'linear_team' => 'travelwheel', 'linear_label' => 'Finance', 'description' => 'Payment verification, refunds, voids and TravelFlex credit decisions.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Customer Support', 'slug' => 'customer-support', 'linear_team' => 'travelwheel', 'linear_label' => 'Customer Support', 'description' => 'Support requests, insurance and customer follow-up.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ground & Airport Services', 'slug' => 'ground-airport', 'linear_team' => 'travelwheel', 'linear_label' => 'Ground & Airport', 'description' => 'Car hire, transfers, lounges, protocol and air cargo.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'IT', 'slug' => 'it', 'linear_team' => 'it', 'linear_label' => null, 'description' => 'The website, the admin and supplier API problems.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $departmentIds = DB::table('departments')->pluck('id', 'slug');
        foreach (['visa_officer' => 'visas', 'administrator' => 'visas', 'finance' => 'finance', 'support' => 'customer-support'] as $role => $slug) {
            DB::table('users')
                ->where('visa_role', $role)
                ->whereNull('department_id')
                ->update(['department_id' => $departmentIds[$slug]]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('deactivated_at');
        });

        Schema::dropIfExists('departments');
    }
};

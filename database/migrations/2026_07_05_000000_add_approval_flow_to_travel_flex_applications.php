<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->string('financing_status')->default('pending')->after('application_status')->index();
            $table->string('deposit_status')->default('not_due')->after('payment_status')->index();
            $table->string('deposit_reference')->nullable()->after('deposit_status');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_reference');
            $table->timestamp('approval_expires_at')->nullable()->after('approved_at');
            $table->timestamp('pricing_revalidated_at')->nullable()->after('approval_expires_at');
        });

        DB::table('travel_flex_applications')->orderBy('id')->chunkById(100, function ($applications): void {
            foreach ($applications as $application) {
                DB::table('travel_flex_applications')->where('id', $application->id)->update([
                    'financing_status' => match ($application->application_status) {
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                        default => 'pending',
                    },
                    'deposit_status' => $application->payment_status === 'paid' ? 'paid' : 'not_due',
                    'deposit_paid_at' => $application->payment_status === 'paid' ? $application->updated_at : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'financing_status', 'deposit_status', 'deposit_reference', 'deposit_paid_at',
                'approval_expires_at', 'pricing_revalidated_at',
            ]);
        });
    }
};

<?php

namespace Tests\Feature;

use App\Models\ReportingExportAudit;
use App\Models\ReportingFact;
use App\Models\User;
use App\Services\Reporting\ReportingAnalytics;
use App\Services\Reporting\DatabaseReportingAdapter;
use App\Services\Reporting\UnifiedReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedAdminReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_unified_reports_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Every product. One operating picture.')
            ->assertSee('Executive overview')
            ->assertSee('Risk &amp; data quality', false);
    }

    public function test_administrator_can_open_reporting_control_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach ([
            '/admin/reporting-targets',
            '/admin/reporting-targets/create',
            '/admin/reporting-schedules',
            '/admin/reporting-schedules/create',
            '/admin/reporting-alerts',
            '/admin/reporting-export-audits',
        ] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_support_can_view_operations_but_not_export_financial_data(): void
    {
        $support = User::factory()->create(['is_admin' => false, 'visa_role' => 'support']);

        $this->actingAs($support)->get('/admin/reports')->assertOk();
        $this->actingAs($support)->get(route('admin.reports.export', ['report' => 'transactions']))->assertForbidden();
    }

    public function test_visa_officer_cannot_access_company_wide_reporting(): void
    {
        $officer = User::factory()->create(['is_admin' => false, 'visa_role' => 'visa_officer']);

        $this->actingAs($officer)->get('/admin/reports')->assertForbidden();
    }

    public function test_financial_summary_does_not_double_count_travelflex_overlay(): void
    {
        $this->fact([
            'source_type' => 'flight_booking',
            'source_id' => 1,
            'product' => 'flights',
            'gross_value' => 500000,
            'verified_collections' => 500000,
            'travelwheel_revenue' => 70000,
            'financially_additive' => true,
            'payment_status' => 'paid',
            'fulfillment_status' => 'completed',
        ]);
        $this->fact([
            'source_type' => 'travel_flex_application',
            'source_id' => 1,
            'product' => 'travel_flex',
            'gross_value' => 550000,
            'verified_collections' => 150000,
            'travelwheel_revenue' => 50000,
            'financially_additive' => false,
            'payment_status' => 'paid',
            'fulfillment_status' => 'in_progress',
        ]);

        $dashboard = app(ReportingAnalytics::class)->dashboard([
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'date_basis' => 'created',
        ]);

        $this->assertSame(500000.0, $dashboard['summary']['gross_value']);
        $this->assertSame(500000.0, $dashboard['summary']['verified_collections']);
        $this->assertSame(70000.0, $dashboard['summary']['travelwheel_revenue']);
        $this->assertSame(2, $dashboard['summary']['transactions']);
        $travelFlex = collect($dashboard['products'])->firstWhere('product', 'travel_flex');
        $this->assertSame(550000.0, $travelFlex['gross_value']);
        $this->assertSame(150000.0, $travelFlex['collections']);
        $this->assertSame(50000.0, $travelFlex['revenue']);
    }

    public function test_admin_export_is_downloaded_and_audited(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->fact([
            'source_type' => 'manual_test',
            'source_id' => 99,
            'product' => 'support',
            'gross_value' => 25000,
            'verified_collections' => 25000,
            'travelwheel_revenue' => 25000,
            'payment_status' => 'paid',
            'fulfillment_status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export', [
            'report' => 'transactions',
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'format' => 'csv',
        ]));

        $response->assertOk()->assertDownload();
        $this->assertDatabaseHas('reporting_export_audits', [
            'user_id' => $admin->id,
            'report_key' => 'transactions',
            'format' => 'csv',
            'row_count' => 1,
        ]);
        $this->assertSame(1, ReportingExportAudit::count());
    }

    public function test_excel_export_and_legacy_value_normalization_are_safe(): void
    {
        $this->fact([
            'source_type' => 'manual_test',
            'source_id' => 100,
            'product' => 'air_cargo',
            'gross_value' => 10000,
        ]);

        $xlsx = app(UnifiedReportExporter::class)->contents('transactions', 'xlsx', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]);
        $adapter = new DatabaseReportingAdapter('missing_table', 'test', fn () => []);

        $this->assertStringStartsWith('PK', $xlsx);
        $this->assertSame('paid', $adapter->payment('successful'));
        $this->assertSame('in_progress', $adapter->fulfillment('submitted'));
        $this->assertNull($adapter->number('NaN'));
    }

    private function fact(array $attributes): ReportingFact
    {
        return ReportingFact::create(array_merge([
            'source_type' => 'test',
            'source_id' => random_int(1, 100000),
            'product' => 'flights',
            'currency' => 'NGN',
            'gross_value' => 0,
            'verified_collections' => 0,
            'travelwheel_revenue' => 0,
            'supplier_cost' => null,
            'gross_profit' => null,
            'financially_additive' => true,
            'payment_status' => 'pending',
            'fulfillment_status' => 'pending',
            'quantity' => 1,
            'created_at_source' => now(),
            'data_quality' => [],
            'last_synced_at' => now(),
        ], $attributes));
    }
}

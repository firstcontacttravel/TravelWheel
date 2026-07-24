<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemHealth;
use App\Models\SystemHealthRun;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemHealthPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_service_returns_a_complete_grouped_report(): void
    {
        $report = app(SystemHealthService::class)->run(includeConnectivity: false);

        $this->assertSame(17, $report['total_count']);
        $this->assertCount(17, $report['checks']);
        $this->assertSame(
            17,
            $report['healthy_count'] + $report['warning_count'] + $report['failed_count'],
        );
        $this->assertArrayHasKey('Application', $report['groups']);
        $this->assertArrayHasKey('Products', $report['groups']);
        $this->assertArrayHasKey('Integrations', $report['groups']);
        $this->assertArrayHasKey('Operations', $report['groups']);
        $this->assertFalse($report['context']['connectivity_included']);
    }

    public function test_administrator_can_open_and_rerun_the_health_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->get('/admin/system-health')
            ->assertOk()
            ->assertSee('Live operational diagnostics')
            ->assertSee('Run checks again')
            ->assertSee('Recent health runs');

        $this->assertDatabaseCount('system_health_runs', 1);

        Livewire::test(SystemHealth::class)
            ->call('runHealthChecks')
            ->assertSee('Live operational diagnostics');

        $this->assertDatabaseCount('system_health_runs', 3);
        $this->assertSame($admin->id, SystemHealthRun::query()->latest()->value('user_id'));
    }

    public function test_non_administrator_cannot_access_system_health_details(): void
    {
        $support = User::factory()->create([
            'is_admin' => false,
            'visa_role' => 'support',
        ]);

        $this->actingAs($support)
            ->get('/admin/system-health')
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Models\Department;
use App\Models\User;
use App\Notifications\StaffLoginCode;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function staff(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'email' => 'ada@travelwheel.ng',
            'password' => 'correct-password-1',
            'department_id' => Department::where('slug', 'operations')->value('id'),
        ]);
    }

    public function test_a_password_alone_does_not_sign_staff_in(): void
    {
        Notification::fake();
        $user = $this->staff();

        $code = null;
        $page = Livewire::test(Login::class)
            ->set('data.email', 'ada@travelwheel.ng')
            ->set('data.password', 'correct-password-1')
            ->call('authenticate');

        $this->assertGuest();
        Notification::assertSentTo($user, StaffLoginCode::class, function (StaffLoginCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $page->set('data.multiFactor.email_code.code', $code === '000000' ? '111111' : '000000')
            ->call('authenticate');
        $this->assertGuest();

        $page->set('data.multiFactor.email_code.code', $code)
            ->call('authenticate');
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_login_code_is_sent_straight_away_not_queued(): void
    {
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new StaffLoginCode('123456', 5));
    }

    public function test_every_account_needs_a_code_by_default(): void
    {
        $this->assertTrue($this->staff()->fresh()->hasEmailAuthentication());
    }

    public function test_staff_can_reset_a_forgotten_password(): void
    {
        Notification::fake();
        $user = $this->staff();

        $this->get(Filament::getPanel('admin')->getRequestPasswordResetUrl())->assertOk();

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'ada@travelwheel.ng')
            ->call('request');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_ceo_by_email_uses_config_so_it_survives_a_cached_config(): void
    {
        $user = $this->staff(['department_id' => null]);
        $this->assertFalse($user->isAdmin());

        config(['travelwheel.admin_emails' => ['ada@travelwheel.ng']]);
        $this->assertTrue($user->isAdmin());
    }

    public function test_the_ceo_sees_and_removes_a_leftover_old_role(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $leftover = $this->staff(['visa_role' => 'administrator']);
        $noDepartment = User::factory()->create(['visa_role' => 'support']);

        Livewire::test(ListStaff::class)
            ->assertSee('Administrator')
            // Would lose all access, so give them a department first
            ->assertTableActionHidden('removeOldRole', $noDepartment)
            ->callTableAction('removeOldRole', $leftover);

        $this->assertNull($leftover->fresh()->visa_role);
        $this->assertSame('support', $noDepartment->fresh()->visa_role);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use App\Filament\Resources\InsurancePurchases\InsurancePurchaseResource;
use App\Filament\Resources\InsuranceQuotes\InsuranceQuoteResource;
use App\Filament\Resources\SupportFlightAssists\SupportFlightAssistResource;
use App\Filament\Resources\SupportYellowCards\SupportYellowCardResource;
use App\Filament\Resources\TravelFlexApplications\TravelFlexApplicationResource;
use App\Models\Department;
use App\Models\User;
use App\Support\Admin\DepartmentNavigation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The navigation rail by department: each member of staff sees their own
 * department's menus first, nothing is hidden, and Finance and Customer
 * Support each have one menu for their work.
 */
class DepartmentNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_has_its_own_menu_for_the_money_decisions(): void
    {
        foreach ([TravelFlexApplicationResource::class, ExchangeRateResource::class, FlightServiceChargeResource::class] as $resource) {
            $this->assertSame('Finance', $resource::getNavigationGroup(), class_basename($resource));
        }

        $this->actingAs($this->staff('finance'))->get('/admin')
            ->assertOk()
            ->assertSee('Bank transfers to confirm')
            ->assertSee('activeTab=awaiting_transfer', escape: false);
    }

    public function test_support_requests_and_insurance_are_one_customer_support_menu(): void
    {
        foreach ([SupportFlightAssistResource::class, SupportYellowCardResource::class, InsurancePurchaseResource::class, InsuranceQuoteResource::class] as $resource) {
            $this->assertSame('Customer Support', $resource::getNavigationGroup(), class_basename($resource));
        }

        $labels = $this->railGroups($this->staff('operations'));
        $this->assertNotContains('Support Requests', $labels);
        $this->assertNotContains('Insurance', $labels);
    }

    /** @return array<string, array{string, list<string>}> */
    public static function departments(): array
    {
        return [
            'finance' => ['finance', ['Finance']],
            'customer support' => ['customer-support', ['Customer Support']],
            'operations' => ['operations', ['Flights', 'Visas', 'Travel Connections', 'Airport Services', 'Air Cargo']],
            'it' => ['it', ['System']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('departments')]
    public function test_each_department_sees_its_own_menus_first_and_still_sees_everything(string $slug, array $own): void
    {
        $labels = $this->railGroups($this->staff($slug));

        $this->assertSame($own, array_slice($labels, 0, count($own)));
        $this->assertEqualsCanonicalizing($this->railGroups(User::factory()->create(['is_admin' => true])), $labels, 'Nothing is hidden.');
    }

    public function test_the_ceo_keeps_the_panels_own_order(): void
    {
        $declared = array_values(array_map(
            fn ($group) => (string) $group->getLabel(),
            Filament::getPanel('admin')->getNavigationGroups(),
        ));

        $labels = $this->railGroups(User::factory()->create(['is_admin' => true]));

        $this->assertSame(array_values(array_intersect($declared, $labels)), $labels);
        $this->assertSame([], DepartmentNavigation::groupsFor(User::factory()->create(['is_admin' => true])));
    }

    public function test_the_rail_draws_a_rule_under_your_own_department(): void
    {
        $html = $this->actingAs($this->staff('finance'))->get('/admin')->assertOk()->getContent();

        $finance = strpos($html, '<span class="tc-rail-tip">Finance</span>');
        $flights = strpos($html, '<span class="tc-rail-tip">Flights</span>');
        $this->assertNotFalse($finance);
        $this->assertLessThan($flights, $finance, 'Finance is above Flights for Finance staff.');
        $this->assertStringContainsString('tc-rail-rule', substr($html, $finance, $flights - $finance));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function staff(string $slug): User
    {
        return User::factory()->create(['department_id' => Department::query()->where('slug', $slug)->value('id')]);
    }

    /** The rail's group labels, top to bottom, as this person sees them. */
    private function railGroups(User $user): array
    {
        $html = $this->actingAs($user)->get('/admin')->assertOk()->getContent();
        preg_match_all('/<li\s+class="tc-rail-group".*?<span class="tc-rail-tip">(.*?)<\/span>/s', $html, $matches);

        return array_map(fn (string $label) => html_entity_decode($label), $matches[1]);
    }
}

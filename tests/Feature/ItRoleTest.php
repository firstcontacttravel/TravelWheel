<?php

namespace Tests\Feature;

use App\Filament\Pages\BookFlight;
use App\Filament\Pages\DefaultCorporateRates;
use App\Filament\Pages\NewBooking;
use App\Filament\Pages\Reports;
use App\Filament\Pages\SystemHealth;
use App\Filament\Pages\WorkflowSettings;
use App\Filament\Pages\Workload;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\CarHires\Pages\ViewCarHire;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CompanyWalletTopUps\CompanyWalletTopUpResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\FlightSuppliers\FlightSupplierResource;
use App\Filament\Resources\Lounges\LoungeResource;
use App\Filament\Resources\Staff\StaffResource;
use App\Filament\Resources\TravelFlexApplications\TravelFlexApplicationResource;
use App\Models\Company;
use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Models\CarHire;
use App\Models\Department;
use App\Models\User;
use App\Support\Reporting\ReportingAccess;
use App\Workflow\FulfilmentService;
use App\Workflow\WorkAccess;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** IT sees and oversees everything, but never money, prices, staff or approving a booking. */
class ItRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function staff(string $department): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function carHire(): CarHire
    {
        return CarHire::create([
            'car_type' => 'Sedan', 'category' => 'standard', 'car_model' => 'Corolla', 'full_name' => 'Test Customer',
            'email' => 'customer@example.test', 'phone_number' => '08000000000', 'passengers' => 1,
            'pickup_location' => 'Ikeja', 'dropoff_location' => 'Lekki', 'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '09:00', 'amount' => 45000, 'payment_option' => 'card', 'payment_reference' => 'CAR-'.Str::random(8),
            'payment_status' => 'paid', 'driver_assigned' => false,
        ]);
    }

    public function test_it_opens_the_system_and_oversight_screens(): void
    {
        $this->actingAs($this->staff('it'));

        foreach ([SystemHealth::getUrl(), FlightSupplierResource::getUrl(), WorkflowSettings::getUrl(), Workload::getUrl(), ActivityLogResource::getUrl(), Reports::getUrl()] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_it_sees_reports_without_the_money(): void
    {
        $it = $this->staff('it');

        $this->assertTrue(ReportingAccess::canView($it));
        $this->assertFalse(ReportingAccess::canViewFinancials($it));
    }

    public function test_it_is_kept_out_of_money_staff_companies_and_customer_bookings(): void
    {
        $it = $this->staff('it');
        $this->actingAs($it);

        $company = Company::create(['name' => 'Acme', 'cac_number' => 'RC1', 'contact_name' => 'Ada', 'contact_email' => 'ada@acme.test', 'status' => 'active']);

        foreach ([StaffResource::getUrl(), DepartmentResource::getUrl(), CompanyResource::getUrl(), CompanyResource::getUrl('view', ['record' => $company]),
            DefaultCorporateRates::getUrl(), CompanyWalletTopUpResource::getUrl(), TravelFlexApplicationResource::getUrl(),
            NewBooking::getUrl(), BookFlight::getUrl()] as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->assertFalse($it->canHandleMoney());
        $this->assertFalse($it->canBookForCustomers());

        // Vendor applications are open to staff, but not their bank details
        $this->get(VendorApplicationResource::getUrl())->assertOk();
        $this->assertFalse(VendorApplicationResource::canSeeBankDetails($it));

        // Not even IT's head may book for a company from its wallet
        $head = User::factory()->create(['department_id' => Department::where('slug', 'it')->value('id'), 'is_department_head' => true]);
        $this->assertFalse($company->canBeBookedForBy($head));
        $this->assertTrue($company->canBeBookedForBy(User::factory()->create(['department_id' => Department::where('slug', 'operations')->value('id'), 'is_department_head' => true])));
    }

    public function test_it_sees_prices_but_cannot_change_them(): void
    {
        $this->actingAs($this->staff('it'));

        $this->get(LoungeResource::getUrl())->assertOk();
        $this->assertFalse(LoungeResource::canCreate());
    }

    public function test_it_hands_bookings_on_but_never_works_one(): void
    {
        $it = $this->staff('it');
        $ada = $this->staff('operations');
        $booking = $this->carHire();
        $item = $booking->workItem()->with('owner')->first();

        $this->assertTrue(WorkAccess::canManage($it, $item));
        $this->assertFalse(WorkAccess::canClaim($it, $item));

        // Reassigns it, as a department head would
        app(WorkItemService::class)->assign($item, $ada, null, $it);
        $this->assertSame($ada->id, $booking->workItem()->first()->owner_id);

        // Even handed to IT, the booking can't be advanced, confirmed or ticketed by them
        app(WorkItemService::class)->assign($booking->workItem()->with('owner')->first(), $it, null, $it);
        $this->assertFalse(WorkAccess::canWork($it, $booking->workItem()->with('owner')->first()));

        $this->actingAs($it);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workReassign')
            ->assertActionHidden('workAdvance')
            ->assertActionHidden('assignDriver');

        $this->expectException(InvalidArgumentException::class);
        app(FulfilmentService::class)->advance($booking, 'completed', $it);
    }

    public function test_other_teams_still_cannot_open_the_system_screens(): void
    {
        $this->actingAs($this->staff('customer-support'));

        foreach ([SystemHealth::getUrl(), FlightSupplierResource::getUrl(), WorkflowSettings::getUrl(), Workload::getUrl(), ActivityLogResource::getUrl()] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
}

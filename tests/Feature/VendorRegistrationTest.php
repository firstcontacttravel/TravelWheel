<?php

namespace Tests\Feature;

use App\Livewire\Pages\Partners\VendorRegistration;
use App\Mail\VendorApplicationDecisionMail;
use App\Mail\VendorApplicationReceivedMail;
use App\Mail\VendorApplicationSubmittedMail;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VisaVendor;
use App\Services\VendorOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_form_page_loads(): void
    {
        $this->get(route('partners.register'))->assertOk()->assertSee('Vendor / Partner Registration');
    }

    public function test_a_vendor_can_complete_and_submit_the_form(): void
    {
        Mail::fake();
        Storage::fake('local');

        $component = $this->fillForm(['car_hire', 'visa']);
        $component->call('submit')->assertHasNoErrors()->assertRedirect(route('partners.register.submitted'));

        $application = VendorApplication::with('documents')->sole();
        $this->assertSame('received', $application->status);
        $this->assertSame(['car_hire', 'visa'], $application->services);
        $this->assertSame('Lagos and Abuja', $application->service_details['car_hire']['locations']);
        $this->assertSame(['Saloon', 'SUV'], $application->service_details['car_hire']['vehicle_types']);
        $this->assertSame('0123456789', $application->account_number);
        $this->assertTrue($application->works_with_other_platforms);

        // Bank details are encrypted at rest
        $this->assertStringNotContainsString('0123456789', (string) \DB::table('vendor_applications')->value('account_number'));

        // 8 required company documents + car hire's 2 required ones
        $this->assertCount(10, $application->documents);
        foreach ($application->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
        }
        $this->assertSame('2030-01-31', $application->documents->firstWhere('type', 'vehicle_insurance')->expires_on->toDateString());

        Mail::assertSent(VendorApplicationReceivedMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertSent(VendorApplicationSubmittedMail::class, fn ($mail) => $mail->hasTo(config('vendor_onboarding.notify_email')));
    }

    public function test_each_step_must_be_complete_before_moving_on(): void
    {
        Livewire::test(VendorRegistration::class)
            ->call('next')
            ->assertHasErrors(['form.registered_name' => 'required', 'form.business_email' => 'required'])
            ->assertSet('step', 1);
    }

    public function test_service_questions_and_documents_follow_the_chosen_services(): void
    {
        $component = Livewire::test(VendorRegistration::class)->set('form.services', ['visa']);

        $types = array_keys($component->instance()->documentList());
        $this->assertContains('visa_accreditation', $types);
        $this->assertNotContains('fleet_list', $types);

        // Visa's "no guarantee of approval" acknowledgement is required
        $component->set('step', 4)->call('next')->assertHasErrors(['acknowledged.visa' => 'accepted']);
    }

    public function test_tick_box_questions_start_as_lists_so_boxes_tick_one_at_a_time(): void
    {
        // Reach step 4 the way a vendor does, choosing Flights on step 3
        $component = Livewire::test(VendorRegistration::class)
            ->set('step', 3)
            ->set('form.business_types', ['travel_agency'])
            ->set('form.services', ['flights', 'car_hire'])
            ->set('form.works_with_other_platforms', 'no')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 4)
            ->assertSet('details.flights.cabins', [])
            ->assertSet('details.car_hire.vehicle_types', [])
            ->assertSet('details.car_hire.categories', []);

        // Each box has its own value in the rendered page
        foreach (['Economy', 'Premium Economy', 'Business', 'First'] as $cabin) {
            $component->assertSeeHtml('wire:model="details.flights.cabins" value="'.$cabin.'"');
        }

        $component->set('details.flights.cabins', ['Business'])->assertSet('details.flights.cabins', ['Business']);
    }

    public function test_an_oversized_or_wrong_type_file_is_refused_as_soon_as_it_is_chosen(): void
    {
        Storage::fake('local');
        $component = Livewire::test(VendorRegistration::class)->set('form.services', ['car_hire'])->set('step', 6);
        $limitKb = VendorRegistration::maxUploadKb();

        $component->set('uploads.company_profile', UploadedFile::fake()->create('profile.pdf', $limitKb + 500, 'application/pdf'))
            ->assertHasErrors(['uploads.company_profile' => 'max'])
            ->assertSee('The maximum is '.VendorRegistration::maxUploadLabel())
            ->assertSet('uploads.company_profile', null);   // dropped, so the box is ready for another file

        $component->set('uploads.terms', UploadedFile::fake()->create('terms.exe', 10))
            ->assertHasErrors(['uploads.terms' => 'mimes'])
            ->assertSee('This file type is not accepted');

        $component->set('uploads.terms', UploadedFile::fake()->create('terms.pdf', 200, 'application/pdf'))
            ->assertHasNoErrors('uploads.terms');
    }

    public function test_the_upload_limit_is_stated_and_never_above_the_servers_own_limit(): void
    {
        $this->assertLessThanOrEqual(10240, VendorRegistration::maxUploadKb());

        Livewire::test(VendorRegistration::class)->set('form.services', ['car_hire'])->set('step', 6)
            ->assertSee('up to '.VendorRegistration::maxUploadLabel().' each')
            ->assertSeeHtml('data-max-bytes="'.(VendorRegistration::maxUploadKb() * 1024).'"');
    }

    public function test_an_expired_document_is_refused(): void
    {
        Storage::fake('local');

        $component = $this->fillForm(['car_hire'], untilStep: 6);
        $component->set('expiries.vehicle_insurance', now()->subDay()->toDateString())
            ->call('next')
            ->assertHasErrors(['expiries.vehicle_insurance' => 'after']);
    }

    public function test_staff_take_an_application_through_review_to_approval(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->fillForm(['car_hire', 'visa'])->call('submit');

        $application = VendorApplication::sole();
        $staff = User::factory()->create();
        $service = app(VendorOnboardingService::class);

        // One document not in order: stays put
        $ids = $application->documents->pluck('id')->all();
        $this->assertSame(1, $service->verifyDocuments($application, array_slice($ids, 1), 'Blurry scan', $staff));
        $this->assertSame('received', $application->fresh()->status);

        $this->assertSame(0, $service->verifyDocuments($application->fresh(), $ids, null, $staff));
        $this->assertSame('documents_verified', $application->fresh()->status);

        $service->completeCompliance($application->fresh(), 'low', now()->addYear()->toDateString(), 'CAC search done', $staff);
        $this->assertSame('compliance_completed', $application->fresh()->status);

        $this->assertTrue($service->approve($application->fresh(), ['visa'], 'Welcome', null, $staff));

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertSame(['visa'], $application->approved_services);
        $this->assertSame('TWV-'.str_pad((string) $application->id, 4, '0', STR_PAD_LEFT), $application->vendor_code);
        $this->assertTrue(VisaVendor::where('email', 'bookings@example.com')->exists());
        Mail::assertSent(VendorApplicationDecisionMail::class, fn ($mail) => $mail->decision === 'approved' && $mail->hasTo('ada@example.com'));
    }

    public function test_a_step_cannot_be_skipped_or_repeated_after_a_decision(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->fillForm(['car_hire'])->call('submit');

        $application = VendorApplication::sole();
        $staff = User::factory()->create();
        $service = app(VendorOnboardingService::class);

        try {
            $service->approve($application, ['car_hire'], null, null, $staff);
            $this->fail('Approval should need compliance first.');
        } catch (\InvalidArgumentException) {
        }

        $service->reject($application, 'Fleet too old', $staff);
        $this->expectException(\InvalidArgumentException::class);
        $service->requestInformation($application->fresh(), 'Anything else?');
    }

    public function test_only_panel_staff_can_open_vendor_documents(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->fillForm(['car_hire'])->call('submit');
        $document = VendorApplication::sole()->documents()->first();

        $this->get(route('admin.vendor-documents.show', $document))->assertRedirect(\Filament\Facades\Filament::getPanel('admin')->getLoginUrl());
        $this->actingAs(User::factory()->create())->get(route('admin.vendor-documents.show', $document))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.vendor-documents.show', $document))->assertOk();
    }

    public function test_the_admin_screens_render_and_review_actions_work(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->fillForm(['car_hire', 'visa'])->call('submit');
        $application = VendorApplication::sole();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(\App\Filament\Resources\VendorApplications\Pages\ListVendorApplications::class)
            ->assertOk()->assertSee('Ada Mobility Ltd')->assertSee(route('partners.register'));

        Livewire::test(\App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication::class, ['record' => $application->getRouteKey()])
            ->assertOk()
            ->assertSee('Lagos and Abuja')          // car hire answers
            ->assertSee('0123456789')               // admins see bank details
            ->assertDontSee('Air Cargo / Freight')  // services not applied for are hidden
            ->mountAction('verifyDocuments')
            ->set('mountedActions.0.data.verified', $application->documents->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('documents_verified', $application->fresh()->status);

        Livewire::test(\App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication::class, ['record' => $application->getRouteKey()])
            ->mountAction('compliance')
            ->set('mountedActions.0.data.risk_rating', 'medium')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('compliance_completed', $application->fresh()->status);
    }

    public function test_bank_details_are_masked_for_staff_outside_finance(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->fillForm(['car_hire'])->call('submit');
        $operations = \App\Models\Department::firstOrCreate(['slug' => 'operations'], ['name' => 'Operations']);
        $this->actingAs(User::factory()->create(['department_id' => $operations->id]));

        Livewire::test(\App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication::class, ['record' => VendorApplication::sole()->getRouteKey()])
            ->assertOk()
            ->assertDontSee('0123456789')
            ->assertSee('••••••6789');
    }

    /** Fill every step for the given services; stops on $untilStep without validating it. */
    private function fillForm(array $services, int $untilStep = 7)
    {
        $component = Livewire::test(VendorRegistration::class)
            ->set('form.registered_name', 'Ada Mobility Ltd')
            ->set('form.registration_number', 'RC 123456')
            ->set('form.address', '1 Marina, Lagos')
            ->set('form.business_email', 'info@example.com')
            ->set('form.business_phone', '08000000000')
            ->set('form.year_established', '2015')
            ->set('form.locations_served', 'Nigeria')
            ->call('next')->assertHasNoErrors()
            ->set('form.contact_name', 'Ada Obi')
            ->set('form.contact_title', 'Director')
            ->set('form.contact_email', 'ada@example.com')
            ->set('form.contact_phone', '08011111111')
            ->set('form.operations_name', 'Dispatch desk')
            ->set('form.operations_phone', '08022222222')
            ->call('next')->assertHasNoErrors()
            ->set('form.business_types', ['car_rental'])
            ->set('form.services', $services)
            ->set('form.works_with_other_platforms', 'yes')
            ->set('form.other_platforms_details', 'Supply two local agencies')
            ->call('next')->assertHasNoErrors();

        foreach ($services as $serviceKey) {
            foreach (config("vendor_onboarding.services.$serviceKey.fields") as $field => $def) {
                if (! ($def['required'] ?? false)) {
                    continue;
                }
                $value = match ($def['type']) {
                    'checkboxes' => array_slice($def['options'], 0, 2),
                    'yesno' => 'yes',
                    default => 'Lagos and Abuja',
                };
                $component->set("details.$serviceKey.$field", $value);
            }
            if (config("vendor_onboarding.services.$serviceKey.acknowledgement")) {
                $component->set("acknowledged.$serviceKey", true);
            }
        }

        $component->call('next')->assertHasNoErrors()
            ->set('form.booking_channels', ['email', 'whatsapp'])
            ->set('form.booking_email', 'bookings@example.com')
            ->set('form.rate_model', 'net')
            ->set('form.bank_name', 'Example Bank')
            ->set('form.account_name', 'Ada Mobility Ltd')
            ->set('form.account_number', '0123456789')
            ->call('next')->assertHasNoErrors();

        foreach ($component->instance()->documentList() as $type => $doc) {
            if ($doc['required']) {
                $component->set("uploads.$type", UploadedFile::fake()->create("$type.pdf", 100, 'application/pdf'));
            }
        }
        $component->set('expiries.vehicle_insurance', '2030-01-31');

        if ($untilStep === 6) {
            return $component;
        }

        return $component->call('next')->assertHasNoErrors()
            ->set('form.declarant_name', 'Ada Obi')
            ->set('form.declarant_title', 'Director')
            ->set('agree', true)
            ->set('consent', true);
    }
}

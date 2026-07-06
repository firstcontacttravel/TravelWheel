<?php

namespace Tests\Feature;

use App\Livewire\Pages\Visa\ApplicationWizard;
use App\Models\Country;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Services\VisaFormWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VisaApplicationWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_result_starts_a_private_draft_with_expected_travelers(): void
    {
        [$product, $search, $result] = $this->catalogue();

        $response = $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);

        $application = VisaApplication::query()->firstOrFail();
        $response->assertRedirect(route('visa.application', $application));
        $this->assertSame('draft', $application->status);
        $this->assertSame(3, $application->travelers()->count());
        $this->assertDatabaseHas('visa_application_status_history', ['visa_application_id' => $application->id, 'to_status' => 'draft']);
        $this->get(route('visa.application', $application))
            ->assertOk()
            ->assertSee('Confirm your trip')
            ->assertSee('class="tw-icon"', false)
            ->assertDontSee('ph ph-arrow-left', false)
            ->assertDontSee('ph ph-cloud-check', false);
    }

    public function test_product_not_present_in_eligible_results_cannot_start(): void
    {
        [$product, $search] = $this->catalogue();

        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => []])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id])
            ->assertSessionHasErrors('visa');

        $this->assertDatabaseCount('visa_applications', 0);
    }

    public function test_wizard_validates_and_autosaves_trip_step(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();

        session()->put("visa_application_access.{$application->reference}", true);
        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set('contactEmail', 'applicant@example.com')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        $this->assertDatabaseHas('visa_applications', ['id' => $application->id, 'contact_email' => 'applicant@example.com', 'completed_step' => 1, 'current_step' => 2]);
    }

    public function test_trip_fields_validate_in_real_time_and_render_invalid_state(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        session()->put("visa_application_access.{$application->reference}", true);

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set('contactEmail', 'not-an-email')
            ->assertHasErrors(['contactEmail' => 'email'])
            ->assertSee('Please check the highlighted fields.')
            ->assertSee('aria-invalid="true"', false)
            ->set('contactEmail', 'valid@example.com')
            ->assertHasNoErrors('contactEmail');
    }

    public function test_all_required_traveler_fields_show_errors_after_submission(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        session()->put("visa_application_access.{$application->reference}", true);
        $traveler = $application->travelers()->firstOrFail();

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set('step', 2)
            ->call('next')
            ->assertHasErrors([
                "travelers.{$traveler->id}.first_name",
                "travelers.{$traveler->id}.last_name",
                "travelers.{$traveler->id}.sex",
                "travelers.{$traveler->id}.date_of_birth",
                "travelers.{$traveler->id}.place_of_birth",
                "travelers.{$traveler->id}.phone",
                "travelers.{$traveler->id}.home_address",
            ])
            ->assertSet('step', 2)
            ->assertSee('Please check the highlighted fields.');
    }

    public function test_resume_requires_the_secret_token(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        $token = session("visa_application_resume_tokens.{$application->reference}");

        session()->forget("visa_application_access.{$application->reference}");
        $this->get(route('visa.application.resume', [$application, 'wrong-token']))->assertForbidden();
        $this->get(route('visa.application.resume', [$application, $token]))->assertRedirect(route('visa.application', $application));
    }

    public function test_document_uploads_follow_adult_and_minor_parent_profiles(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $product->requirements()->create(['name' => "Father's data page", 'category' => 'passport', 'scope' => 'traveler', 'requirement_state' => 'conditional', 'conditions' => ['applicant_type' => 'minor_nigerian']]);
        $product->requirements()->create(['name' => 'Foreign parent CERPAC', 'category' => 'passport', 'scope' => 'traveler', 'requirement_state' => 'conditional', 'conditions' => ['applicant_type' => 'minor_foreign']]);
        $product->requirements()->create(['name' => 'Company CAC', 'category' => 'business', 'scope' => 'traveler', 'requirement_state' => 'conditional', 'conditions' => ['applicant_type' => 'company']]);
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        $child = $application->travelers()->where('traveler_type', 'child')->firstOrFail();
        $documentStep = $this->stepNumber($application, 'documents');

        session()->put("visa_application_access.{$application->reference}", true);
        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set("travelers.{$child->id}.applicant_type", 'minor_nigerian')
            ->set('step', $documentStep)
            ->assertSee("Father's data page")
            ->assertDontSee('Foreign parent CERPAC')
            ->assertDontSee('Company CAC');
    }

    public function test_service_documents_appear_only_after_the_service_is_selected(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $service = $product->optionalServices()->create(['name' => 'Travel insurance', 'service_type' => 'insurance', 'pricing_model' => 'included']);
        $product->requirements()->create(['optional_service_code' => $service->code, 'name' => 'Insurance information form', 'category' => 'supporting_document', 'scope' => 'application', 'requirement_state' => 'required']);
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        $documentStep = $this->stepNumber($application, 'documents');
        session()->put("visa_application_access.{$application->reference}", true);

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->assertSeeInOrder(['Services', 'Documents'])
            ->set('step', $documentStep)
            ->assertDontSee('Insurance information form')
            ->set("serviceSelections.{$service->id}", true)
            ->assertSee('Insurance information form');
    }

    public function test_empty_question_and_service_steps_are_automatically_omitted(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        session()->put("visa_application_access.{$application->reference}", true);

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->assertDontSee('Questions')
            ->assertDontSee('Services')
            ->assertSee('Documents');
    }

    public function test_document_upload_can_be_validated_stored_and_advanced_to_review(): void
    {
        Storage::fake('local');
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        $application->travelers()->where('id', '!=', $application->travelers()->value('id'))->delete();
        $application->load('travelers');
        session()->put("visa_application_access.{$application->reference}", true);
        $requirement = $product->requirements()->firstOrFail();
        $traveler = $application->travelers->first();
        $documentStep = $this->stepNumber($application, 'documents');
        $reviewStep = $this->stepNumber($application, 'review');

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set('step', $documentStep)
            ->set("uploads.{$requirement->id}_{$traveler->id}", UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'))
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', $reviewStep);

        $this->assertDatabaseHas('visa_application_documents', [
            'visa_application_id' => $application->id,
            'visa_requirement_id' => $requirement->id,
            'visa_traveler_id' => $traveler->id,
            'original_name' => 'passport.pdf',
        ]);
    }

    public function test_traveler_labels_only_show_positions_when_type_is_repeated(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        session()->put("visa_application_access.{$application->reference}", true);

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->set('step', 2)
            ->assertSee('Adult 1')
            ->assertSee('Adult 2')
            ->assertSee('Child')
            ->assertDontSee('Child 1');
    }

    public function test_visa_widget_defaults_passport_nationality_to_nigeria(): void
    {
        Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria', 'is_active' => true]);

        $this->get(route('air.visa'))
            ->assertOk()
            ->assertSee('Nigeria (NG)')
            ->assertSee('selected', false);
    }

    public function test_field_selection_and_automatic_steps_are_snapshotted_for_the_application(): void
    {
        [$product, $search, $result] = $this->catalogue();
        $question = $product->questions()->create(['key' => 'host_name', 'section' => 'host', 'label' => 'Host full name', 'input_type' => 'text', 'scope' => 'application', 'is_required' => true, 'is_active' => true]);
        $configuration = [
            'traveler_fields' => ['first_name', 'last_name'],
            'passport_fields' => ['passport_number'],
        ];
        $product->update(['form_configuration' => $configuration]);

        $this->withSession(['visaSearchParamsStore' => $search, 'visaResultsStore' => [$result]])
            ->post(route('visa.applications.start'), ['visa_product_id' => $product->id]);
        $application = VisaApplication::query()->firstOrFail();
        $product->update(['form_configuration' => app(VisaFormWorkflow::class)->defaults()]);
        session()->put("visa_application_access.{$application->reference}", true);

        Livewire::test(ApplicationWizard::class, ['application' => $application])
            ->assertSee('Questions')
            ->assertDontSee('Services')
            ->set('step', 2)
            ->assertSee('First name')
            ->assertDontSee('Home address')
            ->set('step', 3)
            ->assertSee('Passport number')
            ->assertDontSee('Passport type')
            ->set('step', 4)
            ->assertSee('Host full name');

        $snapshot = $application->fresh()->form_configuration;
        $this->assertSame($configuration['traveler_fields'], $snapshot['traveler_fields']);
        $this->assertSame($configuration['passport_fields'], $snapshot['passport_fields']);
        $this->assertTrue($snapshot['steps']['hasQuestions']);
        $this->assertFalse($snapshot['steps']['hasServices']);
        $this->assertTrue($snapshot['steps']['hasDocuments']);
    }

    private function catalogue(): array
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Canada visitor visa', 'slug' => 'canada-visitor', 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 2]);
        $processing = $product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 5, 'maximum_business_days' => 10]);
        $product->requirements()->create(['name' => 'Passport data page', 'category' => 'passport', 'scope' => 'traveler', 'requirement_state' => 'required']);

        $search = ['nationality_id' => $nationality->id, 'destination_id' => $destination->id, 'residence_country_id' => null, 'nationality_name' => 'Ghana', 'destination_name' => 'Canada', 'arrival_date' => now()->addMonth()->toDateString(), 'departure_date' => now()->addMonths(2)->toDateString(), 'adults' => 2, 'children' => 1, 'infants' => 0, 'travelers' => ['adult' => 2, 'child' => 1, 'infant' => 0]];
        $result = ['id' => $product->id, 'eligibility' => ['status' => 'eligible']];

        return [$product->fresh(), $search, $result, $processing];
    }

    private function stepNumber(VisaApplication $application, string $key): int
    {
        $index = collect(app(VisaFormWorkflow::class)->applicationFlow($application->form_configuration))->search(fn (array $step) => $step['key'] === $key);

        return $index + 1;
    }
}

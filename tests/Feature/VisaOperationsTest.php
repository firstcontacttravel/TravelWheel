<?php

namespace Tests\Feature;

use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use App\Mail\VisaApplicationVendorMail;
use App\Models\Country;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Models\VisaVendor;
use App\Services\VisaApplicationTransitionService;
use App\Services\VisaOperationsService;
use App\Services\VisaVendorDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VisaOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_filament_queue_and_review_workspace(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $application = $this->application('submitted');

        $this->actingAs($admin)->get(VisaApplicationResource::getUrl('index'))->assertOk()->assertSee('Visa application queue');
        $this->actingAs($admin)->get(VisaApplicationResource::getUrl('view', ['record' => $application]))->assertOk()->assertSee($application->reference);
    }

    public function test_officer_accepts_assignment_and_starts_review_with_an_immutable_audit(): void
    {
        Mail::fake();
        $application = $this->application('submitted');
        $officer = User::factory()->create(['visa_role' => 'visa_officer']);

        app(VisaApplicationTransitionService::class)->transition($application, 'under_review', $officer, ['public_note' => 'Your application is now in review.', 'internal_note' => 'Payment and passport checked.']);

        $application->refresh();
        $this->assertSame('under_review', $application->status);
        $this->assertSame($officer->id, $application->assigned_to);
        $this->assertDatabaseHas('visa_application_status_history', ['visa_application_id' => $application->id, 'from_status' => 'submitted', 'to_status' => 'under_review', 'actor_id' => $officer->id]);
        $this->assertDatabaseHas('visa_internal_notes', ['visa_application_id' => $application->id, 'body' => 'Payment and passport checked.']);
        $this->assertDatabaseHas('visa_audit_events', ['visa_application_id' => $application->id, 'event_type' => 'status_transition']);
        $this->assertDatabaseHas('visa_notification_events', ['visa_application_id' => $application->id, 'event_type' => 'status:under_review']);
    }

    public function test_transition_matrix_rejects_skipping_review_and_processing(): void
    {
        $this->expectException(ValidationException::class);
        app(VisaApplicationTransitionService::class)->transition($this->application('submitted'), 'approved', User::factory()->create(['visa_role' => 'visa_officer']), ['decision_date' => now()->toDateString()]);
    }

    public function test_document_request_moves_an_active_review_to_action_required(): void
    {
        Mail::fake();
        $application = $this->application('under_review');
        $officer = User::factory()->create(['visa_role' => 'visa_officer']);

        $request = app(VisaOperationsService::class)->requestDocument($application, $officer, ['title' => 'Updated bank statement', 'instructions' => 'Upload the latest three months.', 'due_at' => now()->addDays(5)]);

        $this->assertSame('open', $request->status);
        $this->assertSame('action_required', $application->fresh()->status);
        $this->assertDatabaseHas('visa_notification_events', ['visa_application_id' => $application->id, 'event_type' => 'document_request:'.$request->id]);
    }

    public function test_approved_application_can_be_issued_with_a_private_versioned_document(): void
    {
        Mail::fake();
        Storage::fake('local');
        $application = $this->application('processing');
        $officer = User::factory()->create(['visa_role' => 'visa_officer']);
        $transitions = app(VisaApplicationTransitionService::class);
        $transitions->transition($application, 'approved', $officer, ['decision_date' => now()->toDateString(), 'decision_reference' => 'AUTH-123']);
        $path = "visa-applications/{$application->reference}/issued/visa.pdf";
        Storage::disk('local')->put($path, 'visa document');

        $document = app(VisaOperationsService::class)->issue($application->fresh(), $officer, ['document_path' => $path, 'document_name' => 'issued-visa.pdf']);
        $transitions->transition($application->fresh(), 'issued', $officer, ['valid_from' => now()->toDateString(), 'valid_until' => now()->addYear()->toDateString()]);

        $this->assertSame(1, $document->version);
        $this->assertSame('issued', $application->fresh()->status);
        $this->assertDatabaseHas('visa_audit_events', ['visa_application_id' => $application->id, 'event_type' => 'visa_document_issued']);
    }

    public function test_only_administrator_can_reopen_a_rejected_application(): void
    {
        Mail::fake();
        $application = $this->application('rejected');
        $officer = User::factory()->create(['visa_role' => 'visa_officer']);
        $admin = User::factory()->create(['visa_role' => 'administrator']);
        $service = app(VisaApplicationTransitionService::class);

        $this->assertSame([], $service->allowedTargets($application, $officer));
        $service->transition($application, 'under_review', $admin, ['public_note' => 'Application reopened.', 'internal_note' => 'Authorized appeal correction.']);
        $this->assertSame('under_review', $application->fresh()->status);
    }

    public function test_application_can_be_queued_to_its_vendor_with_private_documents_and_an_audit_event(): void
    {
        Mail::fake();
        Storage::fake('local');
        $application = $this->application('submitted');
        $vendor = VisaVendor::query()->create(['name' => 'Consular Partner', 'contact_person' => 'Visa Desk', 'email' => 'vendor@example.com', 'is_active' => true]);
        $application->product->update(['visa_vendor_id' => $vendor->id]);
        $requirement = $application->product->requirements()->create(['name' => 'Passport data page', 'category' => 'passport', 'scope' => 'traveler', 'requirement_state' => 'required']);
        $path = "visa-applications/{$application->reference}/documents/passport.pdf";
        Storage::disk('local')->put($path, 'private passport');
        $document = $application->documents()->create(['visa_requirement_id' => $requirement->id, 'disk' => 'local', 'path' => $path, 'original_name' => 'passport.pdf', 'mime_type' => 'application/pdf', 'size' => 16]);
        $officer = User::factory()->create(['visa_role' => 'visa_officer']);

        app(VisaVendorDispatchService::class)->send($application->fresh(), $officer);

        $response = $this->actingAs($officer)->get(route('admin.visa.documents.application', $document));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));

        Mail::assertQueued(VisaApplicationVendorMail::class, fn (VisaApplicationVendorMail $mail) => $mail->hasTo('vendor@example.com') && count($mail->attachments()) === 1);
        $this->assertDatabaseHas('visa_audit_events', [
            'visa_application_id' => $application->id,
            'user_id' => $officer->id,
            'event_type' => 'sent_to_vendor',
        ]);
    }

    private function application(string $status): VisaApplication
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'GB', 'name' => 'United Kingdom']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 1]);
        $application = VisaApplication::query()->create([
            'reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', 'resume'), 'visa_product_id' => $product->id, 'product_version' => 1, 'status' => $status,
            'current_step' => 8, 'completed_step' => 8, 'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id,
            'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 1, 'contact_email' => 'operations@example.com',
            'search_snapshot' => [], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
        $application->statusHistory()->create(['to_status' => $status, 'actor_type' => 'system']);

        return $application;
    }
}

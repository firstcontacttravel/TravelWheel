<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\VisaApplication;
use App\Models\VisaFunnelEvent;
use App\Models\VisaProduct;
use App\Services\VisaFunnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisaReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_visa_routes_can_be_disabled_without_disabling_admin(): void
    {
        config(['visa.enabled' => false]);

        $this->get(route('air.visa'))->assertNotFound();
        $this->get('/admin/login')->assertOk();
    }

    public function test_funnel_events_are_idempotent_and_retain_the_application_journey(): void
    {
        $application = $this->application();
        $funnel = app(VisaFunnelService::class);
        $funnel->record('application_started', [], $application, 'start|'.$application->id);
        $journey = session('visa_journey_id');
        session()->forget('visa_journey_id');
        $funnel->record('application_started', [], $application, 'start|'.$application->id);
        $funnel->record('quote_created', [], $application, 'quote|1');

        $this->assertDatabaseCount('visa_funnel_events', 2);
        $this->assertSame(2, VisaFunnelEvent::query()->where('journey_id', $journey)->count());
    }

    public function test_legacy_import_command_is_blocked_by_approved_policy(): void
    {
        $this->artisan('visa:import-legacy-catalogue')->assertFailed();
    }

    private function application(): VisaApplication
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'GB', 'name' => 'United Kingdom']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single']);

        return VisaApplication::query()->create(['reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', 'token'), 'visa_product_id' => $product->id, 'product_version' => 1, 'status' => 'draft', 'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id, 'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 1, 'search_snapshot' => [], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30)]);
    }
}

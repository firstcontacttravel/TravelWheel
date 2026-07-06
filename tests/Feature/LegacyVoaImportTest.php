<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\VisaProduct;
use App\Services\LegacyVisaCatalogueImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyVoaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_singular_voa_table_is_imported_by_country_name_with_single_entry_fees(): void
    {
        $nigeria = Country::query()->create(['alpha2' => 'NG', 'alpha3' => 'NGA', 'name' => 'Nigeria']);
        $ghana = Country::query()->create(['alpha2' => 'GH', 'alpha3' => 'GHA', 'name' => 'Ghana']);
        $czechia = Country::query()->create(['alpha2' => 'CZ', 'alpha3' => 'CZE', 'name' => 'Czechia']);

        Schema::create('voa', function (Blueprint $table): void {
            $table->id();
            $table->string('country');
            $table->decimal('single_entry_fee', 10, 2);
        });
        DB::table('voa')->insert([
            ['country' => 'Ghana', 'single_entry_fee' => 0],
            ['country' => 'Czech Republic', 'single_entry_fee' => 88],
            ['country' => 'processing_adult', 'single_entry_fee' => 40000],
            ['country' => 'Nigeria', 'single_entry_fee' => 0],
        ]);
        VisaProduct::query()->create(['destination_country_id' => $ghana->id, 'name' => 'Incorrect Ghana VOA', 'slug' => 'legacy-voa-gh', 'family' => 'voa', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published']);

        $counts = app(LegacyVisaCatalogueImporter::class)->import();

        $this->assertSame(['standard' => 0, 'voa' => 1], $counts);
        $product = VisaProduct::query()->where('slug', 'legacy-nigeria-business-visa')->firstOrFail();
        $this->assertSame($nigeria->id, $product->destination_country_id);
        $this->assertSame('Nigerian Business Visa', $product->name);
        $this->assertSame('business', $product->category);
        $this->assertSame('single', $product->entry_type);
        $this->assertSame('published', $product->publication_status->value);
        $this->assertEqualsCanonicalizing([$ghana->id, $czechia->id], $product->eligibilityRules()->pluck('country_id')->all());
        $this->assertDatabaseHas('visa_fee_components', ['visa_product_id' => $product->id, 'name' => 'Nigerian Business Visa fee', 'currency' => 'USD', 'amount' => 0]);
        $this->assertDatabaseHas('visa_fee_components', ['visa_product_id' => $product->id, 'name' => 'Nigerian Business Visa fee', 'currency' => 'USD', 'amount' => 88]);
        $this->assertDatabaseMissing('visa_fee_components', ['amount' => 40000]);
        $this->assertDatabaseHas('visa_products', ['slug' => 'legacy-voa-gh', 'publication_status' => 'archived']);
    }
}

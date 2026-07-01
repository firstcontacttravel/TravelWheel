<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentsRenderTest extends TestCase
{
    public function test_button_variants_render_with_namespaced_classes(): void
    {
        $html = Blade::render('<x-ui.button variant="outline" size="sm" href="/visa">Continue</x-ui.button>');

        $this->assertStringContainsString('tw-ui-button--outline', $html);
        $this->assertStringContainsString('tw-ui-button--sm', $html);
        $this->assertStringContainsString('href="/visa"', $html);
    }

    public function test_form_field_exposes_required_and_error_information(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.field label="Passport nationality" for="nationality" required error="Choose a nationality.">
                <x-ui.input id="nationality" name="nationality" invalid />
            </x-ui.field>
        BLADE);

        $this->assertStringContainsString('for="nationality"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('id="nationality-description"', $html);
        $this->assertStringContainsString('Choose a nationality.', $html);
    }

    public function test_stepper_marks_the_current_step_accessibly(): void
    {
        $html = Blade::render(
            '<x-ui.stepper :steps="$steps" :current="2" />',
            ['steps' => ['Trip', 'Travelers', 'Documents']]
        );

        $this->assertStringContainsString('aria-current="step"', $html);
        $this->assertStringContainsString('tw-ui-stepper__item--complete', $html);
        $this->assertStringContainsString('tw-ui-stepper__item--upcoming', $html);
    }

    public function test_card_alert_and_badge_render_their_contracts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.card title="Requirements" description="Review before applying.">
                <x-ui.alert variant="warning">Passport expires soon.</x-ui.alert>
                <x-ui.badge variant="success">Eligible</x-ui.badge>
            </x-ui.card>
        BLADE);

        $this->assertStringContainsString('tw-ui-card__title', $html);
        $this->assertStringContainsString('tw-ui-alert--warning', $html);
        $this->assertStringContainsString('tw-ui-badge--success', $html);
    }

    public function test_remaining_phase_one_components_render_accessible_contracts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.select name="country"><option>Nigeria</option></x-ui.select>
            <x-ui.date name="arrival" />
            <x-ui.file-upload name="passport" />
            <x-ui.modal id="confirm-modal" title="Confirm"><p>Ready.</p></x-ui.modal>
            <x-ui.timeline :items="$timeline" />
            <x-ui.state variant="loading" title="Loading products" />
        BLADE, [
            'timeline' => [
                ['title' => 'Submitted', 'state' => 'complete'],
                ['title' => 'Review', 'state' => 'current'],
            ],
        ]);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('aria-labelledby="confirm-modal-title"', $html);
        $this->assertStringContainsString('aria-current="step"', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
    }

    public function test_price_breakdown_separates_direct_fees_from_pay_now_total(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.price-breakdown
                :items="$items"
                :direct-items="$directItems"
                :total="60000"
            />
        BLADE, [
            'items' => [['label' => 'Service fee', 'amount' => 60000]],
            'directItems' => [['label' => 'Authority fee', 'amount' => 250000]],
        ]);

        $this->assertStringContainsString('Pay separately to the authority', $html);
        $this->assertStringContainsString('NGN 60,000.00', $html);
        $this->assertStringContainsString('NGN 250,000.00', $html);
    }

    public function test_phase_one_showcase_renders_all_major_components(): void
    {
        $html = view('design-system.visa')->render();

        foreach ([
            'tw-ui-page',
            'tw-ui-stepper',
            'tw-ui-select-wrap',
            'tw-ui-upload',
            'tw-ui-price',
            'tw-ui-timeline',
            'tw-ui-state',
            'tw-ui-modal',
        ] as $class) {
            $this->assertStringContainsString($class, $html);
        }
    }
}

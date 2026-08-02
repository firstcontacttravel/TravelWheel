@php
    $sections = [
        ['id' => 'separate-roles', 'label' => 'Important: Separate Roles'],
        ['id' => 'loan-agreement', 'label' => 'Loan Agreement'],
        ['id' => 'loan', 'label' => '1. The Loan'],
        ['id' => 'interest', 'label' => '2. Interest'],
        ['id' => 'insurance', 'label' => '3. Insurance'],
        ['id' => 'payment', 'label' => '4. Repayment'],
        ['id' => 'cost-and-charges', 'label' => '5. Costs and Charges'],
        ['id' => 'breach', 'label' => '6. Breach and Default'],
        ['id' => 'other-obligation', 'label' => '7. Other Obligations'],
        ['id' => 'general', 'label' => '8. General'],
        ['id' => 'confirmation', 'label' => 'Confirmation'],
        ['id' => 'indemnity', 'label' => 'Indemnity'],
    ];
@endphp

<div>
    <x-legal.layout
        title="TravelFlex Fast Credit Loan Agreement"
        updated="31 July 2026"
        :sections="$sections"
        :show-acknowledgement="false"
    >
        <x-legal.callout variant="info">
            <p>This is the Fast Credit Ltd loan agreement presented to customers when they submit a TravelFlex application through TravelWheel.</p>
        </x-legal.callout>

        @include('livewire.pages.flight.partials.fastcredit-agreement', [
            'class' => 'fastcredit-legal-agreement',
            'withAnchors' => true,
        ])
    </x-legal.layout>

    <style>
        .fastcredit-legal-agreement h2 {
            scroll-margin-top: 150px;
            margin: var(--space-8) 0 var(--space-3);
            color: var(--color-neutral-900);
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            line-height: var(--leading-snug);
        }

        .fastcredit-legal-agreement h2:first-child {
            margin-top: var(--space-3);
            font-size: var(--text-2xl);
        }

        .fastcredit-legal-agreement .fastcredit-role-disclosure {
            margin: var(--space-6) 0 var(--space-10);
            padding: var(--space-6);
            border: 2px solid var(--color-primary);
            border-radius: var(--radius-xl);
            background: var(--color-primary-50);
        }

        .fastcredit-legal-agreement .fastcredit-role-disclosure h2 {
            margin-top: 0;
            color: var(--color-primary);
        }

        .fastcredit-legal-agreement .fastcredit-role-disclosure p:last-child {
            margin-bottom: 0;
        }
    </style>
</div>

@props(['steps', 'current' => 1])

<ol class="tw-ui-stepper" style="--tw-ui-step-count: {{ count($steps) }}" aria-label="Progress">
    @foreach($steps as $index => $step)
        @php
            $number = $index + 1;
            $state = $number < $current ? 'complete' : ($number === $current ? 'current' : 'upcoming');
        @endphp
        <li class="tw-ui-stepper__item tw-ui-stepper__item--{{ $state }}" @if($state === 'current') aria-current="step" @endif>
            <span class="tw-ui-stepper__marker">{{ $state === 'complete' ? '✓' : $number }}</span>
            <span class="tw-ui-stepper__label">{{ $step }}</span>
        </li>
    @endforeach
</ol>

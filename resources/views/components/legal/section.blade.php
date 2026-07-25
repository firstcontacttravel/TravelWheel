@props(['id', 'title'])

@php
    $number = null;
    $heading = $title;

    if (preg_match('/^(\d+)\.\s*(.+)$/', $title, $matches)) {
        $number = $matches[1];
        $heading = $matches[2];
    }
@endphp

<section id="{{ $id }}" aria-labelledby="{{ $id }}-heading">
    <h2 id="{{ $id }}-heading">
        @if($number)
            <span class="legal-doc__section-number" aria-hidden="true">{{ $number }}</span>
        @endif
        <span>{{ $heading }}</span>
        <a class="legal-doc__anchor" href="#{{ $id }}" aria-label="Link to {{ $heading }}">
            <i class="ph ph-link-simple" aria-hidden="true"></i>
        </a>
    </h2>
    {{ $slot }}
</section>

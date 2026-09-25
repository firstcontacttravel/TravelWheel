@props(['tone' => 'neutral', 'title' => null])
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
    $map = [
        'neutral' => ['bg' => $c['panel'],       'bd' => $c['line'],    'fg' => $c['text']],
        'success' => ['bg' => $c['success_bg'],  'bd' => '#b7e7cd',     'fg' => $c['success']],
        'warning' => ['bg' => $c['warning_bg'],  'bd' => '#fde8c8',     'fg' => $c['warning']],
        'danger'  => ['bg' => $c['danger_bg'],   'bd' => '#fee4e2',     'fg' => $c['danger']],
    ];
    $t = $map[$tone] ?? $map['neutral'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $t['bg'] }};border:1px solid {{ $t['bd'] }};border-radius:10px;margin-bottom:20px;">
<tr><td style="padding:14px 16px;font-family:{{ $font }};font-size:13px;line-height:1.65;color:{{ $t['fg'] }};">
    @if ($title)<div style="font-weight:700;margin-bottom:4px;">{{ $title }}</div>@endif
    {{ $slot }}
</td></tr>
</table>

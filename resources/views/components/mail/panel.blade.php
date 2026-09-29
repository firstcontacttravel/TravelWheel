@props([
    'label' => null,
    'value' => null,
    'sublabel' => null,
    'altLabel' => null,
    'altValue' => null,
    'altSublabel' => null,
    'mono' => false,
])
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
    $mono = $mono ? "'SFMono-Regular',Consolas,'Liberation Mono',Menlo,Courier,monospace" : $font;
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $c['line'] }};border-radius:10px;background-color:{{ $c['panel'] }};margin-bottom:20px;">
<tr>
    <td style="padding:16px 18px;{{ $altValue ? 'border-right:1px solid '.$c['line'].';' : '' }}">
        <div style="font-family:{{ $font }};font-size:12px;color:{{ $c['muted'] }};line-height:1.4;">{{ $label }}</div>
        <div style="font-family:{{ $mono }};font-size:20px;font-weight:700;color:{{ $c['brand'] }};line-height:1.3;margin-top:4px;">{{ $value }}</div>
        @if ($sublabel)
        <div style="font-family:{{ $font }};font-size:12px;color:{{ $c['muted'] }};margin-top:3px;">{{ $sublabel }}</div>
        @endif
    </td>
    @if ($altValue)
    <td align="right" style="padding:16px 18px;">
        <div style="font-family:{{ $font }};font-size:12px;color:{{ $c['muted'] }};line-height:1.4;">{{ $altLabel }}</div>
        <div style="font-family:{{ $font }};font-size:18px;font-weight:700;color:{{ $c['ink'] }};line-height:1.3;margin-top:4px;">{{ $altValue }}</div>
        @if ($altSublabel)
        <div style="font-family:{{ $font }};font-size:12px;color:{{ $c['muted'] }};margin-top:3px;">{{ $altSublabel }}</div>
        @endif
    </td>
    @endif
</tr>
</table>

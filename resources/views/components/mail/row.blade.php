@props(['label' => null, 'strong' => false])
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
    $cell = 'padding:9px 0;border-bottom:1px solid '.$c['line_soft'].';font-family:'.$font.';font-size:13.5px;line-height:1.5;';
@endphp
<tr>
    <td style="{{ $cell }}color:{{ $c['muted'] }};">{{ $label }}</td>
    <td align="right" style="{{ $cell }}color:{{ $strong ? $c['brand'] : $c['ink'] }};font-weight:{{ $strong ? '700' : '600' }};">{{ $slot }}</td>
</tr>

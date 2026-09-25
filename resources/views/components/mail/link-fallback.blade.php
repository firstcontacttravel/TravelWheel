@props(['url'])
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
@endphp
<div style="font-family:{{ $font }};font-size:12.5px;line-height:1.6;color:{{ $c['muted'] }};margin:-12px 0 20px;">
    If the button does not work, paste this into your browser:<br />
    <a href="{{ $url }}" style="color:{{ $c['brand'] }};word-break:break-all;">{{ $url }}</a>
</div>

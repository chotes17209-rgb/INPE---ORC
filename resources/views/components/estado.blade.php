@props(['color' => 'secondary', 'texto' => null])
<span {{ $attributes->merge(['class' => "estado estado-{$color}"]) }}>{{ $texto ?? $slot }}</span>

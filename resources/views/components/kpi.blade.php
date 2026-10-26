@props(['color' => 'primary', 'icono' => 'graph-up', 'label', 'valor', 'detalle' => null, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "kpi kpi--{$color}"]) }}>
    <div class="kpi__icon"><i class="bi bi-{{ $icono }}" aria-hidden="true"></i></div>
    <div class="min-w-0">
        <div class="kpi__label">{{ $label }}</div>
        <div class="kpi__value">{{ $valor }}</div>
        @if($detalle)<div class="kpi__hint">{{ $detalle }}</div>@endif
    </div>
</{{ $tag }}>

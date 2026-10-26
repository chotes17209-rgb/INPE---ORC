@props(['icono' => 'inbox', 'titulo' => 'No hay registros', 'texto' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi bi-{{ $icono }}" aria-hidden="true"></i>
    <div class="empty-state__title">{{ $titulo }}</div>
    @if($texto)<div class="empty-state__text">{{ $texto }}</div>@endif
    {{ $slot }}
</div>

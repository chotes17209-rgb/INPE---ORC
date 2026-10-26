@props(['numero' => null, 'titulo', 'ayuda' => null, 'icono' => null])
<section {{ $attributes->merge(['class' => 'form-section']) }}>
    <header class="form-section__header">
        @if($numero)
            <span class="form-section__number">{{ $numero }}</span>
        @elseif($icono)
            <i class="bi bi-{{ $icono }} text-primary mt-1" aria-hidden="true"></i>
        @endif
        <div>
            <h2 class="form-section__title">{{ $titulo }}</h2>
            @if($ayuda)<p class="form-section__help">{{ $ayuda }}</p>@endif
        </div>
    </header>
    <div class="form-section__body">
        {{ $slot }}
    </div>
</section>

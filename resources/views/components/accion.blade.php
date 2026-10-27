{{--
    Botón de acción con texto, para las filas de los listados.
    Ejemplos:
      <x-accion tipo="ver" :href="route('…show', $r)" />            → abre la ficha en ventana flotante
      <x-accion tipo="editar" :href="route('…edit', $r)" />
      <x-accion tipo="eliminar" :href="route('…destroy', $r)" confirmar="Se eliminará…" />
      <x-accion tipo="pagar" data-pago-url="…" … />                  → abre el modal de pago
--}}
@props(['tipo' => 'ver', 'href' => null, 'texto' => null, 'icono' => null, 'modal' => true, 'confirmar' => null, 'nombre' => null])
@php
    $tipos = [
        'ver'           => ['eye', 'Ver'],
        'editar'        => ['pencil-square', 'Editar'],
        'pagar'         => ['cash-coin', 'Pagar'],
        'eliminar'      => ['trash3', 'Eliminar'],
        'completar'     => ['check2-circle', 'Conformidad'],
        'mantenimiento' => ['tools', 'Mantenimiento'],
    ];
    [$iconoBase, $textoBase] = $tipos[$tipo] ?? ['dot', ''];
    $icono ??= $iconoBase;
    $texto ??= $textoBase;
    $clase = "btn btn-accion btn-accion--{$tipo}";
    $etiqueta = trim($texto . ' ' . $nombre);
@endphp
@if($tipo === 'eliminar')
    <form method="POST" action="{{ $href }}" class="d-inline"
          data-confirm="{{ $confirmar ?? 'Se eliminará el registro seleccionado.' }}"
          data-confirm-title="¿Desea eliminar{{ $nombre ? ' ' . $nombre : ' este registro' }}?" data-confirm-btn="Sí, eliminar">
        @csrf @method('DELETE')
        <button type="submit" {{ $attributes->merge(['class' => $clase]) }} aria-label="{{ $etiqueta }}">
            <i class="bi bi-{{ $icono }}" aria-hidden="true"></i><span>{{ $texto }}</span>
        </button>
    </form>
@elseif($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clase]) }} @if($modal) data-modal @endif aria-label="{{ $etiqueta }}">
        <i class="bi bi-{{ $icono }}" aria-hidden="true"></i><span>{{ $texto }}</span>
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $clase]) }} aria-label="{{ $etiqueta }}">
        <i class="bi bi-{{ $icono }}" aria-hidden="true"></i><span>{{ $texto }}</span>
    </button>
@endif

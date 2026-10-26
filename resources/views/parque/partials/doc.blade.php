{{-- Fecha de vencimiento de un documento vehicular con su indicador de situación. Requiere $vehiculo y $campo. --}}
@php $situacion = $vehiculo->situacionDocumento($campo); @endphp
@if($situacion === 'sin_registro')
    <span class="text-muted">No registrado</span>
@else
    <span class="doc-fecha {{ $situacion }}">{{ $vehiculo->{$campo}->format('d/m/Y') }}</span>
    @if($situacion === 'vencido')
        <x-estado color="danger" class="ms-1">Vencido</x-estado>
    @elseif($situacion === 'por_vencer')
        <x-estado color="warning" class="ms-1">Por vencer</x-estado>
    @endif
@endif

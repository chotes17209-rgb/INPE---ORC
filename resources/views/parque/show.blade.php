@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Vehículo ' . $vehiculo->placa)
@section('page-title', 'Vehículo ' . $vehiculo->placa)
@section('page-subtitle', $vehiculo->descripcion . ' · ' . $vehiculo->anio_fabricacion . ' · ' . $vehiculo->tipo_label)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $vehiculo->placa }}</li>
@endsection

@section('page-actions')
    @unless(request()->header('X-Modal'))
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalIA" data-analizar-vehiculo="{{ $vehiculo->id }}">
            <i class="bi bi-stars me-1" aria-hidden="true"></i>Analizar con IA
        </button>
    @endunless
    @can('registrar')
        <a href="{{ route('parque.vehiculos.edit', $vehiculo) }}" data-modal class="btn btn-outline-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
        @if($vehiculo->estado !== 'baja')
            <a href="{{ route('parque.mantenimientos.create', ['vehiculo_id' => $vehiculo->id]) }}" data-modal class="btn btn-primary"><i class="bi bi-tools me-1" aria-hidden="true"></i>Programar mantenimiento</a>
        @endif
    @endcan
@endsection

@section('content')
@php
    $docsVencidos = collect(\App\Models\Vehiculo::DOCUMENTOS)->filter(fn ($n, $campo) => $vehiculo->situacionDocumento($campo) === 'vencido');
@endphp
@if($docsVencidos->isNotEmpty() && $vehiculo->estado !== 'baja')
    <div class="callout callout--danger mb-3"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Documentación vencida:</span> {{ $docsVencidos->implode(', ') }}. El vehículo no debería circular hasta regularizarla.</div></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-card-list card-header__icon" aria-hidden="true"></i> Ficha técnica
                <span class="card-header__aside"><x-estado :color="$vehiculo->estado_color">{{ $vehiculo->estado_label }}</x-estado></span>
            </div>
            <div class="ficha">
                <x-dato label="Placa"><span class="font-monospace">{{ $vehiculo->placa }}</span></x-dato>
                <x-dato label="Marca">{{ $vehiculo->marca }}</x-dato>
                <x-dato label="Modelo">{{ $vehiculo->modelo }}</x-dato>
                <x-dato label="Año de fabricación">{{ $vehiculo->anio_fabricacion }}</x-dato>
                <x-dato label="Clase">{{ $vehiculo->tipo_label }}</x-dato>
                <x-dato label="Combustible">{{ $vehiculo->combustible_label }}</x-dato>
                <x-dato label="Color">{{ $vehiculo->color }}</x-dato>
                <x-dato label="N° de serie (VIN)"><span class="font-monospace">{{ $vehiculo->numero_serie }}</span></x-dato>
                <x-dato label="N° de motor"><span class="font-monospace">{{ $vehiculo->numero_motor }}</span></x-dato>
            </div>
            <h3 class="seccion-titulo">Datos patrimoniales y asignación</h3>
            <div class="ficha">
                <x-dato label="Valor de adquisición">S/ {{ number_format($vehiculo->valor_adquisicion ?? 0, 2) }}</x-dato>
                <x-dato label="Fecha de adquisición">{{ $vehiculo->fecha_adquisicion?->format('d/m/Y') }}</x-dato>
                <x-dato label="Kilometraje actual">{{ number_format($vehiculo->kilometraje_actual) }} km</x-dato>
                <x-dato label="Próximo mantenimiento">{{ $vehiculo->kilometraje_siguiente_mantenimiento ? number_format($vehiculo->kilometraje_siguiente_mantenimiento) . ' km' : '' }}</x-dato>
                <x-dato label="Área asignada">{{ $vehiculo->area_asignada }}</x-dato>
                <x-dato label="Responsable">{{ $vehiculo->responsable?->nombre_completo }}</x-dato>
                @if($vehiculo->observaciones)
                    <x-dato label="Observaciones" wide>{{ $vehiculo->observaciones }}</x-dato>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history card-header__icon" aria-hidden="true"></i> Historial de mantenimientos
                <span class="card-header__aside small text-muted">
                    {{ $resumenMantenimientos['completados'] }} de {{ $resumenMantenimientos['total'] }} completados · Gasto acumulado S/ {{ number_format($resumenMantenimientos['gasto'], 2) }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Descripción</th>
                            <th scope="col">Taller</th>
                            <th scope="col" class="text-end">Importe</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($mantenimientos as $m)
                        <tr class="{{ $m->esta_atrasado ? 'row-warning' : '' }}">
                            <td class="text-nowrap">
                                <a href="{{ route('parque.mantenimientos.show', $m) }}" data-modal>{{ ($m->fecha_realizado ?? $m->fecha_programada)->format('d/m/Y') }}</a>
                                <span class="cell-sub">{{ $m->fecha_realizado ? 'Ejecutado' : 'Programado' }}</span>
                            </td>
                            <td><x-estado :color="$m->tipo_badge">{{ $m->tipo_label }}</x-estado></td>
                            <td>{{ Str::limit($m->descripcion, 60) }}</td>
                            <td>{{ $m->taller ?? '—' }}</td>
                            <td class="text-end money">S/ {{ number_format($m->total_efectivo, 2) }}</td>
                            <td><x-estado :color="$m->estado_badge">{{ $m->estado_label }}</x-estado></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-vacio icono="tools" titulo="Sin mantenimientos" texto="Este vehículo aún no registra mantenimientos." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($mantenimientos->hasPages())
                <div class="paginacion">{{ $mantenimientos->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                @if($vehiculo->imagen)
                    <img src="{{ asset('storage/' . $vehiculo->imagen) }}" alt="Fotografía del vehículo {{ $vehiculo->placa }}" class="vehiculo-foto">
                @else
                    <div class="vehiculo-foto vehiculo-foto--vacia" role="img" aria-label="Sin fotografía"><i class="bi bi-truck-front" aria-hidden="true"></i></div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-file-earmark-medical card-header__icon" aria-hidden="true"></i> Documentación</div>
            <div class="card-body py-2">
                <dl class="lista-definicion">
                    @foreach(\App\Models\Vehiculo::DOCUMENTOS as $campo => $nombre)
                        <div><dt>{{ $nombre }}</dt><dd>@include('parque.partials.doc', ['campo' => $campo])</dd></div>
                    @endforeach
                </dl>
            </div>
        </div>

        @can('aprobar')
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-arrow-repeat card-header__icon" aria-hidden="true"></i> Cambiar estado</div>
            <div class="card-body">
                <form method="POST" action="{{ route('parque.vehiculos.cambiar-estado', $vehiculo) }}" class="d-flex gap-2">
                    @csrf @method('PATCH')
                    <label for="nuevo_estado" class="visually-hidden">Nuevo estado</label>
                    <select name="estado" id="nuevo_estado" class="form-select form-select-sm">
                        @foreach(\App\Models\Vehiculo::ESTADOS as $k => $v)
                            <option value="{{ $k }}" @selected($vehiculo->estado === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Actualizar</button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('parque.vehiculos.destroy', $vehiculo) }}"
              data-confirm="Se retirará el vehículo {{ $vehiculo->placa }} del inventario. Si el vehículo fue dado de baja, se recomienda cambiar su estado a «De baja» en lugar de eliminarlo."
              data-confirm-title="Eliminar vehículo" data-confirm-btn="Eliminar">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-link text-danger p-0 small"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar este vehículo</button>
        </form>
        @endcan

        <p class="small text-muted mt-3 mb-0">
            Registrado el {{ $vehiculo->created_at?->format('d/m/Y') }}{{ $vehiculo->creadoPor ? ' por ' . $vehiculo->creadoPor->nombre_completo : '' }}.
        </p>
    </div>
</div>

{{-- Análisis con inteligencia artificial (solo en la página completa) --}}
@unless(request()->header('X-Modal'))
<div class="modal fade" id="modalIA" tabindex="-1" aria-labelledby="modalIATitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalIATitulo"><i class="bi bi-stars me-2" aria-hidden="true"></i>Análisis del vehículo {{ $vehiculo->placa }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="iaResultado" aria-live="polite"></div>
            <div class="modal-footer">
                <span class="small text-muted me-auto"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Análisis referencial generado por IA. Verifique antes de tomar decisiones.</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endunless
@endsection

@push('scripts')
<script>
document.getElementById('modalIA').addEventListener('show.bs.modal', async () => {
    const destino = document.getElementById('iaResultado');
    destino.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted small">Analizando historial y documentación…</p></div>';
    try {
        const r = await fetch(@json(route('ia.analizar-vehiculo')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': ERP.csrf() },
            body: JSON.stringify({ vehiculo_id: {{ $vehiculo->id }} })
        });
        const d = await r.json();
        const p = document.createElement('div');
        p.style.whiteSpace = 'pre-line';
        p.style.lineHeight = '1.65';
        p.textContent = d.analisis || 'No se obtuvo respuesta.';
        destino.replaceChildren(p);
    } catch (e) {
        destino.innerHTML = '<div class="callout callout--danger"><i class="bi bi-x-octagon-fill"></i><div>No fue posible conectar con el servicio de IA.</div></div>';
    }
});
</script>
@endpush

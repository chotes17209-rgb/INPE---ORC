@extends('layouts.app')
@section('title', 'Mantenimiento ' . ($mantenimiento->vehiculo?->placa ?? ''))
@section('page-title', 'Mantenimiento ' . mb_strtolower($mantenimiento->tipo_label))
@section('page-subtitle', ($mantenimiento->vehiculo?->placa ?? '') . ' · ' . ($mantenimiento->vehiculo?->descripcion ?? '') . ' · Programado para el ' . $mantenimiento->fecha_programada->format('d/m/Y'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.index') }}">Mantenimientos</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detalle</li>
@endsection

@section('page-actions')
    <a href="{{ route('parque.vehiculos.show', $mantenimiento->vehiculo_id) }}" class="btn btn-outline-primary"><i class="bi bi-truck-front me-1" aria-hidden="true"></i>Ver vehículo</a>
    @if($mantenimiento->esta_abierto)
        @can('registrar')
            <a href="{{ route('parque.mantenimientos.edit', $mantenimiento) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
        @endcan
        @can('aprobar')
            <a href="{{ route('parque.mantenimientos.completar', $mantenimiento) }}" class="btn btn-success"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Registrar conformidad</a>
        @endcan
    @endif
@endsection

@section('content')
@if($mantenimiento->esta_atrasado)
    <div class="callout callout--danger mb-3"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Mantenimiento atrasado.</span> Estaba programado para el {{ $mantenimiento->fecha_programada->format('d/m/Y') }}. Registre su conformidad o reprograme la fecha.</div></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tools card-header__icon" aria-hidden="true"></i> Datos del mantenimiento
                <span class="card-header__aside"><x-estado :color="$mantenimiento->estado_badge">{{ $mantenimiento->estado_label }}</x-estado></span>
            </div>
            <div class="ficha">
                <x-dato label="Vehículo">{{ $mantenimiento->vehiculo?->placa }} — {{ $mantenimiento->vehiculo?->descripcion }}</x-dato>
                <x-dato label="Tipo"><x-estado :color="$mantenimiento->tipo_badge">{{ $mantenimiento->tipo_label }}</x-estado></x-dato>
                <x-dato label="Fecha programada">{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</x-dato>
                <x-dato label="Fecha de ejecución">{{ $mantenimiento->fecha_realizado?->format('d/m/Y') }}</x-dato>
                <x-dato label="Descripción del trabajo" wide>{{ $mantenimiento->descripcion }}</x-dato>
                <x-dato label="Taller / proveedor">{{ $mantenimiento->taller }}</x-dato>
                <x-dato label="Técnico responsable">{{ $mantenimiento->tecnico_responsable }}</x-dato>
                <x-dato label="Kilometraje">{{ $mantenimiento->kilometraje_mantenimiento ? number_format($mantenimiento->kilometraje_mantenimiento) . ' km' : '' }}</x-dato>
            </div>

            <h3 class="seccion-titulo">Orden de servicio y comprobante</h3>
            <div class="ficha">
                <x-dato label="Orden de servicio"><span class="font-monospace">{{ $mantenimiento->orden_servicio }}</span></x-dato>
                <x-dato label="Fecha de la O.S.">{{ $mantenimiento->fecha_orden_servicio?->format('d/m/Y') }}</x-dato>
                <x-dato label="Registro SIAF"><span class="font-monospace">{{ $mantenimiento->registro_siaf }}</span></x-dato>
                <x-dato label="Comprobante"><span class="font-monospace">{{ $mantenimiento->comprobante }}</span></x-dato>
                <x-dato label="Fecha del comprobante">{{ $mantenimiento->factura_fecha?->format('d/m/Y') }}</x-dato>
            </div>

            @if($mantenimiento->observaciones)
            <h3 class="seccion-titulo">Observaciones</h3>
            <div class="ficha">
                <x-dato label="Informe del trabajo" wide>{{ $mantenimiento->observaciones }}</x-dato>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-cash-stack card-header__icon" aria-hidden="true"></i> Costos</div>
            <div class="card-body">
                <dl class="lista-definicion">
                    <div><dt>Costo estimado</dt><dd class="money">S/ {{ number_format($mantenimiento->costo_estimado, 2) }}</dd></div>
                    <div><dt>Costo real</dt><dd class="money">{{ $mantenimiento->costo_real !== null ? 'S/ ' . number_format($mantenimiento->costo_real, 2) : 'Pendiente' }}</dd></div>
                    @if($mantenimiento->costo_real !== null && $mantenimiento->costo_estimado > 0)
                        @php $variacion = ($mantenimiento->costo_real - $mantenimiento->costo_estimado) / $mantenimiento->costo_estimado * 100; @endphp
                        <div><dt>Variación respecto a lo estimado</dt><dd class="{{ $variacion > 10 ? 'text-danger' : 'text-success' }}">{{ $variacion > 0 ? '+' : '' }}{{ number_format($variacion, 1) }} %</dd></div>
                    @endif
                </dl>
                <div class="total-box d-flex justify-content-between align-items-center mt-3">
                    <span class="total-box__label">{{ $mantenimiento->estado === 'completado' ? 'Total ejecutado' : 'Total estimado' }}</span>
                    <span class="total-box__value">S/ {{ number_format($mantenimiento->total_efectivo, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-signpost-split card-header__icon" aria-hidden="true"></i> Seguimiento</div>
            <div class="card-body">
                <ul class="tramite">
                    <li class="hecho">
                        <strong>Programación</strong>
                        <span class="tramite__fecha">{{ $mantenimiento->created_at->format('d/m/Y H:i') }} · {{ $mantenimiento->registradoPor?->nombre_completo ?? 'Sistema' }}</span>
                    </li>
                    <li class="{{ $mantenimiento->estado === 'completado' ? 'hecho' : 'actual' }}">
                        <strong>{{ $mantenimiento->estado === 'completado' ? 'Conformidad registrada' : $mantenimiento->estado_label }}</strong>
                        @if($mantenimiento->estado === 'completado')
                            <span class="tramite__fecha">{{ $mantenimiento->fecha_aprobacion?->format('d/m/Y H:i') }} · {{ $mantenimiento->aprobadoPor?->nombre_completo ?? '—' }}</span>
                        @else
                            <span class="tramite__fecha">Pendiente de conformidad</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        @if($mantenimiento->estado !== 'completado')
            @can('aprobar')
                <form method="POST" action="{{ route('parque.mantenimientos.destroy', $mantenimiento) }}" class="mt-3"
                      data-confirm="Se eliminará el mantenimiento programado. Si el vehículo no tiene otros mantenimientos abiertos, volverá a estar operativo."
                      data-confirm-title="Eliminar mantenimiento" data-confirm-btn="Eliminar">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-link text-danger p-0 small"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar este mantenimiento</button>
                </form>
            @endcan
        @endif
    </div>
</div>
@endsection

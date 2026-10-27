@extends('layouts.app')
@section('title', 'Historial ' . $vehiculo->placa)
@section('page-title', 'Historial de mantenimientos')
@section('page-subtitle', $vehiculo->placa . ' · ' . $vehiculo->descripcion)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.show', $vehiculo) }}">{{ $vehiculo->placa }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Historial</li>
@endsection
@section('page-actions')
    <a href="{{ route('parque.mantenimientos.reporte-pdf', ['vehiculo' => $vehiculo->placa]) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Exportar PDF</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Tipo</th>
                    <th scope="col">Descripción</th>
                    <th scope="col">F. programada</th>
                    <th scope="col">F. ejecución</th>
                    <th scope="col" class="text-end">Costo estimado</th>
                    <th scope="col" class="text-end">Costo real</th>
                    <th scope="col">Taller</th>
                    <th scope="col">Estado</th>
                </tr>
            </thead>
            <tbody>
            @forelse($mantenimientos as $m)
                <tr>
                    <td><x-estado :color="$m->tipo_badge">{{ $m->tipo_label }}</x-estado></td>
                    <td><a href="{{ route('parque.mantenimientos.show', $m) }}" data-modal>{{ Str::limit($m->descripcion, 60) }}</a></td>
                    <td>{{ $m->fecha_programada->format('d/m/Y') }}</td>
                    <td>{{ $m->fecha_realizado?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end money">S/ {{ number_format($m->costo_estimado, 2) }}</td>
                    <td class="text-end money">{{ $m->costo_real !== null ? 'S/ ' . number_format($m->costo_real, 2) : '—' }}</td>
                    <td>{{ $m->taller ?? '—' }}</td>
                    <td><x-estado :color="$m->estado_badge">{{ $m->estado_label }}</x-estado></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-vacio icono="clock-history" titulo="Sin historial" texto="Este vehículo aún no registra mantenimientos." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($mantenimientos->hasPages())
        <div class="paginacion">{{ $mantenimientos->links() }}</div>
    @endif
</div>
@endsection

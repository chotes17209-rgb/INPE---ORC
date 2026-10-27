@extends('layouts.app')
@section('title', 'Mantenimientos vehiculares')
@section('page-title', 'Parque automotor')
@section('page-subtitle', 'Mantenimientos: programación, conformidad y costo de los servicios de la flota.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Mantenimientos</li>
@endsection

@section('page-actions')
    @can('registrar')
        <a href="{{ route('parque.mantenimientos.create') }}" class="btn btn-primary btn-lg" data-modal><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Nuevo mantenimiento</a>
    @endcan
@endsection
@section('pestanas')@include('partials.pestanas', ['modulo' => 'parque'])@endsection

@section('content')
@php $filtrado = request()->anyFilled(['vehiculo', 'estado', 'tipo_mantenimiento', 'fecha_desde', 'fecha_hasta']); @endphp

<div class="kpi-grid">
    <x-kpi color="warning" icono="calendar-event" label="Programados" :valor="$resumen['programados']" :href="route('parque.mantenimientos.index', ['estado' => 'programado'])" detalle="Pendientes de ejecución" />
    <x-kpi color="info" icono="gear-wide-connected" label="En proceso" :valor="$resumen['en_proceso']" :href="route('parque.mantenimientos.index', ['estado' => 'en_proceso'])" detalle="Vehículos en taller" />
    <x-kpi :color="$resumen['atrasados'] ? 'danger' : 'success'" icono="exclamation-triangle" label="Atrasados" :valor="$resumen['atrasados']" detalle="Fecha programada vencida" />
    <x-kpi color="primary" icono="cash-stack" label="Gasto ejecutado {{ date('Y') }}" :valor="'S/ ' . number_format($resumen['gasto_anio'], 2)" detalle="Mantenimientos con conformidad" />
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul card-header__icon" aria-hidden="true"></i> Mantenimientos
        <span class="badge text-bg-light border">{{ $mantenimientos->total() }}</span>
        <span class="card-header__aside">
            <a href="{{ route('parque.mantenimientos.reporte-pdf', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>PDF</a>
            <a href="{{ route('parque.mantenimientos.reporte-excel', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-spreadsheet text-success me-1" aria-hidden="true"></i>Excel</a>
        </span>
    </div>

    <form method="GET" class="filter-bar" role="search" aria-label="Filtrar mantenimientos">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_vehiculo">Placa</label>
                <input type="search" name="vehiculo" id="f_vehiculo" value="{{ request('vehiculo') }}" class="form-control form-control-sm" placeholder="ABC-123">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_estado">Estado</label>
                <select name="estado" id="f_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\VehiculoMantenimiento::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_tipo">Tipo</label>
                <select name="tipo_mantenimiento" id="f_tipo" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\VehiculoMantenimiento::TIPOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('tipo_mantenimiento') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_desde">Programado desde</label>
                <input type="date" name="fecha_desde" id="f_desde" value="{{ request('fecha_desde') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_hasta">Hasta</label>
                <input type="date" name="fecha_hasta" id="f_hasta" value="{{ request('fecha_hasta') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                @if($filtrado)
                    <a href="{{ route('parque.mantenimientos.index') }}" class="btn btn-sm btn-outline-secondary" title="Quitar filtros" aria-label="Quitar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Vehículo</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Trabajo</th>
                    <th scope="col">Programado</th>
                    <th scope="col" class="text-end">Importe</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($mantenimientos as $m)
                <tr class="{{ $m->esta_atrasado ? 'row-alert' : '' }}">
                    <td>
                        <a href="{{ route('parque.vehiculos.show', $m->vehiculo_id) }}" class="codigo" data-modal>{{ $m->vehiculo?->placa }}</a>
                        <span class="cell-sub">{{ $m->vehiculo?->descripcion }}</span>
                    </td>
                    <td><x-estado :color="$m->tipo_badge">{{ $m->tipo_label }}</x-estado></td>
                    <td>
                        {{ Str::limit($m->descripcion, 50) }}
                        @if($m->taller)<span class="cell-sub">{{ $m->taller }}</span>@endif
                    </td>
                    <td class="text-nowrap">
                        {{ $m->fecha_programada->format('d/m/Y') }}
                        @if($m->esta_atrasado)
                            <span class="cell-sub text-danger fw-semibold">Atrasado {{ (int) $m->fecha_programada->diffInDays(today()) }} día(s)</span>
                        @elseif($m->fecha_realizado)
                            <span class="cell-sub">Ejecutado {{ $m->fecha_realizado->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td class="text-end money">
                        <span class="fw-semibold">S/ {{ number_format($m->total_efectivo, 2) }}</span>
                        @if($m->estado !== 'completado')<span class="cell-sub">Estimado</span>@endif
                    </td>
                    <td><x-estado :color="$m->estado_badge">{{ $m->estado_label }}</x-estado></td>
                    <td>
                        <div class="table-actions">
                            <x-accion tipo="ver" :href="route('parque.mantenimientos.show', $m)" :nombre="'del mantenimiento de ' . $m->vehiculo?->placa" texto="Ver" />
                            @if($m->esta_abierto)
                                @can('registrar')
                                    <x-accion tipo="editar" :href="route('parque.mantenimientos.edit', $m)" />
                                @endcan
                                @can('aprobar')
                                    <x-accion tipo="completar" :href="route('parque.mantenimientos.completar', $m)" />
                                    <x-accion tipo="eliminar" :href="route('parque.mantenimientos.destroy', $m)"
                                              confirmar="Se eliminará el mantenimiento programado del vehículo {{ $m->vehiculo?->placa }}." />
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-vacio icono="tools" titulo="No se encontraron mantenimientos"
                                 :texto="$filtrado ? 'Pruebe con otros criterios de búsqueda.' : 'Aún no se ha programado ningún mantenimiento.'">
                            @can('registrar')
                                <a href="{{ route('parque.mantenimientos.create') }}" class="btn btn-primary" data-modal><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Programar mantenimiento</a>
                            @endcan
                        </x-vacio>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($mantenimientos->hasPages())
        <div class="paginacion">{{ $mantenimientos->links() }}</div>
    @endif
</div>
@endsection

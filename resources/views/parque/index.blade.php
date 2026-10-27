@extends('layouts.app')
@section('title', 'Parque automotor')
@section('page-title', 'Parque automotor')
@section('page-subtitle', 'Vehículos de la flota: datos, documentación vigente y estado.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Parque automotor</li>@endsection

@section('page-actions')
    @can('registrar')
        <a href="{{ route('parque.vehiculos.create') }}" class="btn btn-primary btn-lg" data-modal><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Nuevo vehículo</a>
    @endcan
@endsection
@section('pestanas')@include('partials.pestanas', ['modulo' => 'parque'])@endsection

@section('content')
@php $filtrado = request()->anyFilled(['buscar', 'estado', 'tipo', 'combustible']); @endphp

<div class="kpi-grid">
    <x-kpi color="primary" icono="truck-front" label="Vehículos registrados" :valor="$stats['total']" detalle="Total de la flota" />
    <x-kpi color="success" icono="check2-circle" label="Disponibles para servicio" :valor="$stats['operativos']" detalle="Operativos, disponibles o asignados" />
    <x-kpi color="warning" icono="tools" label="En mantenimiento" :valor="$stats['mantenimiento']"
           :href="route('parque.vehiculos.index', ['estado' => 'mantenimiento'])" detalle="Fuera de servicio temporalmente" />
    <x-kpi :color="$stats['docs_vencidos'] ? 'danger' : 'info'" icono="file-earmark-medical" label="Documentos vencidos" :valor="$stats['docs_vencidos']"
           :detalle="$stats['docs_por_vencer'] . ' vehículo(s) con SOAT o revisión por vencer'" />
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul card-header__icon" aria-hidden="true"></i> Flota vehicular
        <span class="badge text-bg-light border">{{ $vehiculos->total() }}</span>
        <span class="card-header__aside">
            <a href="{{ route('parque.reporte-pdf', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>PDF</a>
            <a href="{{ route('parque.reporte-excel', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-spreadsheet text-success me-1" aria-hidden="true"></i>Excel</a>
        </span>
    </div>

    <form method="GET" class="filter-bar" role="search" aria-label="Filtrar vehículos">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="f_buscar">Buscar</label>
                <input type="search" name="buscar" id="f_buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="Placa, marca o modelo">
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label" for="f_estado">Estado</label>
                <select name="estado" id="f_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\Vehiculo::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label" for="f_tipo">Clase</label>
                <select name="tipo" id="f_tipo" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todas</option>
                    @foreach(\App\Models\Vehiculo::TIPOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label" for="f_comb">Combustible</label>
                <select name="combustible" id="f_comb" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\Vehiculo::COMBUSTIBLES as $k => $v)
                        <option value="{{ $k }}" @selected(request('combustible') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                @if($filtrado)
                    <a href="{{ route('parque.vehiculos.index') }}" class="btn btn-sm btn-outline-secondary" title="Quitar filtros" aria-label="Quitar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Placa</th>
                    <th scope="col">Vehículo</th>
                    <th scope="col" class="text-end">Kilometraje</th>
                    <th scope="col">Documentos</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($vehiculos as $v)
                @php $alerta = in_array('vencido', [$v->situacionDocumento('fecha_soat'), $v->situacionDocumento('fecha_revision_tecnica')]) && $v->estado !== 'baja'; @endphp
                <tr class="{{ $alerta ? 'row-alert' : '' }}">
                    <td><a href="{{ route('parque.vehiculos.show', $v) }}" class="codigo" data-modal>{{ $v->placa }}</a></td>
                    <td>
                        <span class="cell-main">{{ $v->descripcion }}</span>
                        <span class="cell-sub">{{ $v->tipo_label }} · {{ $v->anio_fabricacion }}{{ $v->color ? ' · ' . $v->color : '' }}</span>
                    </td>
                    <td class="text-end money">{{ number_format($v->kilometraje_actual) }} km</td>
                    <td class="text-nowrap">
                        <span class="d-block"><span class="text-muted">SOAT:</span> @include('parque.partials.doc', ['vehiculo' => $v, 'campo' => 'fecha_soat'])</span>
                        <span class="d-block mt-1"><span class="text-muted">Rev. técnica:</span> @include('parque.partials.doc', ['vehiculo' => $v, 'campo' => 'fecha_revision_tecnica'])</span>
                    </td>
                    <td>
                        <x-estado :color="$v->estado_color">{{ $v->estado_label }}</x-estado>
                        @if($v->mantenimientos_abiertos_count)
                            <span class="cell-sub">{{ $v->mantenimientos_abiertos_count }} mant. abierto(s)</span>
                        @endif
                    </td>
                    <td>
                        <div class="table-actions">
                            <x-accion tipo="ver" :href="route('parque.vehiculos.show', $v)" :nombre="$v->placa" />
                            @can('registrar')
                                <x-accion tipo="editar" :href="route('parque.vehiculos.edit', $v)" :nombre="$v->placa" />
                                @if($v->estado !== 'baja')
                                    <x-accion tipo="mantenimiento" :href="route('parque.mantenimientos.create', ['vehiculo_id' => $v->id])" :nombre="$v->placa" />
                                @endif
                            @endcan
                            @can('aprobar')
                                <x-accion tipo="eliminar" :href="route('parque.vehiculos.destroy', $v)" :nombre="$v->placa"
                                          confirmar="Se retirará el vehículo {{ $v->placa }} del inventario. Si fue dado de baja, se recomienda cambiar su estado en lugar de eliminarlo." />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-vacio icono="truck-front" titulo="No se encontraron vehículos"
                                 :texto="$filtrado ? 'Pruebe con otros criterios de búsqueda.' : 'Aún no se ha registrado ningún vehículo.'">
                            @can('registrar')
                                <a href="{{ route('parque.vehiculos.create') }}" class="btn btn-primary" data-modal><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Registrar vehículo</a>
                            @endcan
                        </x-vacio>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($vehiculos->hasPages())
        <div class="paginacion">{{ $vehiculos->links() }}</div>
    @endif
</div>
@endsection

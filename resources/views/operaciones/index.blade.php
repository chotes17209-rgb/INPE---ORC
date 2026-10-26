@extends('layouts.app')
@section('title', 'Operaciones recíprocas')
@section('page-title', 'Operaciones recíprocas')
@section('page-subtitle', 'Pagos de servicios básicos, contratos y convenios con otras entidades.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Operaciones recíprocas</li>@endsection

@section('page-actions')
    <a href="{{ route('operaciones.resumen-mensual') }}" class="btn btn-outline-primary"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Resumen mensual</a>
    @can('registrar')
        <a href="{{ route('operaciones.registros.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva operación</a>
    @endcan
@endsection

@section('content')
@php $filtrado = request()->anyFilled(['buscar', 'tipo_servicio', 'estado', 'mes', 'anio']); @endphp

<div class="kpi-grid">
    <x-kpi color="warning" icono="hourglass-split" label="Por pagar"
           :valor="'S/ ' . number_format($resumen['total_pendiente'], 2)" :detalle="$resumen['count_pendiente'] . ' operación(es) en trámite'"
           :href="route('operaciones.registros.index', ['estado' => 'pendiente'])" />
    <x-kpi color="danger" icono="exclamation-octagon" label="Vencido sin pago"
           :valor="'S/ ' . number_format($resumen['total_vencido'], 2)" :detalle="$resumen['count_vencido'] . ' operación(es) vencida(s)'"
           :href="route('operaciones.registros.index', ['estado' => 'vencido'])" />
    <x-kpi color="success" icono="check2-circle" label="Pagado en {{ date('Y') }}"
           :valor="'S/ ' . number_format($resumen['total_pagado'], 2)" detalle="Pagos registrados en el ejercicio"
           :href="route('operaciones.registros.index', ['estado' => 'pagado'])" />
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul card-header__icon" aria-hidden="true"></i> Registros
        <span class="badge text-bg-light border">{{ $operaciones->total() }}</span>
        <span class="card-header__aside">
            <a href="{{ route('operaciones.reporte-pdf', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>PDF</a>
            <a href="{{ route('operaciones.reporte-excel', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-spreadsheet text-success me-1" aria-hidden="true"></i>Excel</a>
        </span>
    </div>

    <form method="GET" class="filter-bar" role="search" aria-label="Filtrar operaciones">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="f_buscar">Buscar</label>
                <input type="search" name="buscar" id="f_buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="N° de operación, entidad, RUC, comprobante o contrato">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_tipo">Servicio</label>
                <select name="tipo_servicio" id="f_tipo" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    <optgroup label="Servicios básicos">
                        @foreach(\App\Models\OperacionReciproca::SERVICIOS_BASICOS as $k => $v)
                            <option value="{{ $k }}" @selected(request('tipo_servicio') === $k)>{{ $v }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Otros pagos">
                        @foreach(\App\Models\OperacionReciproca::OTROS_PAGOS as $k => $v)
                            <option value="{{ $k }}" @selected(request('tipo_servicio') === $k)>{{ $v }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_estado">Estado</label>
                <select name="estado" id="f_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\OperacionReciproca::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label" for="f_mes">Periodo</label>
                <select name="mes" id="f_mes" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Mes</option>
                    @foreach(\App\Support\Catalogo::MESES as $n => $mes)
                        <option value="{{ $n }}" @selected((int) request('mes') === $n)>{{ $mes }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label" for="f_anio">Año</label>
                <input type="number" name="anio" id="f_anio" value="{{ request('anio') }}" class="form-control form-control-sm" min="2020" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
            </div>
            <div class="col-lg-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                @if($filtrado)
                    <a href="{{ route('operaciones.registros.index') }}" class="btn btn-sm btn-outline-secondary" title="Quitar filtros" aria-label="Quitar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">N° operación</th>
                    <th scope="col">Servicio</th>
                    <th scope="col">Entidad proveedora</th>
                    <th scope="col">Comprobante</th>
                    <th scope="col">Periodo</th>
                    <th scope="col">Vencimiento</th>
                    <th scope="col" class="text-end">Importe</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($operaciones as $op)
                <tr class="{{ $op->estado === 'vencido' ? 'row-alert' : '' }}">
                    <td><a href="{{ route('operaciones.registros.show', $op) }}" class="codigo">{{ $op->numero_operacion }}</a></td>
                    <td>
                        <span class="cell-main">{{ $op->tipo_servicio_label }}</span>
                        <span class="cell-sub">{{ $op->grupo_servicio }}</span>
                    </td>
                    <td>
                        <span class="cell-main">{{ Str::limit($op->entidad_proveedora, 34) }}</span>
                        <span class="cell-sub">RUC {{ $op->ruc_entidad ?? '—' }}</span>
                    </td>
                    <td class="text-nowrap">
                        {{ $op->comprobante ?? '—' }}
                        <span class="cell-sub">Emitido {{ $op->fecha_emision->format('d/m/Y') }}</span>
                    </td>
                    <td class="text-nowrap">{{ $op->periodo }}</td>
                    <td class="text-nowrap">
                        <span class="{{ $op->esta_vencida ? 'text-danger fw-semibold' : '' }}">{{ $op->fecha_vencimiento->format('d/m/Y') }}</span>
                        @if($op->estado !== 'pagado' && ! $op->esta_vencida)
                            <span class="cell-sub">{{ $op->fecha_vencimiento->isToday() ? 'Vence hoy' : 'en ' . (int) today()->diffInDays($op->fecha_vencimiento) . ' día(s)' }}</span>
                        @endif
                    </td>
                    <td class="text-end money fw-semibold">S/ {{ number_format($op->total, 2) }}</td>
                    <td><x-estado :color="$op->estado_badge">{{ $op->estado_label }}</x-estado></td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('operaciones.registros.show', $op) }}" class="btn btn-sm btn-light btn-icon" title="Ver detalle" aria-label="Ver detalle de {{ $op->numero_operacion }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @if($op->es_editable)
                                @can('registrar')
                                    <a href="{{ route('operaciones.registros.edit', $op) }}" class="btn btn-sm btn-light btn-icon" title="Editar" aria-label="Editar {{ $op->numero_operacion }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @endcan
                            @endif
                            @if($op->puede_pagarse)
                                @can('aprobar')
                                    <button type="button" class="btn btn-sm btn-outline-success btn-icon" title="Registrar pago" aria-label="Registrar pago de {{ $op->numero_operacion }}"
                                            data-pago-url="{{ route('operaciones.registros.aprobar', $op) }}" data-pago-codigo="{{ $op->numero_operacion }}"
                                            data-pago-detalle="{{ $op->entidad_proveedora }} — {{ $op->tipo_servicio_label }}" data-pago-total="{{ $op->total }}">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-vacio icono="receipt" titulo="No se encontraron operaciones"
                                 :texto="$filtrado ? 'Pruebe con otros criterios de búsqueda.' : 'Aún no se ha registrado ninguna operación recíproca.'">
                            @can('registrar')
                                <a href="{{ route('operaciones.registros.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Registrar operación</a>
                            @endcan
                        </x-vacio>
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($operaciones->count())
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end">Subtotal de la página</td>
                    <td class="text-end money">S/ {{ number_format($operaciones->sum('total'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($operaciones->hasPages())
        <div class="paginacion">{{ $operaciones->links() }}</div>
    @endif
</div>

@can('aprobar')
    @include('partials.modal-pago')
@endcan
@endsection

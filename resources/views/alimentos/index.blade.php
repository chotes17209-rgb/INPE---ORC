@extends('layouts.app')
@section('title', 'Suministro de alimentos')
@section('page-title', 'Suministro de alimentos')
@section('page-subtitle', 'Comprobantes de los contratos de alimentación de los establecimientos penitenciarios.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Suministro de alimentos</li>@endsection

@section('page-actions')
    @can('registrar')
        <a href="{{ route('alimentos.registros.create') }}" class="btn btn-primary btn-lg" data-modal><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Nuevo registro</a>
    @endcan
@endsection
@section('pestanas')@include('partials.pestanas', ['modulo' => 'alimentos'])@endsection

@section('content')
<div class="kpi-grid">
    <x-kpi color="warning" icono="hourglass-split" label="Por pagar"
           :valor="'S/ ' . number_format($resumen['total_pendiente'], 2)" :detalle="$resumen['count_pendiente'] . ' registro(s) en trámite'"
           :href="route('alimentos.registros.index', ['estado' => 'pendiente'])" />
    <x-kpi color="danger" icono="exclamation-octagon" label="Vencido sin pago"
           :valor="'S/ ' . number_format($resumen['total_vencido'], 2)" :detalle="$resumen['count_vencido'] . ' registro(s) con periodo concluido'"
           :href="route('alimentos.registros.index', ['estado' => 'vencido'])" />
    <x-kpi color="success" icono="check2-circle" label="Pagado en {{ date('Y') }}"
           :valor="'S/ ' . number_format($resumen['total_pagado'], 2)" detalle="Pagos registrados en el ejercicio"
           :href="route('alimentos.registros.index', ['estado' => 'pagado', 'anio' => date('Y')])" />
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul card-header__icon" aria-hidden="true"></i> Registros
        <span class="badge text-bg-light border">{{ $registros->total() }}</span>
        <span class="card-header__aside">
            <a href="{{ route('alimentos.reporte-pdf', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>PDF</a>
            <a href="{{ route('alimentos.reporte-excel', request()->query()) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-spreadsheet text-success me-1" aria-hidden="true"></i>Excel</a>
        </span>
    </div>

    <form method="GET" class="filter-bar" role="search" aria-label="Filtrar registros">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="f_buscar">Buscar</label>
                <input type="search" name="buscar" id="f_buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="N° de registro, proveedor, RUC, contrato u O.S.">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_penal">Establecimiento</label>
                <select name="penal" id="f_penal" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\Alimento::PENALES as $k => $v)
                        <option value="{{ $k }}" @selected(request('penal') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_estado">Estado</label>
                <select name="estado" id="f_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\Alimento::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label" for="f_mes">Mes</label>
                <select name="mes" id="f_mes" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
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
                @if(request()->anyFilled(['buscar', 'penal', 'estado', 'mes', 'anio']))
                    <a href="{{ route('alimentos.registros.index') }}" class="btn btn-sm btn-outline-secondary" title="Quitar filtros" aria-label="Quitar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">N° registro</th>
                    <th scope="col">Proveedor</th>
                    <th scope="col">Establecimiento y periodo</th>
                    <th scope="col" class="text-end">Importe</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($registros as $r)
                <tr class="{{ $r->estado === 'vencido' ? 'row-alert' : '' }}">
                    <td><a href="{{ route('alimentos.registros.show', $r) }}" class="codigo" data-modal>{{ $r->numero_registro }}</a></td>
                    <td class="cell-proveedor">
                        <span class="cell-main">{{ Str::limit($r->proveedor_razon_social, 45) }}</span>
                        <span class="cell-sub">RUC {{ $r->proveedor_ruc ?? '—' }}@if($r->numero_contrato) · {{ $r->numero_contrato }}@endif</span>
                    </td>
                    <td class="text-nowrap">
                        <span class="etiqueta mb-1">{{ $r->penal_label }}</span><br>
                        {{ $r->periodo_inicio->format('d/m/Y') }}
                        <span class="cell-sub {{ $r->estado === 'vencido' ? 'text-danger fw-semibold' : '' }}">al {{ $r->periodo_fin->format('d/m/Y') }}</span>
                    </td>
                    <td class="text-end money fw-semibold">S/ {{ number_format($r->total, 2) }}</td>
                    <td><x-estado :color="$r->estado_badge">{{ $r->estado_label }}</x-estado></td>
                    <td>
                        <div class="table-actions">
                            <x-accion tipo="ver" :href="route('alimentos.registros.show', $r)" :nombre="$r->numero_registro" />
                            @if($r->es_editable)
                                @can('registrar')
                                    <x-accion tipo="editar" :href="route('alimentos.registros.edit', $r)" :nombre="$r->numero_registro" />
                                @endcan
                            @endif
                            @if($r->puede_pagarse)
                                @can('aprobar')
                                    <x-accion tipo="pagar" :nombre="$r->numero_registro"
                                              data-pago-url="{{ route('alimentos.registros.aprobar', $r) }}" data-pago-codigo="{{ $r->numero_registro }}"
                                              data-pago-detalle="{{ $r->proveedor_razon_social }}" data-pago-total="{{ $r->total }}" />
                                @endcan
                            @endif
                            @if($r->es_editable)
                                @can('aprobar')
                                    <x-accion tipo="eliminar" :href="route('alimentos.registros.destroy', $r)" :nombre="$r->numero_registro"
                                              confirmar="Se eliminará el registro {{ $r->numero_registro }} de {{ $r->proveedor_razon_social }}. Esta acción quedará registrada en la auditoría." />
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-vacio icono="basket3" titulo="No se encontraron registros"
                                 :texto="request()->anyFilled(['buscar', 'penal', 'estado', 'mes', 'anio']) ? 'Pruebe con otros criterios de búsqueda.' : 'Aún no se ha registrado ningún suministro de alimentos.'">
                            @can('registrar')
                                <a href="{{ route('alimentos.registros.create') }}" class="btn btn-primary" data-modal><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Registrar suministro</a>
                            @endcan
                        </x-vacio>
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($registros->count())
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal de la página</td>
                    <td class="text-end money">S/ {{ number_format($registros->sum('total'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($registros->hasPages())
        <div class="paginacion">{{ $registros->links() }}</div>
    @endif
</div>
@endsection

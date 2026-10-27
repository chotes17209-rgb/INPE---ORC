@extends('layouts.app')
@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Consolidado del gasto por módulo y por proveedor — ejercicio ' . $anio . '.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Reportes</li>@endsection

@section('pestanas')@include('partials.pestanas', ['modulo' => 'reportes'])@endsection
@section('page-actions')
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-download me-1" aria-hidden="true"></i>Reporte general</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('reportes.general-pdf', ['anio' => $anio]) }}"><i class="bi bi-file-earmark-pdf text-danger me-2" aria-hidden="true"></i>Descargar PDF</a></li>
            <li><a class="dropdown-item" href="{{ route('reportes.general-excel', ['anio' => $anio]) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2" aria-hidden="true"></i>Descargar Excel (CSV)</a></li>
        </ul>
    </div>
@endsection

@section('content')
<div class="card mb-3">
    <form method="GET" class="filter-bar rounded" aria-label="Filtros del reporte">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label" for="r_anio">Ejercicio</label>
                <input type="number" name="anio" id="r_anio" value="{{ $anio }}" class="form-control form-control-sm" min="2020" max="{{ date('Y') + 1 }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="r_penal">Establecimiento (alimentos)</label>
                <select name="penal" id="r_penal" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\Alimento::PENALES as $k => $v)
                        <option value="{{ $k }}" @selected($penal === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Aplicar</button>
            </div>
        </div>
    </form>
</div>

<div class="kpi-grid">
    <x-kpi color="primary" icono="tools" label="Mantenimiento vehicular" :valor="'S/ ' . number_format($totales['mantenimiento'], 2)" detalle="Mantenimientos con conformidad" />
    <x-kpi color="gold" icono="basket3" label="Suministro de alimentos" :valor="'S/ ' . number_format($totales['alimentos'], 2)" detalle="Comprobantes emitidos en el ejercicio" />
    <x-kpi color="info" icono="lightning-charge" label="Servicios básicos" :valor="'S/ ' . number_format($totales['serv_basicos'], 2)" detalle="Energía, agua, telefonía e internet" />
    <x-kpi color="success" icono="file-earmark-text" label="Otros pagos y contratos" :valor="'S/ ' . number_format($totales['otros_pagos'], 2)" detalle="Alquileres, convenios y otros" />
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart card-header__icon" aria-hidden="true"></i> Gasto mensual por módulo
                <span class="card-header__aside d-none d-md-flex gap-3">
                    <span class="leyenda" style="--c:#1F1F1F">Mantenimiento</span>
                    <span class="leyenda" style="--c:#E0AE0B">Alimentos</span>
                    <span class="leyenda" style="--c:#0C9044">Servicios básicos</span>
                </span>
            </div>
            <div class="card-body"><canvas id="graficoMensual" height="120" role="img" aria-label="Gasto mensual por módulo"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-download card-header__icon" aria-hidden="true"></i> Reportes por módulo</div>
            <div class="list-group list-group-flush">
                @foreach([
                    ['Parque automotor', 'truck-front', 'parque.reporte-pdf', 'parque.reporte-excel', []],
                    ['Mantenimientos', 'tools', 'parque.mantenimientos.reporte-pdf', 'parque.mantenimientos.reporte-excel', ['fecha_desde' => $anio . '-01-01', 'fecha_hasta' => $anio . '-12-31']],
                    ['Suministro de alimentos', 'basket3', 'alimentos.reporte-pdf', 'alimentos.reporte-excel', array_filter(['anio' => $anio, 'penal' => $penal])],
                    ['Operaciones recíprocas', 'receipt', 'operaciones.reporte-pdf', 'operaciones.reporte-excel', ['anio' => $anio]],
                ] as [$nombre, $icono, $pdf, $excel, $params])
                <div class="list-group-item d-flex align-items-center gap-2">
                    <i class="bi bi-{{ $icono }} text-primary" aria-hidden="true"></i>
                    <span class="flex-grow-1 small fw-semibold">{{ $nombre }}</span>
                    <a href="{{ route($pdf, $params) }}" class="btn btn-sm btn-light" title="PDF" aria-label="{{ $nombre }} en PDF"><i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i></a>
                    <a href="{{ route($excel, $params) }}" class="btn btn-sm btn-light" title="Excel" aria-label="{{ $nombre }} en Excel"><i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i></a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-table card-header__icon" aria-hidden="true"></i> Detalle mensual {{ $anio }}</div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Mes</th>
                    <th scope="col" class="text-end">Mantenimiento</th>
                    <th scope="col" class="text-end">Alimentos</th>
                    <th scope="col" class="text-end">Servicios básicos</th>
                    <th scope="col" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach(\App\Support\Catalogo::MESES as $m => $mes)
                @php
                    $tm = $mantenimientoMensual[$m]['total'];
                    $ta = $alimentosMensual[$m]['total'];
                    $ts = $serviciosMensual[$m]['total'];
                    $tt = $tm + $ta + $ts;
                @endphp
                <tr class="{{ $tt == 0 ? 'text-muted' : '' }}">
                    <th scope="row" class="fw-semibold">{{ $mes }}</th>
                    <td class="text-end money">{{ $tm > 0 ? 'S/ ' . number_format($tm, 2) : '—' }}</td>
                    <td class="text-end money">{{ $ta > 0 ? 'S/ ' . number_format($ta, 2) : '—' }}</td>
                    <td class="text-end money">{{ $ts > 0 ? 'S/ ' . number_format($ts, 2) : '—' }}</td>
                    <td class="text-end money fw-semibold">{{ $tt > 0 ? 'S/ ' . number_format($tt, 2) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Total {{ $anio }}</th>
                    <td class="text-end money">S/ {{ number_format(array_sum(array_column($mantenimientoMensual, 'total')), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format(array_sum(array_column($alimentosMensual, 'total')), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format(array_sum(array_column($serviciosMensual, 'total')), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($totales['mantenimiento'] + $totales['alimentos'] + $totales['serv_basicos'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-basket3 card-header__icon" aria-hidden="true"></i> Suministro de alimentos por proveedor</div>
            <div class="table-responsive scroll-panel">
                <table class="table table-hover align-middle">
                    <thead><tr><th scope="col">Proveedor</th><th scope="col">Establecimiento</th><th scope="col" class="text-end">Registrado</th><th scope="col" class="text-end">Pagado</th></tr></thead>
                    <tbody>
                    @forelse($alimentosPorProveedor as $p)
                        <tr>
                            <td>
                                <span class="cell-main">{{ Str::limit($p->proveedor_razon_social, 40) }}</span>
                                <span class="cell-sub">RUC {{ $p->proveedor_ruc ?? '—' }}{{ $p->numero_contrato ? ' · ' . $p->numero_contrato : '' }}</span>
                            </td>
                            <td><span class="etiqueta">{{ \App\Models\Alimento::PENALES[$p->penal] ?? '—' }}</span></td>
                            <td class="text-end money">S/ {{ number_format($p->total_registrado, 2) }}</td>
                            <td class="text-end money text-success">S/ {{ number_format($p->total_pagado, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-vacio icono="basket3" titulo="Sin información para el ejercicio" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-building card-header__icon" aria-hidden="true"></i> Operaciones recíprocas por entidad</div>
            <div class="scroll-panel">
                @forelse($pagoPorProveedor as $p)
                    @php $avance = $p->total_registrado > 0 ? round($p->total_pagado / $p->total_registrado * 100) : 0; @endphp
                    <div class="list-row d-block">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="list-row__title" title="{{ $p->entidad_proveedora }}">{{ $p->entidad_proveedora }}</span>
                            <span class="small fw-semibold">{{ $avance }}% pagado</span>
                        </div>
                        <div class="progress my-1" role="progressbar" aria-valuenow="{{ $avance }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de pago">
                            <div class="progress-bar {{ $avance >= 100 ? 'bg-success' : '' }}" style="width: {{ $avance }}%"></div>
                        </div>
                        <div class="list-row__sub d-flex justify-content-between">
                            <span>S/ {{ number_format($p->total_pagado, 2) }} de S/ {{ number_format($p->total_registrado, 2) }}</span>
                            <span>{{ $p->numero_contrato ?: ($p->ruc_entidad ? 'RUC ' . $p->ruc_entidad : '') }}</span>
                        </div>
                    </div>
                @empty
                    <x-vacio icono="building" titulo="Sin información para el ejercicio" />
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('vendor-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    new Chart(document.getElementById('graficoMensual'), {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [
                { label: 'Mantenimiento',     data: @json(array_values(array_column($mantenimientoMensual, 'total'))), backgroundColor: '#1F1F1F', borderRadius: 3, maxBarThickness: 22 },
                { label: 'Alimentos',         data: @json(array_values(array_column($alimentosMensual, 'total'))),     backgroundColor: '#E0AE0B', borderRadius: 3, maxBarThickness: 22 },
                { label: 'Servicios básicos', data: @json(array_values(array_column($serviciosMensual, 'total'))),     backgroundColor: '#0C9044', borderRadius: 3, maxBarThickness: 22 },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + Number(v).toLocaleString('es-PE') } }, x: { grid: { display: false } } }
        }
    });
});
</script>
@endpush

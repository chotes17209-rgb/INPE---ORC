@extends('layouts.app')
@section('title', 'Panel de control')
@section('page-title', 'Panel de control')
@section('page-subtitle', 'Resumen de la gestión contable del ejercicio ' . $anio . ' — ' . config('inpe.oficina'))
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Panel de control</li>@endsection

@section('page-actions')
    <a href="{{ route('reportes.index') }}" class="btn btn-outline-primary"><i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>Ver reportes</a>
    @can('registrar')
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo registro
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('operaciones.registros.create') }}"><i class="bi bi-receipt me-2" aria-hidden="true"></i>Operación recíproca</a></li>
            <li><a class="dropdown-item" href="{{ route('alimentos.registros.create') }}"><i class="bi bi-basket3 me-2" aria-hidden="true"></i>Suministro de alimentos</a></li>
            <li><a class="dropdown-item" href="{{ route('parque.mantenimientos.create') }}"><i class="bi bi-tools me-2" aria-hidden="true"></i>Mantenimiento vehicular</a></li>
            <li><a class="dropdown-item" href="{{ route('parque.vehiculos.create') }}"><i class="bi bi-truck-front me-2" aria-hidden="true"></i>Vehículo</a></li>
        </ul>
    </div>
    @endcan
@endsection

@section('content')

{{-- ── Alertas que requieren atención ── --}}
@php
    $alertas = collect([
        $porPagar['operaciones_vencidas'] ? ['danger', 'clock-history', "{$porPagar['operaciones_vencidas']} operación(es) recíproca(s) vencida(s) sin pago", route('operaciones.registros.index', ['estado' => 'vencido'])] : null,
        $porPagar['alimentos_vencidos'] ? ['danger', 'basket3', "{$porPagar['alimentos_vencidos']} registro(s) de alimentos con periodo vencido sin pago", route('alimentos.registros.index', ['estado' => 'vencido'])] : null,
        $mantVencidos->count() ? ['danger', 'tools', "{$mantVencidos->count()} mantenimiento(s) con fecha programada vencida", route('parque.mantenimientos.index', ['estado' => 'programado'])] : null,
        $soatsVencidos ? ['danger', 'file-earmark-x', "{$soatsVencidos} vehículo(s) operativo(s) con SOAT vencido", route('parque.vehiculos.index')] : null,
        $soatsPorVencer ? ['warning', 'file-earmark-medical', "{$soatsPorVencer} SOAT por vencer en los próximos " . config('inpe.dias_alerta.documentos_vehiculo') . ' días', route('parque.vehiculos.index')] : null,
        $revisionesPorVencer ? ['warning', 'clipboard-check', "{$revisionesPorVencer} revisión(es) técnica(s) por vencer", route('parque.vehiculos.index')] : null,
    ])->filter();
@endphp

@if($alertas->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-bell card-header__icon" aria-hidden="true"></i> Asuntos que requieren atención
        <span class="card-header__aside"><x-estado color="danger">{{ $alertas->count() }} alerta(s)</x-estado></span>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($alertas as [$tipo, $icono, $texto, $url])
            <div class="col-md-6">
                <a href="{{ $url }}" class="callout callout--{{ $tipo }} text-reset h-100">
                    <i class="bi bi-{{ $icono }}" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ $texto }}</span>
                    <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Indicadores principales ── --}}
<div class="kpi-grid">
    <x-kpi color="primary" icono="cash-stack" label="Gasto ejecutado {{ $anio }}"
           :valor="'S/ ' . number_format($gastos['total'], 2)" detalle="Total pagado en todos los módulos" />
    <x-kpi color="warning" icono="hourglass-split" label="Obligaciones por pagar"
           :valor="'S/ ' . number_format($porPagar['monto_pendiente'], 2)"
           :detalle="($porPagar['operaciones_pendientes'] + $porPagar['alimentos_pendientes']) . ' pendientes · ' . ($porPagar['operaciones_vencidas'] + $porPagar['alimentos_vencidos']) . ' vencidas'"
           :href="route('operaciones.registros.index', ['estado' => 'pendiente'])" />
    <x-kpi color="info" icono="truck-front" label="Flota vehicular"
           :valor="$flota['operativos'] . ' / ' . $flota['total']" :detalle="'Operativos · ' . $flota['mantenimiento'] . ' en mantenimiento'"
           :href="route('parque.vehiculos.index')" />
    <x-kpi color="{{ $mantVencidos->count() ? 'danger' : 'success' }}" icono="wrench-adjustable" label="Mantenimientos (7 días)"
           :valor="$mantProximos->count()" :detalle="'Programados · ' . $mantVencidos->count() . ' atrasado(s)'"
           :href="route('parque.mantenimientos.index')" />
</div>

{{-- ── Gasto por módulo ── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart card-header__icon" aria-hidden="true"></i> Gasto pagado por mes (últimos 6 meses)
                <span class="card-header__aside d-none d-md-flex gap-3">
                    <span class="leyenda" style="--c:#12355b">Mantenimiento</span>
                    <span class="leyenda" style="--c:#b08a2e">Alimentos</span>
                    <span class="leyenda" style="--c:#1f6391">Op. recíprocas</span>
                </span>
            </div>
            <div class="card-body d-flex flex-column"><div class="flex-grow-1" style="position:relative;min-height:300px"><canvas id="graficoGastos" aria-label="Gráfico de gasto mensual" role="img"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart card-header__icon" aria-hidden="true"></i> Distribución del gasto {{ $anio }}</div>
            <div class="card-body">
                <dl class="lista-definicion">
                    <div><dt>Mantenimiento vehicular</dt><dd class="money">S/ {{ number_format($gastos['mantenimiento'], 2) }}</dd></div>
                    <div><dt>Suministro de alimentos</dt><dd class="money">S/ {{ number_format($gastos['alimentos'], 2) }}</dd></div>
                    <div><dt>Servicios básicos</dt><dd class="money">S/ {{ number_format($gastos['servicios'], 2) }}</dd></div>
                    <div><dt>Otros pagos y contratos</dt><dd class="money">S/ {{ number_format($gastos['otros_pagos'], 2) }}</dd></div>
                    <div class="fw-bold"><dt class="text-body">Total ejecutado</dt><dd class="money">S/ {{ number_format($gastos['total'], 2) }}</dd></div>
                </dl>
                @if($operacionesPorServicio->isNotEmpty())
                    <hr>
                    <div class="small fw-semibold text-muted text-uppercase mb-2">Operaciones recíprocas por servicio</div>
                    <div style="position:relative;height:230px"><canvas id="graficoServicios" aria-label="Distribución por tipo de servicio" role="img"></canvas></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Próximos vencimientos --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-calendar-event card-header__icon" aria-hidden="true"></i> Pagos próximos a vencer
                <span class="card-header__aside"><a href="{{ route('operaciones.registros.index', ['estado' => 'pendiente']) }}" class="small">Ver todos</a></span>
            </div>
            <div class="scroll-panel">
                @forelse($proximosVencimientos as $op)
                    <a href="{{ route('operaciones.registros.show', $op) }}" class="list-row">
                        <span class="list-row__icon bg-danger-subtle text-danger"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <span class="list-row__main">
                            <span class="list-row__title d-block">{{ $op->entidad_proveedora }}</span>
                            <span class="list-row__sub">{{ $op->tipo_servicio_label }} · S/ {{ number_format($op->total, 2) }}</span>
                        </span>
                        <x-estado color="warning">{{ $op->fecha_vencimiento->format('d/m') }}</x-estado>
                    </a>
                @empty
                    @if($alimentosPorVencer->isEmpty())
                        <x-vacio icono="calendar-check" titulo="Sin vencimientos próximos" texto="No hay pagos que venzan en los próximos días." />
                    @endif
                @endforelse
                @foreach($alimentosPorVencer as $al)
                    <a href="{{ route('alimentos.registros.show', $al) }}" class="list-row">
                        <span class="list-row__icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-basket3" aria-hidden="true"></i></span>
                        <span class="list-row__main">
                            <span class="list-row__title d-block">{{ $al->proveedor_razon_social }}</span>
                            <span class="list-row__sub">Alimentos · {{ $al->penal_label }} · S/ {{ number_format($al->total, 2) }}</span>
                        </span>
                        <x-estado color="warning">{{ $al->periodo_fin->format('d/m') }}</x-estado>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Mantenimientos --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-wrench-adjustable card-header__icon" aria-hidden="true"></i> Mantenimientos de la semana
                <span class="card-header__aside"><a href="{{ route('parque.mantenimientos.index') }}" class="small">Ver todos</a></span>
            </div>
            <div class="scroll-panel">
                @foreach($mantVencidos as $m)
                    <a href="{{ route('parque.mantenimientos.show', $m) }}" class="list-row">
                        <span class="list-row__icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                        <span class="list-row__main">
                            <span class="list-row__title d-block">{{ $m->vehiculo?->placa }} — {{ $m->descripcion }}</span>
                            <span class="list-row__sub">Atrasado desde {{ $m->fecha_programada->format('d/m/Y') }}</span>
                        </span>
                        <x-estado color="danger">Atrasado</x-estado>
                    </a>
                @endforeach
                @forelse($mantProximos as $m)
                    <a href="{{ route('parque.mantenimientos.show', $m) }}" class="list-row">
                        <span class="list-row__icon bg-primary-subtle text-primary"><i class="bi bi-tools" aria-hidden="true"></i></span>
                        <span class="list-row__main">
                            <span class="list-row__title d-block">{{ $m->vehiculo?->placa }} — {{ $m->descripcion }}</span>
                            <span class="list-row__sub">{{ $m->tipo_label }} · {{ $m->taller ?: 'Taller por definir' }}</span>
                        </span>
                        <x-estado color="info">{{ $m->fecha_programada->format('d/m') }}</x-estado>
                    </a>
                @empty
                    @if($mantVencidos->isEmpty())
                        <x-vacio icono="check2-circle" titulo="Sin mantenimientos pendientes" texto="No hay mantenimientos programados para los próximos 7 días." />
                    @endif
                @endforelse
            </div>
        </div>
    </div>

    {{-- Ejecución por proveedor --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-building card-header__icon" aria-hidden="true"></i> Ejecución por proveedor {{ $anio }}
            </div>
            <div class="scroll-panel">
                @forelse($pagoPorProveedor as $p)
                    @php $avance = $p->total_registrado > 0 ? round($p->total_pagado / $p->total_registrado * 100) : 0; @endphp
                    <div class="list-row d-block">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="list-row__title" title="{{ $p->entidad_proveedora }}">{{ $p->entidad_proveedora }}</span>
                            <span class="small fw-semibold">{{ $avance }}%</span>
                        </div>
                        <div class="progress my-1" role="progressbar" aria-valuenow="{{ $avance }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de pago">
                            <div class="progress-bar {{ $avance >= 100 ? 'bg-success' : '' }}" style="width: {{ $avance }}%"></div>
                        </div>
                        <div class="list-row__sub d-flex justify-content-between">
                            <span>Pagado S/ {{ number_format($p->total_pagado, 2) }} de S/ {{ number_format($p->total_registrado, 2) }}</span>
                            @if($p->numero_contrato)<span>{{ $p->numero_contrato }}</span>@endif
                        </div>
                    </div>
                @empty
                    <x-vacio icono="building" titulo="Sin información" texto="Aún no hay operaciones registradas en el ejercicio." />
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Accesos a reportes ── --}}
<div class="card">
    <div class="card-header"><i class="bi bi-download card-header__icon" aria-hidden="true"></i> Reportes rápidos</div>
    <div class="card-body">
        <div class="row g-2">
            @foreach([
                ['parque.mantenimientos.reporte-pdf', 'Mantenimientos'],
                ['alimentos.reporte-pdf', 'Suministro de alimentos'],
                ['operaciones.reporte-pdf', 'Operaciones recíprocas'],
                ['parque.reporte-pdf', 'Parque automotor'],
            ] as [$ruta, $nombre])
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route($ruta) }}" class="btn btn-light w-100 text-start d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf text-danger fs-5" aria-hidden="true"></i>
                    <span><span class="d-block fw-semibold small">{{ $nombre }}</span><span class="d-block text-muted" style="font-size:.72rem">Descargar PDF</span></span>
                </a>
            </div>
            @endforeach
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
    const datos = @json($gastosMensuales);
    new Chart(document.getElementById('graficoGastos'), {
        type: 'bar',
        data: {
            labels: datos.map(d => d.mes),
            datasets: [
                { label: 'Mantenimiento',  data: datos.map(d => d.mantenimiento), backgroundColor: '#12355b', borderRadius: 3, maxBarThickness: 28 },
                { label: 'Alimentos',      data: datos.map(d => d.alimentos),     backgroundColor: '#b08a2e', borderRadius: 3, maxBarThickness: 28 },
                { label: 'Op. recíprocas', data: datos.map(d => d.servicios),     backgroundColor: '#1f6391', borderRadius: 3, maxBarThickness: 28 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + Number(v).toLocaleString('es-PE') } },
                x: { grid: { display: false } }
            }
        }
    });

    const servicios = @json($operacionesPorServicio);
    const lienzo = document.getElementById('graficoServicios');
    if (lienzo && servicios.length) {
        new Chart(lienzo, {
            type: 'doughnut',
            data: { labels: servicios.map(s => s.servicio), datasets: [{ data: servicios.map(s => s.monto), backgroundColor: ERP.colores, borderWidth: 2, borderColor: '#fff' }] },
            options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
        });
    }
});
</script>
@endpush

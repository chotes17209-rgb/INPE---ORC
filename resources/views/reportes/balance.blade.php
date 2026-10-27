@extends('layouts.app')
@section('title', 'Ejecución del gasto')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Ejecución del gasto: obligaciones registradas frente a pagos efectuados, por módulo — ejercicio ' . $anio . '.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reportes.index', ['anio' => $anio]) }}">Reportes</a></li>
    <li class="breadcrumb-item active" aria-current="page">Ejecución del gasto</li>
@endsection
@section('pestanas')@include('partials.pestanas', ['modulo' => 'reportes'])@endsection
@section('page-actions')
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label for="b_anio" class="small text-muted text-nowrap">Ejercicio</label>
        <input type="number" name="anio" id="b_anio" value="{{ $anio }}" class="form-control form-control-sm" style="width:100px" min="2020" max="{{ date('Y') + 1 }}">
        <button class="btn btn-sm btn-primary">Ver</button>
    </form>
    <a href="{{ route('reportes.general-pdf', ['anio' => $anio]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF</a>
@endsection

@section('content')
@php $t = $balance['total']; @endphp

<div class="kpi-grid">
    <x-kpi color="primary" icono="journal-text" label="Obligaciones registradas" :valor="'S/ ' . number_format($t['registrado'], 2)" :detalle="$t['cantidad'] . ' registros en el ejercicio'" />
    <x-kpi color="success" icono="check2-circle" label="Pagado" :valor="'S/ ' . number_format($t['pagado'], 2)" :detalle="number_format($t['avance'], 1) . ' % de ejecución'" />
    <x-kpi color="warning" icono="hourglass-split" label="Saldo por pagar" :valor="'S/ ' . number_format($t['pendiente'], 2)" detalle="Obligaciones aún no pagadas" />
    <x-kpi color="danger" icono="exclamation-octagon" label="Vencido" :valor="'S/ ' . number_format($t['vencido'], 2)" detalle="Obligaciones con plazo vencido" />
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clipboard-data card-header__icon" aria-hidden="true"></i> Ejecución por módulo</div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Módulo</th>
                    <th scope="col" class="text-end">Registros</th>
                    <th scope="col" class="text-end">Registrado</th>
                    <th scope="col" class="text-end">Pagado</th>
                    <th scope="col" class="text-end">Saldo</th>
                    <th scope="col" class="text-end">Vencido</th>
                    <th scope="col" style="width:22%">Avance de ejecución</th>
                </tr>
            </thead>
            <tbody>
            @foreach($balance['filas'] as $fila)
                <tr>
                    <th scope="row" class="fw-semibold">{{ $fila['modulo'] }}</th>
                    <td class="text-end">{{ $fila['cantidad'] }}</td>
                    <td class="text-end money">S/ {{ number_format($fila['registrado'], 2) }}</td>
                    <td class="text-end money text-success">S/ {{ number_format($fila['pagado'], 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($fila['pendiente'], 2) }}</td>
                    <td class="text-end money {{ $fila['vencido'] > 0 ? 'text-danger fw-semibold' : '' }}">S/ {{ number_format($fila['vencido'], 2) }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" role="progressbar" aria-valuenow="{{ $fila['avance'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avance de {{ $fila['modulo'] }}">
                                <div class="progress-bar {{ $fila['avance'] >= 100 ? 'bg-success' : '' }}" style="width: {{ min(100, $fila['avance']) }}%"></div>
                            </div>
                            <span class="small fw-semibold" style="width:3.5rem;text-align:right">{{ number_format($fila['avance'], 1) }} %</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Total</th>
                    <td class="text-end">{{ $t['cantidad'] }}</td>
                    <td class="text-end money">S/ {{ number_format($t['registrado'], 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($t['pagado'], 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($t['pendiente'], 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($t['vencido'], 2) }}</td>
                    <td class="fw-semibold">{{ number_format($t['avance'], 1) }} %</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="card-footer small text-muted">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        Alimentos: por fecha de emisión del comprobante. Operaciones recíprocas: por periodo de consumo. Mantenimientos: por fecha programada (se excluyen los cancelados; se considera pagado el mantenimiento con conformidad).
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-graph-up card-header__icon" aria-hidden="true"></i> Pagos efectuados por mes</div>
    <div class="card-body"><canvas id="graficoPagos" height="90" role="img" aria-label="Pagos efectuados por mes"></canvas></div>
</div>
@endsection

@push('vendor-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mensual = @json(array_values($balance['mensual']));
    new Chart(document.getElementById('graficoPagos'), {
        type: 'line',
        data: {
            labels: mensual.map(m => m.mes.substring(0, 3)),
            datasets: [{ label: 'Pagado', data: mensual.map(m => m.pagado), borderColor: '#1F1F1F', backgroundColor: 'rgba(224, 174, 11,.08)', fill: true, tension: .3, pointRadius: 3 }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + Number(v).toLocaleString('es-PE') } }, x: { grid: { display: false } } }
        }
    });
});
</script>
@endpush

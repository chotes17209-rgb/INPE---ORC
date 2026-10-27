@extends('layouts.app')
@section('title', 'Resumen mensual de operaciones recíprocas')
@section('page-title', 'Operaciones recíprocas')
@section('page-subtitle', 'Resumen mensual: importes por periodo de consumo — ejercicio ' . $anio . '.')
@section('pestanas')@include('partials.pestanas', ['modulo' => 'operaciones'])@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('operaciones.registros.index') }}">Operaciones recíprocas</a></li>
    <li class="breadcrumb-item active" aria-current="page">Resumen mensual</li>
@endsection

@section('page-actions')
    <a href="{{ route('operaciones.reporte-pdf', array_filter(['anio' => $anio, 'tipo_servicio' => $tipo, 'estado' => $estado])) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Exportar PDF</a>
    <a href="{{ route('operaciones.reporte-excel', array_filter(['anio' => $anio, 'tipo_servicio' => $tipo, 'estado' => $estado])) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Exportar Excel</a>
@endsection

@section('content')
@php
    $tot = collect($resumen);
    $meses = \App\Support\Catalogo::MESES;
@endphp
<div class="card">
    <form method="GET" class="filter-bar" aria-label="Filtros del resumen">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label" for="r_anio">Ejercicio</label>
                <input type="number" name="anio" id="r_anio" value="{{ $anio }}" class="form-control form-control-sm" min="2020" max="{{ date('Y') + 1 }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="r_tipo">Tipo de servicio</label>
                <select name="tipo_servicio" id="r_tipo" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    <optgroup label="Servicios básicos">
                        @foreach(\App\Models\OperacionReciproca::SERVICIOS_BASICOS as $k => $v)
                            <option value="{{ $k }}" @selected($tipo === $k)>{{ $v }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Otros pagos">
                        @foreach(\App\Models\OperacionReciproca::OTROS_PAGOS as $k => $v)
                            <option value="{{ $k }}" @selected($tipo === $k)>{{ $v }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="r_estado">Estado</label>
                <select name="estado" id="r_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\OperacionReciproca::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected($estado === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Aplicar</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Periodo</th>
                    <th scope="col" class="text-end">Operaciones</th>
                    <th scope="col" class="text-end">Base imponible</th>
                    <th scope="col" class="text-end">IGV</th>
                    <th scope="col" class="text-end">Total registrado</th>
                    <th scope="col" class="text-end">Pagado</th>
                    <th scope="col" class="text-end">Por pagar</th>
                </tr>
            </thead>
            <tbody>
            @foreach($resumen as $m => $d)
                <tr class="{{ $d['cantidad'] === 0 ? 'text-muted' : '' }}">
                    <th scope="row" class="fw-semibold">
                        @if($d['cantidad'] > 0)
                            <a href="{{ route('operaciones.registros.index', array_filter(['anio' => $anio, 'mes' => $m, 'tipo_servicio' => $tipo, 'estado' => $estado])) }}">{{ $meses[$m] }}</a>
                        @else
                            {{ $meses[$m] }}
                        @endif
                    </th>
                    <td class="text-end">{{ $d['cantidad'] ?: '—' }}</td>
                    <td class="text-end money">{{ $d['total_base'] > 0 ? 'S/ ' . number_format($d['total_base'], 2) : '—' }}</td>
                    <td class="text-end money">{{ $d['total_igv'] > 0 ? 'S/ ' . number_format($d['total_igv'], 2) : '—' }}</td>
                    <td class="text-end money fw-semibold">{{ $d['total_mes'] > 0 ? 'S/ ' . number_format($d['total_mes'], 2) : '—' }}</td>
                    <td class="text-end money text-success">{{ $d['pagado'] > 0 ? 'S/ ' . number_format($d['pagado'], 2) : '—' }}</td>
                    <td class="text-end money text-danger">{{ $d['pendiente'] > 0 ? 'S/ ' . number_format($d['pendiente'], 2) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Total {{ $anio }}</th>
                    <td class="text-end">{{ $tot->sum('cantidad') }}</td>
                    <td class="text-end money">S/ {{ number_format($tot->sum('total_base'), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($tot->sum('total_igv'), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($tot->sum('total_mes'), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($tot->sum('pagado'), 2) }}</td>
                    <td class="text-end money">S/ {{ number_format($tot->sum('pendiente'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

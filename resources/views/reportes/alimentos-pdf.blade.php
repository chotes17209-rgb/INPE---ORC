@extends('reportes.layouts.pdf')
@section('titulo', 'Reporte de suministro de alimentos')
@section('subtitulo', $filtros ?? 'Todos los registros')
@section('codigo', 'REP-ALI-' . now()->format('Ymd'))

@section('contenido')
@php
    $pagado = $registros->where('estado', 'pagado')->sum('total');
    $vencido = $registros->where('estado', 'vencido')->sum('total');
@endphp
<table class="resumen">
    <tr>
        <td><div class="et">Registros</div><div class="va">{{ $registros->count() }}</div></td>
        <td><div class="et">Importe total</div><div class="va">S/ {{ number_format($registros->sum('total'), 2) }}</div></td>
        <td><div class="et">Pagado</div><div class="va">S/ {{ number_format($pagado, 2) }}</div></td>
        <td><div class="et">Por pagar</div><div class="va">S/ {{ number_format($registros->sum('total') - $pagado, 2) }}</div></td>
        <td><div class="et">Vencido</div><div class="va">S/ {{ number_format($vencido, 2) }}</div></td>
    </tr>
</table>

<table class="datos">
    <thead>
        <tr>
            <th>N° registro</th><th>Establecimiento</th><th>Proveedor</th><th>Contrato</th><th>Periodo</th>
            <th>O.S. / SIAF</th><th>Comprobante</th><th class="r">Base imp.</th><th class="r">IGV</th><th class="r">Total</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($registros as $r)
        <tr>
            <td class="mono nw">{{ $r->numero_registro }}</td>
            <td>{{ $r->penal_label }}</td>
            <td>{{ $r->proveedor_razon_social }}<span class="sub-celda">RUC {{ $r->proveedor_ruc ?? '—' }}</span></td>
            <td>{{ $r->numero_contrato ?? '—' }}</td>
            <td class="nw">{{ $r->periodo_inicio->format('d/m/Y') }}<span class="sub-celda">al {{ $r->periodo_fin->format('d/m/Y') }}</span></td>
            <td>{{ $r->orden_servicio ?? '—' }}<span class="sub-celda">SIAF {{ $r->registro_siaf ?? '—' }}</span></td>
            <td class="nw">{{ $r->comprobante ?? '—' }}<span class="sub-celda">{{ $r->fecha_emision->format('d/m/Y') }}</span></td>
            <td class="r nw">{{ number_format($r->base_imponible, 2) }}</td>
            <td class="r nw">{{ number_format($r->igv, 2) }}</td>
            <td class="r nw"><strong>{{ number_format($r->total, 2) }}</strong></td>
            <td><span class="estado e-{{ $r->estado_badge }}">{{ mb_strtoupper($r->estado_label) }}</span>@if($r->fecha_pago)<span class="sub-celda">{{ $r->fecha_pago->format('d/m/Y') }}</span>@endif</td>
        </tr>
    @empty
        <tr><td colspan="11" class="c muted">No existen registros para los criterios seleccionados.</td></tr>
    @endforelse
    </tbody>
    @if($registros->count())
    <tfoot>
        <tr>
            <td colspan="7" class="r">TOTALES (S/)</td>
            <td class="r nw">{{ number_format($registros->sum('base_imponible'), 2) }}</td>
            <td class="r nw">{{ number_format($registros->sum('igv'), 2) }}</td>
            <td class="r nw">{{ number_format($registros->sum('total'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
<p class="nota">Importes expresados en soles (S/). Estado vigente a la fecha de emisión del reporte.</p>
@endsection

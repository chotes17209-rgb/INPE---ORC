@extends('reportes.layouts.pdf')
@section('titulo', 'Reporte de operaciones recíprocas')
@section('subtitulo', $filtros ?? 'Todos los registros')
@section('codigo', 'REP-ORC-' . now()->format('Ymd'))

@section('contenido')
@php
    $pagado = $operaciones->where('estado', 'pagado')->sum('total');
    $vencido = $operaciones->where('estado', 'vencido')->sum('total');
@endphp
<table class="resumen">
    <tr>
        <td><div class="et">Operaciones</div><div class="va">{{ $operaciones->count() }}</div></td>
        <td><div class="et">Importe total</div><div class="va">S/ {{ number_format($operaciones->sum('total'), 2) }}</div></td>
        <td><div class="et">Pagado</div><div class="va">S/ {{ number_format($pagado, 2) }}</div></td>
        <td><div class="et">Por pagar</div><div class="va">S/ {{ number_format($operaciones->sum('total') - $pagado, 2) }}</div></td>
        <td><div class="et">Vencido</div><div class="va">S/ {{ number_format($vencido, 2) }}</div></td>
    </tr>
</table>

<table class="datos">
    <thead>
        <tr>
            <th>N° operación</th><th>Servicio</th><th>Entidad proveedora</th><th>Comprobante</th><th>Periodo</th>
            <th>Emisión</th><th>Vencimiento</th><th>O.S. / SIAF</th><th class="r">IGV</th><th class="r">Total</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($operaciones as $o)
        <tr>
            <td class="mono nw">{{ $o->numero_operacion }}</td>
            <td>{{ $o->tipo_servicio_label }}<span class="sub-celda">{{ $o->grupo_servicio }}</span></td>
            <td>{{ $o->entidad_proveedora }}<span class="sub-celda">RUC {{ $o->ruc_entidad ?? '—' }}</span></td>
            <td class="nw">{{ $o->comprobante ?? '—' }}</td>
            <td class="nw">{{ $o->periodo }}</td>
            <td class="nw">{{ $o->fecha_emision->format('d/m/Y') }}</td>
            <td class="nw">{{ $o->fecha_vencimiento->format('d/m/Y') }}</td>
            <td>{{ $o->orden_servicio ?? '—' }}<span class="sub-celda">SIAF {{ $o->registro_siaf ?? '—' }}</span></td>
            <td class="r nw">{{ number_format($o->igv, 2) }}</td>
            <td class="r nw"><strong>{{ number_format($o->total, 2) }}</strong></td>
            <td><span class="estado e-{{ $o->estado_badge }}">{{ mb_strtoupper($o->estado_label) }}</span>@if($o->fecha_pago)<span class="sub-celda">{{ $o->fecha_pago->format('d/m/Y') }}</span>@endif</td>
        </tr>
    @empty
        <tr><td colspan="11" class="c muted">No existen operaciones para los criterios seleccionados.</td></tr>
    @endforelse
    </tbody>
    @if($operaciones->count())
    <tfoot>
        <tr>
            <td colspan="8" class="r">TOTALES (S/)</td>
            <td class="r nw">{{ number_format($operaciones->sum('igv'), 2) }}</td>
            <td class="r nw">{{ number_format($operaciones->sum('total'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
<p class="nota">Importes expresados en soles (S/). Estado vigente a la fecha de emisión del reporte.</p>
@endsection

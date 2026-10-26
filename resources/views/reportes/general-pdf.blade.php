@extends('reportes.layouts.pdf')
@section('titulo', 'Reporte general de gestión contable — Ejercicio ' . $anio)
@section('subtitulo', 'Ejecución del gasto y detalle de registros por módulo')
@section('codigo', 'REP-GEN-' . $anio)

@section('contenido')
@php $t = $balance['total']; @endphp

<div class="seccion">1. Ejecución del gasto por módulo</div>
<table class="datos">
    <thead>
        <tr><th>Módulo</th><th class="r">Registros</th><th class="r">Registrado</th><th class="r">Pagado</th><th class="r">Saldo</th><th class="r">Vencido</th><th class="r">Avance</th></tr>
    </thead>
    <tbody>
    @foreach($balance['filas'] as $f)
        <tr>
            <td><strong>{{ $f['modulo'] }}</strong></td>
            <td class="r">{{ $f['cantidad'] }}</td>
            <td class="r nw">{{ number_format($f['registrado'], 2) }}</td>
            <td class="r nw">{{ number_format($f['pagado'], 2) }}</td>
            <td class="r nw">{{ number_format($f['pendiente'], 2) }}</td>
            <td class="r nw">{{ number_format($f['vencido'], 2) }}</td>
            <td class="r nw">{{ number_format($f['avance'], 1) }} %</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL (S/)</td><td class="r">{{ $t['cantidad'] }}</td>
            <td class="r nw">{{ number_format($t['registrado'], 2) }}</td><td class="r nw">{{ number_format($t['pagado'], 2) }}</td>
            <td class="r nw">{{ number_format($t['pendiente'], 2) }}</td><td class="r nw">{{ number_format($t['vencido'], 2) }}</td>
            <td class="r nw">{{ number_format($t['avance'], 1) }} %</td>
        </tr>
    </tfoot>
</table>

<div class="seccion">2. Suministro de alimentos ({{ $alimentos->count() }} registros)</div>
<table class="datos">
    <thead><tr><th>N° registro</th><th>Establecimiento</th><th>Proveedor</th><th>Contrato</th><th>Comprobante</th><th>Periodo</th><th class="r">Total</th><th>Estado</th></tr></thead>
    <tbody>
    @forelse($alimentos as $r)
        <tr>
            <td class="mono nw">{{ $r->numero_registro }}</td>
            <td>{{ $r->penal_label }}</td>
            <td>{{ \Illuminate\Support\Str::limit($r->proveedor_razon_social, 45) }}</td>
            <td>{{ $r->numero_contrato ?? '—' }}</td>
            <td class="nw">{{ $r->comprobante ?? '—' }}</td>
            <td class="nw">{{ $r->periodo_inicio->format('d/m/Y') }} al {{ $r->periodo_fin->format('d/m/Y') }}</td>
            <td class="r nw">{{ number_format($r->total, 2) }}</td>
            <td><span class="estado e-{{ $r->estado_badge }}">{{ mb_strtoupper($r->estado_label) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="8" class="c muted">Sin registros en el ejercicio.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><td colspan="6" class="r">TOTAL ALIMENTOS (S/)</td><td class="r nw">{{ number_format($alimentos->sum('total'), 2) }}</td><td></td></tr></tfoot>
</table>

<div class="seccion">3. Operaciones recíprocas ({{ $operaciones->count() }} registros)</div>
<table class="datos">
    <thead><tr><th>N° operación</th><th>Servicio</th><th>Entidad</th><th>Comprobante</th><th>Emisión</th><th>Vencimiento</th><th class="r">Total</th><th>Estado</th></tr></thead>
    <tbody>
    @forelse($operaciones as $o)
        <tr>
            <td class="mono nw">{{ $o->numero_operacion }}</td>
            <td>{{ $o->tipo_servicio_label }}</td>
            <td>{{ \Illuminate\Support\Str::limit($o->entidad_proveedora, 45) }}</td>
            <td class="nw">{{ $o->comprobante ?? '—' }}</td>
            <td class="nw">{{ $o->fecha_emision->format('d/m/Y') }}</td>
            <td class="nw">{{ $o->fecha_vencimiento->format('d/m/Y') }}</td>
            <td class="r nw">{{ number_format($o->total, 2) }}</td>
            <td><span class="estado e-{{ $o->estado_badge }}">{{ mb_strtoupper($o->estado_label) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="8" class="c muted">Sin registros en el ejercicio.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><td colspan="6" class="r">TOTAL OPERACIONES (S/)</td><td class="r nw">{{ number_format($operaciones->sum('total'), 2) }}</td><td></td></tr></tfoot>
</table>

<div class="seccion">4. Mantenimientos vehiculares con conformidad ({{ $mantenimientos->count() }})</div>
<table class="datos">
    <thead><tr><th>Placa</th><th>Tipo</th><th>Trabajo</th><th>Taller</th><th>Ejecutado</th><th>Comprobante</th><th class="r">Importe</th></tr></thead>
    <tbody>
    @forelse($mantenimientos as $m)
        <tr>
            <td class="mono nw">{{ $m->vehiculo?->placa }}</td>
            <td>{{ $m->tipo_label }}</td>
            <td>{{ \Illuminate\Support\Str::limit($m->descripcion, 60) }}</td>
            <td>{{ $m->taller ?? '—' }}</td>
            <td class="nw">{{ $m->fecha_realizado?->format('d/m/Y') }}</td>
            <td class="nw">{{ $m->comprobante ?? '—' }}</td>
            <td class="r nw">{{ number_format($m->total_efectivo, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="c muted">Sin mantenimientos ejecutados en el ejercicio.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><td colspan="6" class="r">TOTAL MANTENIMIENTOS (S/)</td><td class="r nw">{{ number_format($mantenimientos->sum('total_efectivo'), 2) }}</td></tr></tfoot>
</table>

<table class="firmas">
    <tr>
        <td><div class="linea">Elaborado por</div><div class="muted">{{ auth()->user()?->nombre_completo }}</div></td>
        <td></td>
        <td><div class="linea">V.° B.° Jefe(a) del {{ config('inpe.area') }}</div><div class="muted">&nbsp;</div></td>
    </tr>
</table>
@endsection

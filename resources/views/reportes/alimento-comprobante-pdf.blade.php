@extends('reportes.layouts.pdf')
@section('titulo', 'Ficha de registro — Suministro de alimentos')
@section('subtitulo', 'Registro ' . $registro->numero_registro . ' · ' . $registro->penal_label)
@section('codigo', $registro->numero_registro)

@section('contenido')
<table class="ficha" style="margin-top:6px">
    <tr>
        <td style="width:50%"><span class="et">N° de registro</span><span class="va mono">{{ $registro->numero_registro }}</span></td>
        <td style="width:50%"><span class="et">Estado</span><span class="va"><span class="estado e-{{ $registro->estado_badge }}">{{ mb_strtoupper($registro->estado_label) }}</span></span></td>
    </tr>
</table>

<div class="seccion">Proveedor y contrato</div>
<table class="ficha">
    <tr>
        <td colspan="2"><span class="et">Razón social</span><span class="va">{{ $registro->proveedor_razon_social }}</span></td>
        <td><span class="et">RUC</span><span class="va mono">{{ $registro->proveedor_ruc ?? '—' }}</span></td>
        <td><span class="et">N° de contrato</span><span class="va">{{ $registro->numero_contrato ?? '—' }}</span></td>
    </tr>
    <tr>
        <td colspan="2"><span class="et">Nombre comercial</span><span class="va">{{ $registro->proveedor_nombre ?? '—' }}</span></td>
        <td colspan="2"><span class="et">Área responsable</span><span class="va">{{ $registro->area_responsable ?? '—' }}</span></td>
    </tr>
</table>

<div class="seccion">Establecimiento y periodo de suministro</div>
<table class="ficha">
    <tr>
        <td><span class="et">Establecimiento penal</span><span class="va">{{ $registro->penal_label }}</span></td>
        <td><span class="et">Periodo desde</span><span class="va">{{ $registro->periodo_inicio->format('d/m/Y') }}</span></td>
        <td><span class="et">Periodo hasta</span><span class="va">{{ $registro->periodo_fin->format('d/m/Y') }}</span></td>
        <td><span class="et">Días</span><span class="va">{{ (int) $registro->periodo_inicio->diffInDays($registro->periodo_fin) + 1 }}</span></td>
    </tr>
</table>

<div class="seccion">Orden de servicio, SIAF y garantía</div>
<table class="ficha">
    <tr>
        <td><span class="et">Orden de servicio</span><span class="va">{{ $registro->orden_servicio ?? '—' }}</span></td>
        <td><span class="et">Fecha O.S.</span><span class="va">{{ $registro->fecha_orden_servicio?->format('d/m/Y') ?? '—' }}</span></td>
        <td><span class="et">Registro SIAF</span><span class="va mono">{{ $registro->registro_siaf ?? '—' }}</span></td>
        <td><span class="et">Garantía</span><span class="va">{{ $registro->garantia_descripcion ?? '—' }}</span></td>
    </tr>
    @if($registro->garantia_monto || $registro->garantia_vencimiento)
    <tr>
        <td colspan="2"><span class="et">Monto de la garantía</span><span class="va">{{ $registro->garantia_monto ? 'S/ ' . number_format($registro->garantia_monto, 2) : '—' }}</span></td>
        <td colspan="2"><span class="et">Vencimiento de la garantía</span><span class="va">{{ $registro->garantia_vencimiento?->format('d/m/Y') ?? '—' }}</span></td>
    </tr>
    @endif
</table>

<div class="seccion">Comprobante de pago</div>
<table class="ficha">
    <tr>
        <td><span class="et">Comprobante</span><span class="va mono">{{ $registro->comprobante ?? '—' }}</span></td>
        <td><span class="et">Fecha de emisión</span><span class="va">{{ $registro->fecha_emision->format('d/m/Y') }}</span></td>
        <td><span class="et">Base imponible</span><span class="va">S/ {{ number_format($registro->base_imponible, 2) }}</span></td>
        <td><span class="et">IGV</span><span class="va">S/ {{ number_format($registro->igv, 2) }}</span></td>
    </tr>
    <tr>
        <td colspan="2"><span class="et">Otros cargos</span><span class="va">S/ {{ number_format($registro->otros, 2) }}</span></td>
        <td colspan="2" style="background:#fdf3d0"><span class="et">Importe total</span><span class="va" style="font-size:12px;color:#141414">S/ {{ number_format($registro->total, 2) }}</span></td>
    </tr>
</table>

@if($registro->estado === 'pagado')
<div class="seccion">Registro del pago</div>
<table class="ficha">
    <tr>
        <td><span class="et">Fecha de pago</span><span class="va">{{ $registro->fecha_pago?->format('d/m/Y') }}</span></td>
        <td><span class="et">Método de pago</span><span class="va">{{ $registro->metodo_pago_label }}</span></td>
        <td><span class="et">N° de voucher</span><span class="va">{{ $registro->numero_voucher ?? '—' }}</span></td>
        <td><span class="et">Aprobado por</span><span class="va">{{ $registro->aprobadoPor?->nombre_completo ?? '—' }}</span></td>
    </tr>
</table>
@endif

@if($registro->descripcion || $registro->observaciones)
<div class="seccion">Información complementaria</div>
<table class="ficha">
    @if($registro->descripcion)<tr><td colspan="4"><span class="et">Descripción</span><span class="va" style="font-weight:normal">{{ $registro->descripcion }}</span></td></tr>@endif
    @if($registro->observaciones)<tr><td colspan="4"><span class="et">Observaciones</span><span class="va" style="font-weight:normal">{{ $registro->observaciones }}</span></td></tr>@endif
</table>
@endif

<table class="firmas">
    <tr>
        <td><div class="linea">Registrado por</div><div class="muted">{{ $registro->registradoPor?->nombre_completo ?? '—' }}</div></td>
        <td><div class="linea">Revisado por</div><div class="muted">&nbsp;</div></td>
        <td><div class="linea">V.° B.° Jefe(a) del {{ config('inpe.area') }}</div><div class="muted">&nbsp;</div></td>
    </tr>
</table>
<p class="nota">Registrado en el sistema el {{ $registro->created_at->format('d/m/Y H:i') }}.</p>
@endsection

@extends('reportes.layouts.pdf')
@section('titulo', 'Ficha de registro — Operación recíproca')
@section('subtitulo', 'Operación ' . $operacion->numero_operacion . ' · ' . $operacion->tipo_servicio_label . ' · Periodo ' . $operacion->periodo)
@section('codigo', $operacion->numero_operacion)

@section('contenido')
<table class="ficha" style="margin-top:6px">
    <tr>
        <td style="width:50%"><span class="et">N° de operación</span><span class="va mono">{{ $operacion->numero_operacion }}</span></td>
        <td style="width:50%"><span class="et">Estado</span><span class="va"><span class="estado e-{{ $operacion->estado_badge }}">{{ mb_strtoupper($operacion->estado_label) }}</span></span></td>
    </tr>
</table>

<div class="seccion">Servicio y entidad proveedora</div>
<table class="ficha">
    <tr>
        <td><span class="et">Tipo de servicio</span><span class="va">{{ $operacion->tipo_servicio_label }}</span></td>
        <td colspan="2"><span class="et">Entidad proveedora</span><span class="va">{{ $operacion->entidad_proveedora }}</span></td>
        <td><span class="et">RUC</span><span class="va mono">{{ $operacion->ruc_entidad ?? '—' }}</span></td>
    </tr>
    <tr>
        <td colspan="2"><span class="et">Razón social</span><span class="va">{{ $operacion->razon_social ?? '—' }}</span></td>
        <td><span class="et">Establecimiento / sede</span><span class="va">{{ $operacion->penal_pertenece ?? '—' }}</span></td>
        <td><span class="et">Código de suministro</span><span class="va">{{ $operacion->usuario_servicio ?? '—' }}</span></td>
    </tr>
    @if($operacion->numero_contrato)
    <tr>
        <td colspan="2"><span class="et">N° de contrato</span><span class="va">{{ $operacion->numero_contrato }}</span></td>
        <td><span class="et">Vigencia desde</span><span class="va">{{ $operacion->vigencia_contrato_inicio?->format('d/m/Y') ?? '—' }}</span></td>
        <td><span class="et">Vigencia hasta</span><span class="va">{{ $operacion->vigencia_contrato_fin?->format('d/m/Y') ?? '—' }}</span></td>
    </tr>
    @endif
</table>

<div class="seccion">Comprobante y plazos</div>
<table class="ficha">
    <tr>
        <td><span class="et">Comprobante</span><span class="va mono">{{ $operacion->comprobante ?? '—' }}</span></td>
        <td><span class="et">Periodo de consumo</span><span class="va">{{ $operacion->periodo }}</span></td>
        <td><span class="et">Fecha de emisión</span><span class="va">{{ $operacion->fecha_emision->format('d/m/Y') }}</span></td>
        <td><span class="et">Fecha de vencimiento</span><span class="va">{{ $operacion->fecha_vencimiento->format('d/m/Y') }}</span></td>
    </tr>
    <tr>
        <td><span class="et">Orden de servicio</span><span class="va">{{ $operacion->orden_servicio ?? '—' }}</span></td>
        <td><span class="et">Fecha O.S.</span><span class="va">{{ $operacion->fecha_orden_servicio?->format('d/m/Y') ?? '—' }}</span></td>
        <td colspan="2"><span class="et">Registro SIAF</span><span class="va mono">{{ $operacion->registro_siaf ?? '—' }}</span></td>
    </tr>
</table>

<div class="seccion">Importes</div>
<table class="ficha">
    <tr>
        <td><span class="et">Base imponible</span><span class="va">S/ {{ number_format($operacion->base_imponible ?? 0, 2) }}</span></td>
        <td><span class="et">Monto del servicio</span><span class="va">S/ {{ number_format($operacion->monto, 2) }}</span></td>
        <td><span class="et">IGV</span><span class="va">S/ {{ number_format($operacion->igv, 2) }}</span></td>
        <td><span class="et">Otros cargos</span><span class="va">S/ {{ number_format($operacion->otros, 2) }}</span></td>
    </tr>
    <tr>
        <td colspan="4" style="background:#fdf3d0"><span class="et">Importe total</span><span class="va" style="font-size:12px;color:#141414">S/ {{ number_format($operacion->total, 2) }}</span></td>
    </tr>
</table>

@if($operacion->estado === 'pagado')
<div class="seccion">Registro del pago</div>
<table class="ficha">
    <tr>
        <td><span class="et">Fecha de pago</span><span class="va">{{ $operacion->fecha_pago?->format('d/m/Y') }}</span></td>
        <td><span class="et">Método de pago</span><span class="va">{{ $operacion->metodo_pago_label }}</span></td>
        <td><span class="et">N° de voucher</span><span class="va">{{ $operacion->numero_voucher ?? '—' }}</span></td>
        <td><span class="et">Aprobado por</span><span class="va">{{ $operacion->aprobadoPor?->nombre_completo ?? '—' }}</span></td>
    </tr>
</table>
@endif

@if($operacion->descripcion || $operacion->observaciones)
<div class="seccion">Información complementaria</div>
<table class="ficha">
    @if($operacion->descripcion)<tr><td colspan="4"><span class="et">Descripción</span><span class="va" style="font-weight:normal">{{ $operacion->descripcion }}</span></td></tr>@endif
    @if($operacion->observaciones)<tr><td colspan="4"><span class="et">Observaciones</span><span class="va" style="font-weight:normal">{{ $operacion->observaciones }}</span></td></tr>@endif
</table>
@endif

<table class="firmas">
    <tr>
        <td><div class="linea">Registrado por</div><div class="muted">{{ $operacion->registradoPor?->nombre_completo ?? '—' }}</div></td>
        <td><div class="linea">Revisado por</div><div class="muted">&nbsp;</div></td>
        <td><div class="linea">V.° B.° Jefe(a) del {{ config('inpe.area') }}</div><div class="muted">&nbsp;</div></td>
    </tr>
</table>
<p class="nota">Registrado en el sistema el {{ $operacion->created_at->format('d/m/Y H:i') }}.</p>
@endsection

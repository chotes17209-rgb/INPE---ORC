@extends('layouts.app')
@section('title', 'Operación ' . $operacion->numero_operacion)
@section('page-title', 'Operación ' . $operacion->numero_operacion)
@section('page-subtitle', $operacion->tipo_servicio_label . ' · ' . $operacion->entidad_proveedora . ' · Periodo ' . $operacion->periodo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('operaciones.registros.index') }}">Operaciones recíprocas</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $operacion->numero_operacion }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('operaciones.registros.comprobante-pdf', $operacion) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Ficha PDF</a>
    @if($operacion->es_editable)
        @can('registrar')
            <a href="{{ route('operaciones.registros.edit', $operacion) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
        @endcan
    @endif
    @if($operacion->puede_pagarse)
        @can('aprobar')
            <button type="button" class="btn btn-success"
                    data-pago-url="{{ route('operaciones.registros.aprobar', $operacion) }}" data-pago-codigo="{{ $operacion->numero_operacion }}"
                    data-pago-detalle="{{ $operacion->entidad_proveedora }} — {{ $operacion->tipo_servicio_label }}" data-pago-total="{{ $operacion->total }}">
                <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>Registrar pago
            </button>
        @endcan
    @endif
@endsection

@section('content')
@if($operacion->estado === 'vencido')
    <div class="callout callout--danger mb-3"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Operación vencida.</span> La fecha de vencimiento fue el {{ $operacion->fecha_vencimiento->format('d/m/Y') }} y aún no se registra el pago. Regularice para evitar intereses o cortes del servicio.</div></div>
@elseif($operacion->estado === 'observado')
    <div class="callout callout--warning mb-3"><i class="bi bi-flag-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Comprobante observado.</span> {{ $operacion->observaciones ?: 'Revise las observaciones antes de continuar con el trámite.' }}</div></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-building card-header__icon" aria-hidden="true"></i> Servicio y entidad proveedora
                <span class="card-header__aside"><x-estado :color="$operacion->estado_badge">{{ $operacion->estado_label }}</x-estado></span>
            </div>
            <div class="ficha">
                <x-dato label="Tipo de servicio">{{ $operacion->tipo_servicio_label }} <span class="text-muted small">({{ $operacion->grupo_servicio }})</span></x-dato>
                <x-dato label="Entidad proveedora">{{ $operacion->entidad_proveedora }}</x-dato>
                <x-dato label="RUC"><span class="font-monospace">{{ $operacion->ruc_entidad }}</span></x-dato>
                <x-dato label="Razón social" wide>{{ $operacion->razon_social }}</x-dato>
                <x-dato label="Domicilio fiscal" wide>{{ $operacion->direccion_entidad }}</x-dato>
                <x-dato label="Descripción del servicio">{{ $operacion->servicio_prestado }}</x-dato>
                <x-dato label="Establecimiento / sede">{{ ['ORC' => 'Sede de la Oficina Regional'][$operacion->penal_pertenece] ?? (\App\Models\Alimento::PENALES[$operacion->penal_pertenece] ?? $operacion->penal_pertenece) }}</x-dato>
                <x-dato label="Código de suministro">{{ $operacion->usuario_servicio }}</x-dato>
            </div>

            @if($operacion->numero_contrato || $operacion->vigencia_contrato_inicio)
            <h3 class="seccion-titulo">Contrato</h3>
            <div class="ficha">
                <x-dato label="N° de contrato">{{ $operacion->numero_contrato }}</x-dato>
                <x-dato label="Vigencia desde">{{ $operacion->vigencia_contrato_inicio?->format('d/m/Y') }}</x-dato>
                <x-dato label="Vigencia hasta">{{ $operacion->vigencia_contrato_fin?->format('d/m/Y') }}</x-dato>
            </div>
            @endif

            <h3 class="seccion-titulo">Fechas del comprobante</h3>
            <div class="ficha">
                <x-dato label="Periodo de consumo">{{ $operacion->periodo }}</x-dato>
                <x-dato label="Fecha de emisión">{{ $operacion->fecha_emision->format('d/m/Y') }}</x-dato>
                <x-dato label="Fecha de recepción">{{ $operacion->fecha_facturacion?->format('d/m/Y') }}</x-dato>
                <x-dato label="Fecha de vencimiento"><span class="{{ $operacion->esta_vencida ? 'text-danger fw-semibold' : '' }}">{{ $operacion->fecha_vencimiento->format('d/m/Y') }}</span></x-dato>
            </div>

            <h3 class="seccion-titulo">Orden de servicio y SIAF</h3>
            <div class="ficha">
                <x-dato label="Orden de servicio">{{ $operacion->orden_servicio }}</x-dato>
                <x-dato label="Fecha de la O.S.">{{ $operacion->fecha_orden_servicio?->format('d/m/Y') }}</x-dato>
                <x-dato label="Registro SIAF"><span class="font-monospace">{{ $operacion->registro_siaf }}</span></x-dato>
                <x-dato label="Área responsable">{{ $operacion->area_responsable }}</x-dato>
            </div>

            @if($operacion->descripcion || $operacion->observaciones)
            <h3 class="seccion-titulo">Información complementaria</h3>
            <div class="ficha">
                <x-dato label="Descripción" wide>{{ $operacion->descripcion }}</x-dato>
                <x-dato label="Observaciones" wide>{{ $operacion->observaciones }}</x-dato>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-receipt card-header__icon" aria-hidden="true"></i> Comprobante</div>
            <div class="card-body">
                <dl class="lista-definicion">
                    <div><dt>Comprobante</dt><dd class="font-monospace">{{ $operacion->comprobante ?? '—' }}</dd></div>
                    <div><dt>Base imponible</dt><dd class="money">S/ {{ number_format($operacion->base_imponible ?? 0, 2) }}</dd></div>
                    <div><dt>Monto del servicio</dt><dd class="money">S/ {{ number_format($operacion->monto, 2) }}</dd></div>
                    <div><dt>IGV</dt><dd class="money">S/ {{ number_format($operacion->igv, 2) }}</dd></div>
                    <div><dt>Otros cargos</dt><dd class="money">S/ {{ number_format($operacion->otros, 2) }}</dd></div>
                </dl>
                <div class="total-box d-flex justify-content-between align-items-center mt-3">
                    <span class="total-box__label">Importe total</span>
                    <span class="total-box__value">S/ {{ number_format($operacion->total, 2) }}</span>
                </div>
                @if($operacion->archivo_adjunto)
                    <a href="{{ asset('storage/' . $operacion->archivo_adjunto) }}" target="_blank" rel="noopener" class="btn btn-light w-100 mt-3">
                        <i class="bi bi-paperclip me-1" aria-hidden="true"></i>Ver documento sustentatorio
                    </a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-signpost-split card-header__icon" aria-hidden="true"></i> Seguimiento del trámite</div>
            <div class="card-body">
                <ul class="tramite">
                    <li class="hecho">
                        <strong>Registro</strong>
                        <span class="tramite__fecha">{{ $operacion->created_at->format('d/m/Y H:i') }} · {{ $operacion->registradoPor?->nombre_completo ?? 'Sistema' }}</span>
                    </li>
                    <li class="{{ $operacion->estado === 'pagado' ? 'hecho' : 'actual' }}">
                        <strong>{{ $operacion->estado === 'pagado' ? 'Pago registrado' : $operacion->estado_label }}</strong>
                        @if($operacion->estado === 'pagado')
                            <span class="tramite__fecha">
                                {{ $operacion->fecha_pago?->format('d/m/Y') }} · {{ $operacion->metodo_pago_label }}
                                @if($operacion->numero_voucher)<br>Voucher {{ $operacion->numero_voucher }}@endif
                                <br>Aprobado por {{ $operacion->aprobadoPor?->nombre_completo ?? '—' }}
                            </span>
                        @else
                            <span class="tramite__fecha">Vence el {{ $operacion->fecha_vencimiento->format('d/m/Y') }}</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        @if($operacion->es_editable)
            @can('aprobar')
                <form method="POST" action="{{ route('operaciones.registros.destroy', $operacion) }}" class="mt-3"
                      data-confirm="Se eliminará la operación {{ $operacion->numero_operacion }}. Esta acción quedará registrada en la auditoría."
                      data-confirm-title="Eliminar operación" data-confirm-btn="Eliminar">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-link text-danger p-0 small"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar esta operación</button>
                </form>
            @endcan
        @endif
    </div>
</div>

@can('aprobar')
    @include('partials.modal-pago')
@endcan
@endsection

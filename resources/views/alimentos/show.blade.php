@extends('layouts.app')
@section('title', 'Registro ' . $registro->numero_registro)
@section('page-title', 'Registro ' . $registro->numero_registro)
@section('page-subtitle', $registro->proveedor_razon_social . ' · ' . $registro->penal_label)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('alimentos.registros.index') }}">Suministro de alimentos</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $registro->numero_registro }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('alimentos.registros.comprobante-pdf', $registro) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Ficha PDF</a>
    @if($registro->es_editable)
        @can('registrar')
            <a href="{{ route('alimentos.registros.edit', $registro) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
        @endcan
    @endif
    @if($registro->puede_pagarse)
        @can('aprobar')
            <button type="button" class="btn btn-success"
                    data-pago-url="{{ route('alimentos.registros.aprobar', $registro) }}" data-pago-codigo="{{ $registro->numero_registro }}"
                    data-pago-detalle="{{ $registro->proveedor_razon_social }}" data-pago-total="{{ $registro->total }}">
                <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>Registrar pago
            </button>
        @endcan
    @endif
@endsection

@section('content')
@if($registro->estado === 'vencido')
    <div class="callout callout--danger mb-3"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Periodo concluido sin pago.</span> El periodo de suministro terminó el {{ $registro->periodo_fin->format('d/m/Y') }} y el comprobante aún no registra pago.</div></div>
@elseif($registro->estado === 'observado')
    <div class="callout callout--warning mb-3"><i class="bi bi-flag-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Comprobante observado.</span> {{ $registro->observaciones ?: 'Revise las observaciones antes de continuar con el trámite.' }}</div></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-building card-header__icon" aria-hidden="true"></i> Proveedor y contrato
                <span class="card-header__aside"><x-estado :color="$registro->estado_badge">{{ $registro->estado_label }}</x-estado></span>
            </div>
            <div class="ficha">
                <x-dato label="Razón social" class="dato--wide">{{ $registro->proveedor_razon_social }}</x-dato>
                <x-dato label="RUC"><span class="font-monospace">{{ $registro->proveedor_ruc }}</span></x-dato>
                <x-dato label="Nombre comercial">{{ $registro->proveedor_nombre }}</x-dato>
                <x-dato label="N° de contrato">{{ $registro->numero_contrato }}</x-dato>
                <x-dato label="Área responsable">{{ $registro->area_responsable }}</x-dato>
            </div>

            <h3 class="seccion-titulo">Establecimiento y periodo</h3>
            <div class="ficha">
                <x-dato label="Establecimiento penal">{{ $registro->penal_label }}</x-dato>
                <x-dato label="Periodo desde">{{ $registro->periodo_inicio->format('d/m/Y') }}</x-dato>
                <x-dato label="Periodo hasta"><span class="{{ $registro->estado === 'vencido' ? 'text-danger fw-semibold' : '' }}">{{ $registro->periodo_fin->format('d/m/Y') }}</span></x-dato>
                <x-dato label="Duración">{{ (int) $registro->periodo_inicio->diffInDays($registro->periodo_fin) + 1 }} días</x-dato>
            </div>

            <h3 class="seccion-titulo">Orden de servicio y SIAF</h3>
            <div class="ficha">
                <x-dato label="Orden de servicio">{{ $registro->orden_servicio }}</x-dato>
                <x-dato label="Fecha de la O.S.">{{ $registro->fecha_orden_servicio?->format('d/m/Y') }}</x-dato>
                <x-dato label="Registro SIAF"><span class="font-monospace">{{ $registro->registro_siaf }}</span></x-dato>
            </div>

            @if($registro->garantia_descripcion || $registro->garantia_monto)
            <h3 class="seccion-titulo">Garantía</h3>
            <div class="ficha">
                <x-dato label="Documento">{{ $registro->garantia_descripcion }}</x-dato>
                <x-dato label="Monto">{{ $registro->garantia_monto ? 'S/ ' . number_format($registro->garantia_monto, 2) : '' }}</x-dato>
                <x-dato label="Vencimiento">
                    @if($registro->garantia_vencimiento)
                        {{ $registro->garantia_vencimiento->format('d/m/Y') }}
                        @if($registro->garantia_vencimiento->isPast())<x-estado color="danger" class="ms-1">Vencida</x-estado>@endif
                    @endif
                </x-dato>
            </div>
            @endif

            @if($registro->descripcion || $registro->observaciones)
            <h3 class="seccion-titulo">Información complementaria</h3>
            <div class="ficha">
                <x-dato label="Descripción" wide>{{ $registro->descripcion }}</x-dato>
                <x-dato label="Observaciones" wide>{{ $registro->observaciones }}</x-dato>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-receipt card-header__icon" aria-hidden="true"></i> Comprobante</div>
            <div class="card-body">
                <dl class="lista-definicion">
                    <div><dt>Comprobante</dt><dd class="font-monospace">{{ $registro->comprobante ?? '—' }}</dd></div>
                    <div><dt>Fecha de emisión</dt><dd>{{ $registro->fecha_emision->format('d/m/Y') }}</dd></div>
                    <div><dt>Base imponible</dt><dd class="money">S/ {{ number_format($registro->base_imponible, 2) }}</dd></div>
                    <div><dt>IGV</dt><dd class="money">S/ {{ number_format($registro->igv, 2) }}</dd></div>
                    <div><dt>Otros cargos</dt><dd class="money">S/ {{ number_format($registro->otros, 2) }}</dd></div>
                </dl>
                <div class="total-box d-flex justify-content-between align-items-center mt-3">
                    <span class="total-box__label">Importe total</span>
                    <span class="total-box__value">S/ {{ number_format($registro->total, 2) }}</span>
                </div>
                @if($registro->archivo_adjunto)
                    <a href="{{ asset('storage/' . $registro->archivo_adjunto) }}" target="_blank" rel="noopener" class="btn btn-light w-100 mt-3">
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
                        <span class="tramite__fecha">{{ $registro->created_at->format('d/m/Y H:i') }} · {{ $registro->registradoPor?->nombre_completo ?? 'Sistema' }}</span>
                    </li>
                    <li class="{{ $registro->estado === 'pagado' ? 'hecho' : 'actual' }}">
                        <strong>{{ $registro->estado === 'pagado' ? 'Pago registrado' : $registro->estado_label }}</strong>
                        @if($registro->estado === 'pagado')
                            <span class="tramite__fecha">
                                {{ $registro->fecha_pago?->format('d/m/Y') }} · {{ $registro->metodo_pago_label }}
                                @if($registro->numero_voucher)<br>Voucher {{ $registro->numero_voucher }}@endif
                                <br>Aprobado por {{ $registro->aprobadoPor?->nombre_completo ?? '—' }}
                            </span>
                        @else
                            <span class="tramite__fecha">Pendiente de registro de pago</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        @if($registro->es_editable)
            @can('aprobar')
                <form method="POST" action="{{ route('alimentos.registros.destroy', $registro) }}" class="mt-3"
                      data-confirm="Se eliminará el registro {{ $registro->numero_registro }}. Esta acción quedará registrada en la auditoría."
                      data-confirm-title="Eliminar registro" data-confirm-btn="Eliminar">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-link text-danger p-0 small"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar este registro</button>
                </form>
            @endcan
        @endif
    </div>
</div>

@can('aprobar')
    @include('partials.modal-pago')
@endcan
@endsection

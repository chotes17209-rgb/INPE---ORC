@extends('layouts.app')
@section('title', 'Registrar conformidad del mantenimiento')
@section('page-title', 'Registrar conformidad del mantenimiento')
@section('page-subtitle', 'Registre la ejecución del servicio y su costo real. El vehículo volverá a estar operativo.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.index') }}">Mantenimientos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.show', $mantenimiento) }}">Detalle</a></li>
    <li class="breadcrumb-item active" aria-current="page">Conformidad</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4 order-lg-2">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle card-header__icon" aria-hidden="true"></i> Mantenimiento programado</div>
            <div class="card-body">
                <dl class="lista-definicion">
                    <div><dt>Vehículo</dt><dd>{{ $mantenimiento->vehiculo->placa }}</dd></div>
                    <div><dt>Unidad</dt><dd>{{ $mantenimiento->vehiculo->descripcion }}</dd></div>
                    <div><dt>Tipo</dt><dd>{{ $mantenimiento->tipo_label }}</dd></div>
                    <div><dt>Fecha programada</dt><dd>{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</dd></div>
                    <div><dt>Costo estimado</dt><dd class="money">S/ {{ number_format($mantenimiento->costo_estimado, 2) }}</dd></div>
                    <div><dt>Kilometraje registrado</dt><dd>{{ number_format($mantenimiento->vehiculo->kilometraje_actual) }} km</dd></div>
                </dl>
                <p class="small text-muted mt-3 mb-0">{{ $mantenimiento->descripcion }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8 order-lg-1">
        <form method="POST" action="{{ route('parque.mantenimientos.marcar-completo', $mantenimiento) }}" class="card"
              data-confirm="Se registrará la conformidad del mantenimiento y el vehículo {{ $mantenimiento->vehiculo->placa }} volverá a estar operativo. Después no podrá modificarse."
              data-confirm-title="Confirmar conformidad" data-confirm-btn="Registrar conformidad" data-confirm-variant="success">
            @csrf
            <div class="card-body pb-0">
                <x-seccion numero="1" titulo="Ejecución del servicio">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-label for="fecha_realizado" requerido>Fecha de ejecución</x-label>
                            <input type="date" name="fecha_realizado" id="fecha_realizado" required max="{{ date('Y-m-d') }}"
                                   class="form-control @error('fecha_realizado') is-invalid @enderror" value="{{ old('fecha_realizado', date('Y-m-d')) }}">
                            <x-error campo="fecha_realizado" />
                        </div>
                        <div class="col-md-4">
                            <x-label for="kilometraje_mantenimiento">Kilometraje al ingreso</x-label>
                            <div class="input-group">
                                <input type="number" name="kilometraje_mantenimiento" id="kilometraje_mantenimiento" min="{{ $mantenimiento->vehiculo->kilometraje_actual }}"
                                       class="form-control @error('kilometraje_mantenimiento') is-invalid @enderror"
                                       value="{{ old('kilometraje_mantenimiento', $mantenimiento->kilometraje_mantenimiento ?? $mantenimiento->vehiculo->kilometraje_actual) }}">
                                <span class="input-group-text">km</span>
                            </div>
                            <div class="form-text">Actualiza el kilometraje del vehículo.</div>
                            <x-error campo="kilometraje_mantenimiento" />
                        </div>
                        <div class="col-md-4">
                            <x-label for="costo_real" requerido>Costo real</x-label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" name="costo_real" id="costo_real" step="0.01" min="0" required
                                       class="form-control @error('costo_real') is-invalid @enderror" value="{{ old('costo_real', $mantenimiento->costo_real ?? $mantenimiento->costo_estimado) }}">
                            </div>
                            <x-error campo="costo_real" />
                        </div>
                    </div>
                </x-seccion>

                <x-seccion numero="2" titulo="Comprobante del servicio" ayuda="Si no indica el total facturado, se considerará el costo real.">
                    <div class="row g-3">
                        <div class="col-6 col-md-2">
                            <x-label for="factura_serie">Serie</x-label>
                            <input type="text" name="factura_serie" id="factura_serie" maxlength="10" data-mayusculas class="form-control" value="{{ old('factura_serie', $mantenimiento->factura_serie) }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <x-label for="factura_numero_completo">Número</x-label>
                            <input type="text" name="factura_numero_completo" id="factura_numero_completo" maxlength="20" class="form-control"
                                   value="{{ old('factura_numero_completo', $mantenimiento->factura_numero_completo ?? $mantenimiento->factura_numero) }}">
                        </div>
                        <div class="col-md-3">
                            <x-label for="factura_fecha">Fecha</x-label>
                            <input type="date" name="factura_fecha" id="factura_fecha" class="form-control" value="{{ old('factura_fecha', $mantenimiento->factura_fecha?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <x-label for="total">Total facturado</x-label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" name="total" id="total" step="0.01" min="0" class="form-control" value="{{ old('total', $mantenimiento->total) }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <x-label for="observaciones">Informe u observaciones del trabajo</x-label>
                            <textarea name="observaciones" id="observaciones" rows="3" maxlength="500" data-contador class="form-control">{{ old('observaciones', $mantenimiento->observaciones) }}</textarea>
                        </div>
                    </div>
                </x-seccion>
            </div>
            <div class="form-actions">
                <a href="{{ route('parque.mantenimientos.show', $mantenimiento) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success" data-texto-envio="Registrando…"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Registrar conformidad</button>
            </div>
        </form>
    </div>
</div>
@endsection

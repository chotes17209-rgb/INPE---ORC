{{-- Formulario compartido para registrar y editar suministros de alimentos. Requiere $registro. --}}
@php
    $f = fn ($campo) => $registro->{$campo} instanceof \Carbon\CarbonInterface ? $registro->{$campo}->format('Y-m-d') : $registro->{$campo};
@endphp

<x-seccion numero="1" titulo="Proveedor y contrato" ayuda="Escriba el RUC y la razón social se completará sola. Si no conoce el RUC, escriba el nombre del proveedor y elija de la lista.">
    <div class="row g-3">
        <div class="col-md-4 col-lg-3">
            <x-label for="proveedor_ruc">RUC del proveedor</x-label>
            <div class="input-group">
                <input type="text" name="proveedor_ruc" id="proveedor_ruc" inputmode="numeric" maxlength="11"
                       class="form-control @error('proveedor_ruc') is-invalid @enderror"
                       value="{{ old('proveedor_ruc', $registro->proveedor_ruc) }}" placeholder="20XXXXXXXXX">
                <button type="button" class="btn btn-outline-primary"
                        data-ruc-url="{{ route('servicios.ruc', '__RUC__') }}" data-ruc-buscar="{{ route('servicios.ruc-buscar') }}" data-ruc-input="#proveedor_ruc"
                        data-ruc-razon="#proveedor_razon_social" data-ruc-nombre="#proveedor_nombre" data-ruc-estado="#proveedor_ruc_estado">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
                </button>
            </div>
            <div id="proveedor_ruc_estado" class="form-text"></div>
            <x-error campo="proveedor_ruc" />
        </div>
        <div class="col-md-8 col-lg-5">
            <x-label for="proveedor_razon_social" requerido>Razón social</x-label>
            <input type="text" name="proveedor_razon_social" id="proveedor_razon_social" maxlength="250" required
                   class="form-control @error('proveedor_razon_social') is-invalid @enderror"
                   value="{{ old('proveedor_razon_social', $registro->proveedor_razon_social) }}">
            <x-error campo="proveedor_razon_social" />
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="proveedor_nombre">Nombre comercial</x-label>
            <input type="text" name="proveedor_nombre" id="proveedor_nombre" maxlength="200" class="form-control"
                   value="{{ old('proveedor_nombre', $registro->proveedor_nombre) }}">
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="numero_contrato">N° de contrato</x-label>
            <input type="text" name="numero_contrato" id="numero_contrato" maxlength="60" class="form-control"
                   value="{{ old('numero_contrato', $registro->numero_contrato) }}" placeholder="Ej.: CONT-ALI-2025-001">
        </div>
    </div>
</x-seccion>

<x-seccion numero="2" titulo="Establecimiento y periodo de suministro">
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <x-label for="penal">Establecimiento penal</x-label>
            <select name="penal" id="penal" class="form-select @error('penal') is-invalid @enderror">
                <option value="">Seleccione…</option>
                @foreach(\App\Models\Alimento::PENALES as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('penal', $registro->penal) === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
            <x-error campo="penal" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="periodo_inicio" requerido>Periodo desde</x-label>
            <input type="date" name="periodo_inicio" id="periodo_inicio" required
                   class="form-control @error('periodo_inicio') is-invalid @enderror" value="{{ old('periodo_inicio', $f('periodo_inicio')) }}">
            <x-error campo="periodo_inicio" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="periodo_fin" requerido>Periodo hasta</x-label>
            <input type="date" name="periodo_fin" id="periodo_fin" required
                   class="form-control @error('periodo_fin') is-invalid @enderror" value="{{ old('periodo_fin', $f('periodo_fin')) }}">
            <div class="form-text">Si el periodo concluye sin registrarse el pago, el registro pasa a «Vencido».</div>
            <x-error campo="periodo_fin" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="area_responsable">Área responsable</x-label>
            <input type="text" name="area_responsable" id="area_responsable" maxlength="100" class="form-control"
                   value="{{ old('area_responsable', $registro->area_responsable) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="3" titulo="Orden de servicio y registro SIAF">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="orden_servicio">N° de orden de servicio</x-label>
            <input type="text" name="orden_servicio" id="orden_servicio" maxlength="50" class="form-control"
                   value="{{ old('orden_servicio', $registro->orden_servicio) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_orden_servicio">Fecha de la orden de servicio</x-label>
            <input type="date" name="fecha_orden_servicio" id="fecha_orden_servicio" class="form-control @error('fecha_orden_servicio') is-invalid @enderror"
                   value="{{ old('fecha_orden_servicio', $f('fecha_orden_servicio')) }}">
            <x-error campo="fecha_orden_servicio" />
        </div>
        <div class="col-md-4">
            <x-label for="registro_siaf">N° de registro SIAF</x-label>
            <input type="text" name="registro_siaf" id="registro_siaf" maxlength="20" inputmode="numeric" data-solo-digitos
                   class="form-control" value="{{ old('registro_siaf', $registro->registro_siaf) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="4" titulo="Garantía de fiel cumplimiento" ayuda="Complete esta sección solo si el contrato exige garantía.">
    <div class="row g-3">
        <div class="col-md-5">
            <x-label for="garantia_descripcion">Documento de garantía</x-label>
            <input type="text" name="garantia_descripcion" id="garantia_descripcion" maxlength="200" class="form-control"
                   value="{{ old('garantia_descripcion', $registro->garantia_descripcion) }}" placeholder="Ej.: Carta fianza N° 0012345">
        </div>
        <div class="col-md-3">
            <x-label for="garantia_monto">Monto de la garantía</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="garantia_monto" id="garantia_monto" step="0.01" min="0" class="form-control @error('garantia_monto') is-invalid @enderror"
                       value="{{ old('garantia_monto', $registro->garantia_monto) }}">
            </div>
            <x-error campo="garantia_monto" />
        </div>
        <div class="col-md-4">
            <x-label for="garantia_vencimiento">Vencimiento de la garantía</x-label>
            <input type="date" name="garantia_vencimiento" id="garantia_vencimiento" class="form-control"
                   value="{{ old('garantia_vencimiento', $f('garantia_vencimiento')) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="5" titulo="Comprobante de pago e importes" ayuda="El total se calcula automáticamente (base imponible + IGV + otros).">
    <div class="row g-3">
        <div class="col-6 col-md-2">
            <x-label for="factura_serie">Serie</x-label>
            <input type="text" name="factura_serie" id="factura_serie" maxlength="10" data-mayusculas class="form-control"
                   value="{{ old('factura_serie', $registro->factura_serie) }}" placeholder="F001">
        </div>
        <div class="col-6 col-md-3">
            <x-label for="factura_numero">Número</x-label>
            <input type="text" name="factura_numero" id="factura_numero" maxlength="20" class="form-control"
                   value="{{ old('factura_numero', $registro->factura_numero) }}" placeholder="00000001">
        </div>
        <div class="col-md-3">
            <x-label for="fecha_emision" requerido>Fecha de emisión</x-label>
            <input type="date" name="fecha_emision" id="fecha_emision" required max="{{ date('Y-m-d') }}"
                   class="form-control @error('fecha_emision') is-invalid @enderror"
                   value="{{ old('fecha_emision', $f('fecha_emision') ?? date('Y-m-d')) }}">
            <x-error campo="fecha_emision" />
        </div>
        <div class="col-md-4">
            <x-label for="estado">Situación del trámite</x-label>
            <select name="estado" id="estado" class="form-select">
                @foreach(['pendiente' => 'Pendiente de pago', 'en_proceso' => 'En proceso de pago', 'observado' => 'Observado'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('estado', in_array($registro->estado, ['pendiente', 'en_proceso', 'observado']) ? $registro->estado : 'pendiente') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 col-lg-3">
            <x-label for="ali_base" requerido>Base imponible</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="base_imponible" id="ali_base" step="0.01" min="0" required data-suma="ali"
                       class="form-control @error('base_imponible') is-invalid @enderror" value="{{ old('base_imponible', $registro->base_imponible ?? '0.00') }}">
            </div>
            <x-error campo="base_imponible" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="ali_igv" requerido>IGV</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="igv" id="ali_igv" step="0.01" min="0" required data-suma="ali"
                       class="form-control @error('igv') is-invalid @enderror" value="{{ old('igv', $registro->igv ?? '0.00') }}">
                <button type="button" class="btn btn-outline-secondary" data-igv-base="#ali_base" data-igv-destino="#ali_igv" title="Calcular el 18 % de la base imponible">18 %</button>
            </div>
            <x-error campo="igv" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="ali_otros">Otros cargos</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="otros" id="ali_otros" step="0.01" min="0" data-suma="ali"
                       class="form-control" value="{{ old('otros', $registro->otros ?? '0.00') }}">
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="ali_total" requerido>Importe total</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="total" id="ali_total" step="0.01" min="0.01" required data-total="ali"
                       class="form-control fw-bold @error('total') is-invalid @enderror" value="{{ old('total', $registro->total ?? '0.00') }}">
            </div>
            <x-error campo="total" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="6" titulo="Información complementaria">
    <div class="row g-3">
        <div class="col-md-6">
            <x-label for="descripcion">Descripción del suministro</x-label>
            <textarea name="descripcion" id="descripcion" rows="3" maxlength="500" data-contador class="form-control">{{ old('descripcion', $registro->descripcion) }}</textarea>
        </div>
        <div class="col-md-6">
            <x-label for="observaciones">Observaciones</x-label>
            <textarea name="observaciones" id="observaciones" rows="3" maxlength="500" data-contador class="form-control">{{ old('observaciones', $registro->observaciones) }}</textarea>
        </div>
        <div class="col-md-6">
            <x-label for="archivo_adjunto">Documento sustentatorio</x-label>
            <input type="file" name="archivo_adjunto" id="archivo_adjunto" accept=".pdf,.jpg,.jpeg,.png"
                   class="form-control @error('archivo_adjunto') is-invalid @enderror">
            <div class="form-text">
                Formatos PDF, JPG o PNG. Tamaño máximo: 5 MB.
                @if($registro->archivo_adjunto)
                    Archivo actual: <a href="{{ asset('storage/' . $registro->archivo_adjunto) }}" target="_blank" rel="noopener">ver documento</a> (se reemplazará si adjunta otro).
                @endif
            </div>
            <x-error campo="archivo_adjunto" />
        </div>
    </div>
</x-seccion>

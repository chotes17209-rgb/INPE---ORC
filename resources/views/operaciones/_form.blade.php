{{-- Formulario compartido para registrar y editar operaciones recíprocas. Requiere $operacion. --}}
@php
    $f = fn ($campo) => $operacion->{$campo} instanceof \Carbon\CarbonInterface ? $operacion->{$campo}->format('Y-m-d') : $operacion->{$campo};
    $establecimientos = ['ORC' => 'Sede de la Oficina Regional'] + \App\Models\Alimento::PENALES;
    $penalActual = old('penal_pertenece', $operacion->penal_pertenece);
    if ($penalActual && ! array_key_exists($penalActual, $establecimientos)) {
        $establecimientos[$penalActual] = $penalActual; // conserva valores registrados anteriormente
    }
@endphp

<x-seccion numero="1" titulo="Servicio y entidad proveedora" ayuda="Escriba el RUC y los datos se completarán solos. Si no conoce el RUC, escriba el nombre de la entidad y elija de la lista.">
    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <x-label for="tipo_servicio" requerido>Tipo de servicio</x-label>
            <select name="tipo_servicio" id="tipo_servicio" required class="form-select @error('tipo_servicio') is-invalid @enderror">
                <option value="">Seleccione…</option>
                <optgroup label="Servicios básicos">
                    @foreach(\App\Models\OperacionReciproca::SERVICIOS_BASICOS as $k => $v)
                        <option value="{{ $k }}" @selected(old('tipo_servicio', $operacion->tipo_servicio) === $k)>{{ $v }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Otros pagos y contratos">
                    @foreach(\App\Models\OperacionReciproca::OTROS_PAGOS as $k => $v)
                        <option value="{{ $k }}" @selected(old('tipo_servicio', $operacion->tipo_servicio) === $k)>{{ $v }}</option>
                    @endforeach
                </optgroup>
            </select>
            <x-error campo="tipo_servicio" />
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="ruc_entidad">RUC de la entidad</x-label>
            <div class="input-group">
                <input type="text" name="ruc_entidad" id="ruc_entidad" inputmode="numeric" maxlength="11"
                       class="form-control @error('ruc_entidad') is-invalid @enderror" value="{{ old('ruc_entidad', $operacion->ruc_entidad) }}" placeholder="20XXXXXXXXX">
                <button type="button" class="btn btn-outline-primary"
                        data-ruc-url="{{ route('servicios.ruc', '__RUC__') }}" data-ruc-buscar="{{ route('servicios.ruc-buscar') }}" data-ruc-sugerir="#entidad_proveedora" data-ruc-input="#ruc_entidad"
                        data-ruc-razon="#razon_social" data-ruc-nombre="#entidad_proveedora" data-ruc-direccion="#direccion_entidad" data-ruc-estado="#ruc_entidad_estado">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
                </button>
            </div>
            <div id="ruc_entidad_estado" class="form-text"></div>
            <x-error campo="ruc_entidad" />
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="entidad_proveedora" requerido>Entidad proveedora</x-label>
            <input type="text" name="entidad_proveedora" id="entidad_proveedora" maxlength="200" required
                   class="form-control @error('entidad_proveedora') is-invalid @enderror" value="{{ old('entidad_proveedora', $operacion->entidad_proveedora) }}"
                   placeholder="Nombre con el que se identifica (ej.: Electrocentro S.A.)">
            <x-error campo="entidad_proveedora" />
        </div>
        <div class="col-md-6">
            <x-label for="razon_social">Razón social</x-label>
            <input type="text" name="razon_social" id="razon_social" maxlength="250" class="form-control" value="{{ old('razon_social', $operacion->razon_social) }}">
        </div>
        <div class="col-md-6">
            <x-label for="direccion_entidad">Domicilio fiscal</x-label>
            <input type="text" name="direccion_entidad" id="direccion_entidad" maxlength="250" class="form-control" value="{{ old('direccion_entidad', $operacion->direccion_entidad) }}">
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="servicio_prestado">Descripción del servicio</x-label>
            <input type="text" name="servicio_prestado" id="servicio_prestado" maxlength="200" class="form-control"
                   value="{{ old('servicio_prestado', $operacion->servicio_prestado) }}" placeholder="Ej.: Suministro de energía eléctrica">
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="penal_pertenece">Establecimiento / sede</x-label>
            <select name="penal_pertenece" id="penal_pertenece" class="form-select">
                <option value="">Seleccione…</option>
                @foreach($establecimientos as $k => $v)
                    <option value="{{ $k }}" @selected($penalActual === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-lg-4">
            <x-label for="usuario_servicio">Código de suministro / usuario</x-label>
            <input type="text" name="usuario_servicio" id="usuario_servicio" maxlength="150" class="form-control"
                   value="{{ old('usuario_servicio', $operacion->usuario_servicio) }}" placeholder="N° de suministro, línea o cuenta">
        </div>
    </div>
</x-seccion>

<x-seccion numero="2" titulo="Contrato" ayuda="Complete esta sección cuando el pago derive de un contrato o convenio.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="numero_contrato">N° de contrato o convenio</x-label>
            <input type="text" name="numero_contrato" id="numero_contrato" maxlength="60" class="form-control" value="{{ old('numero_contrato', $operacion->numero_contrato) }}">
        </div>
        <div class="col-md-4">
            <x-label for="vigencia_contrato_inicio">Vigencia desde</x-label>
            <input type="date" name="vigencia_contrato_inicio" id="vigencia_contrato_inicio" class="form-control" value="{{ old('vigencia_contrato_inicio', $f('vigencia_contrato_inicio')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="vigencia_contrato_fin">Vigencia hasta</x-label>
            <input type="date" name="vigencia_contrato_fin" id="vigencia_contrato_fin" class="form-control @error('vigencia_contrato_fin') is-invalid @enderror" value="{{ old('vigencia_contrato_fin', $f('vigencia_contrato_fin')) }}">
            <x-error campo="vigencia_contrato_fin" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="3" titulo="Comprobante y periodo de consumo">
    <div class="row g-3">
        <div class="col-6 col-md-2">
            <x-label for="factura_serie">Serie</x-label>
            <input type="text" name="factura_serie" id="factura_serie" maxlength="10" data-mayusculas class="form-control" value="{{ old('factura_serie', $operacion->factura_serie) }}" placeholder="F001">
        </div>
        <div class="col-6 col-md-3">
            <x-label for="numero_factura">Número</x-label>
            <input type="text" name="numero_factura" id="numero_factura" maxlength="20" class="form-control" value="{{ old('numero_factura', $operacion->numero_factura) }}" placeholder="00000001">
        </div>
        <div class="col-md-3 col-lg-2">
            <x-label for="periodo_mes" requerido>Mes del periodo</x-label>
            <select name="periodo_mes" id="periodo_mes" required class="form-select @error('periodo_mes') is-invalid @enderror">
                @foreach(\App\Support\Catalogo::MESES as $n => $mes)
                    <option value="{{ $n }}" @selected((int) old('periodo_mes', $operacion->periodo_mes) === $n)>{{ $mes }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-lg-1">
            <x-label for="periodo_anio" requerido>Año</x-label>
            <input type="number" name="periodo_anio" id="periodo_anio" required min="2000" max="2099"
                   class="form-control @error('periodo_anio') is-invalid @enderror" value="{{ old('periodo_anio', $operacion->periodo_anio) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_emision" requerido>Fecha de emisión</x-label>
            <input type="date" name="fecha_emision" id="fecha_emision" required max="{{ date('Y-m-d') }}"
                   class="form-control @error('fecha_emision') is-invalid @enderror" value="{{ old('fecha_emision', $f('fecha_emision') ?? date('Y-m-d')) }}">
            <x-error campo="fecha_emision" />
        </div>
        <div class="col-md-4">
            <x-label for="fecha_facturacion">Fecha de recepción</x-label>
            <input type="date" name="fecha_facturacion" id="fecha_facturacion" class="form-control" value="{{ old('fecha_facturacion', $f('fecha_facturacion')) }}">
            <div class="form-text">Fecha en que el comprobante ingresó a la entidad.</div>
        </div>
        <div class="col-md-4">
            <x-label for="fecha_vencimiento" requerido>Fecha de vencimiento</x-label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required
                   class="form-control @error('fecha_vencimiento') is-invalid @enderror" value="{{ old('fecha_vencimiento', $f('fecha_vencimiento')) }}">
            <div class="form-text">Al día siguiente sin pago, la operación pasa a «Vencido».</div>
            <x-error campo="fecha_vencimiento" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="4" titulo="Orden de servicio y registro SIAF">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="orden_servicio">N° de orden de servicio</x-label>
            <input type="text" name="orden_servicio" id="orden_servicio" maxlength="50" class="form-control" value="{{ old('orden_servicio', $operacion->orden_servicio) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_orden_servicio">Fecha de la orden de servicio</x-label>
            <input type="date" name="fecha_orden_servicio" id="fecha_orden_servicio" class="form-control" value="{{ old('fecha_orden_servicio', $f('fecha_orden_servicio')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="registro_siaf">N° de registro SIAF</x-label>
            <input type="text" name="registro_siaf" id="registro_siaf" inputmode="numeric" data-solo-digitos maxlength="18"
                   class="form-control @error('registro_siaf') is-invalid @enderror" value="{{ old('registro_siaf', $operacion->registro_siaf) }}">
            <x-error campo="registro_siaf" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="5" titulo="Importes" ayuda="El total se calcula automáticamente (monto del servicio + IGV + otros cargos).">
    <div class="row g-3">
        <div class="col-md-6 col-lg">
            <x-label for="op_base">Base imponible</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="base_imponible" id="op_base" step="0.01" min="0" class="form-control" value="{{ old('base_imponible', $operacion->base_imponible) }}">
            </div>
            <div class="form-text">Referencial, según comprobante.</div>
        </div>
        <div class="col-md-6 col-lg">
            <x-label for="op_monto" requerido>Monto del servicio</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="monto" id="op_monto" step="0.01" min="0" required data-suma="op"
                       class="form-control @error('monto') is-invalid @enderror" value="{{ old('monto', $operacion->monto ?? '0.00') }}">
            </div>
            <x-error campo="monto" />
        </div>
        <div class="col-md-6 col-lg">
            <x-label for="op_igv">IGV</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="igv" id="op_igv" step="0.01" min="0" data-suma="op" class="form-control" value="{{ old('igv', $operacion->igv ?? '0.00') }}">
                <button type="button" class="btn btn-outline-secondary" data-igv-base="#op_monto" data-igv-destino="#op_igv" title="Calcular el 18 % del monto">18 %</button>
            </div>
        </div>
        <div class="col-md-6 col-lg">
            <x-label for="op_otros">Otros cargos</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="otros" id="op_otros" step="0.01" min="0" data-suma="op" class="form-control" value="{{ old('otros', $operacion->otros ?? '0.00') }}">
            </div>
        </div>
        <div class="col-md-6 col-lg">
            <x-label for="op_total" requerido>Importe total</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="total" id="op_total" step="0.01" min="0.01" required data-total="op"
                       class="form-control fw-bold @error('total') is-invalid @enderror" value="{{ old('total', $operacion->total ?? '0.00') }}">
            </div>
            <x-error campo="total" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="6" titulo="Información complementaria">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="area_responsable">Área responsable</x-label>
            <input type="text" name="area_responsable" id="area_responsable" maxlength="100" class="form-control" value="{{ old('area_responsable', $operacion->area_responsable) }}">
        </div>
        <div class="col-md-8">
            <x-label for="archivo_adjunto">Documento sustentatorio</x-label>
            <input type="file" name="archivo_adjunto" id="archivo_adjunto" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('archivo_adjunto') is-invalid @enderror">
            <div class="form-text">
                Formatos PDF, JPG o PNG. Tamaño máximo: 5 MB.
                @if($operacion->archivo_adjunto)
                    Archivo actual: <a href="{{ asset('storage/' . $operacion->archivo_adjunto) }}" target="_blank" rel="noopener">ver documento</a> (se reemplazará si adjunta otro).
                @endif
            </div>
            <x-error campo="archivo_adjunto" />
        </div>
        <div class="col-md-6">
            <x-label for="descripcion">Descripción</x-label>
            <textarea name="descripcion" id="descripcion" rows="3" maxlength="500" data-contador class="form-control">{{ old('descripcion', $operacion->descripcion) }}</textarea>
        </div>
        <div class="col-md-6">
            <x-label for="observaciones">Observaciones</x-label>
            <textarea name="observaciones" id="observaciones" rows="3" maxlength="500" data-contador class="form-control">{{ old('observaciones', $operacion->observaciones) }}</textarea>
        </div>
    </div>
</x-seccion>

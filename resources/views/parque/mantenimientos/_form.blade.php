{{-- Formulario compartido para programar y editar mantenimientos. Requiere $mantenimiento y $vehiculos. --}}
@php
    $f = fn ($campo) => $mantenimiento->{$campo} instanceof \Carbon\CarbonInterface ? $mantenimiento->{$campo}->format('Y-m-d') : $mantenimiento->{$campo};
@endphp

<x-seccion numero="1" titulo="Vehículo y trabajo a realizar">
    <div class="row g-3">
        <div class="col-md-6 col-lg-5">
            <x-label for="vehiculo_id" requerido>Vehículo</x-label>
            @if($mantenimiento->exists)
                <input type="text" class="form-control" value="{{ $mantenimiento->vehiculo?->placa }} — {{ $mantenimiento->vehiculo?->descripcion }}" readonly>
                <div class="form-text">El vehículo de un mantenimiento registrado no puede cambiarse.</div>
            @else
                <select name="vehiculo_id" id="vehiculo_id" required class="form-select @error('vehiculo_id') is-invalid @enderror">
                    <option value="">Seleccione…</option>
                    @foreach($vehiculos as $v)
                        <option value="{{ $v->id }}" @selected((int) old('vehiculo_id', $mantenimiento->vehiculo_id) === $v->id)>
                            {{ $v->placa }} — {{ $v->descripcion }} ({{ number_format($v->kilometraje_actual) }} km)
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Al programar el mantenimiento, el vehículo pasa al estado «En mantenimiento».</div>
                <x-error campo="vehiculo_id" />
            @endif
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="tipo_mantenimiento" requerido>Tipo de mantenimiento</x-label>
            <select name="tipo_mantenimiento" id="tipo_mantenimiento" required class="form-select @error('tipo_mantenimiento') is-invalid @enderror">
                @foreach(\App\Models\VehiculoMantenimiento::TIPOS as $k => $v)
                    <option value="{{ $k }}" @selected(old('tipo_mantenimiento', $mantenimiento->tipo_mantenimiento) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <x-error campo="tipo_mantenimiento" />
        </div>
        <div class="col-md-6 col-lg-2">
            <x-label for="fecha_programada" requerido>Fecha programada</x-label>
            <input type="date" name="fecha_programada" id="fecha_programada" required
                   class="form-control @error('fecha_programada') is-invalid @enderror" value="{{ old('fecha_programada', $f('fecha_programada')) }}">
            <x-error campo="fecha_programada" />
        </div>
        <div class="col-md-6 col-lg-2">
            <x-label for="kilometraje_mantenimiento">Kilometraje</x-label>
            <input type="number" name="kilometraje_mantenimiento" id="kilometraje_mantenimiento" min="0" class="form-control"
                   value="{{ old('kilometraje_mantenimiento', $mantenimiento->kilometraje_mantenimiento) }}">
        </div>
        <div class="col-12">
            <x-label for="descripcion" requerido>Descripción del trabajo</x-label>
            <textarea name="descripcion" id="descripcion" rows="3" maxlength="500" required data-contador
                      class="form-control @error('descripcion') is-invalid @enderror" placeholder="Ej.: Cambio de aceite y filtros, revisión del sistema de frenos.">{{ old('descripcion', $mantenimiento->descripcion) }}</textarea>
            <x-error campo="descripcion" />
        </div>
        <div class="col-md-6">
            <x-label for="taller">Taller / proveedor</x-label>
            <input type="text" name="taller" id="taller" maxlength="100" class="form-control" value="{{ old('taller', $mantenimiento->taller) }}">
        </div>
        <div class="col-md-6">
            <x-label for="tecnico_responsable">Técnico responsable</x-label>
            <input type="text" name="tecnico_responsable" id="tecnico_responsable" maxlength="100" class="form-control" value="{{ old('tecnico_responsable', $mantenimiento->tecnico_responsable) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="2" titulo="Costos" ayuda="El costo real y el total se completan al concluir el mantenimiento; puede adelantarlos si ya cuenta con la cotización.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="costo_estimado" requerido>Costo estimado</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="costo_estimado" id="costo_estimado" step="0.01" min="0" required
                       class="form-control @error('costo_estimado') is-invalid @enderror" value="{{ old('costo_estimado', $mantenimiento->costo_estimado) }}">
            </div>
            <x-error campo="costo_estimado" />
        </div>
        <div class="col-md-4">
            <x-label for="costo_real">Costo real</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="costo_real" id="costo_real" step="0.01" min="0" class="form-control" value="{{ old('costo_real', $mantenimiento->costo_real) }}">
            </div>
        </div>
        <div class="col-md-4">
            <x-label for="total">Total facturado</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="total" id="total" step="0.01" min="0" class="form-control" value="{{ old('total', $mantenimiento->total) }}">
            </div>
        </div>
    </div>
</x-seccion>

<x-seccion numero="3" titulo="Orden de servicio, SIAF y comprobante">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="orden_servicio">N° de orden de servicio</x-label>
            <input type="text" name="orden_servicio" id="orden_servicio" maxlength="50" class="form-control" value="{{ old('orden_servicio', $mantenimiento->orden_servicio) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_orden_servicio">Fecha de la orden de servicio</x-label>
            <input type="date" name="fecha_orden_servicio" id="fecha_orden_servicio" class="form-control" value="{{ old('fecha_orden_servicio', $f('fecha_orden_servicio')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="registro_siaf">N° de registro SIAF</x-label>
            <input type="text" name="registro_siaf" id="registro_siaf" inputmode="numeric" maxlength="18" data-solo-digitos
                   class="form-control @error('registro_siaf') is-invalid @enderror" value="{{ old('registro_siaf', $mantenimiento->registro_siaf) }}">
            <x-error campo="registro_siaf" />
        </div>
        <div class="col-6 col-md-2">
            <x-label for="factura_serie">Serie</x-label>
            <input type="text" name="factura_serie" id="factura_serie" maxlength="10" data-mayusculas class="form-control" value="{{ old('factura_serie', $mantenimiento->factura_serie) }}" placeholder="E001">
        </div>
        <div class="col-6 col-md-3">
            <x-label for="factura_numero_completo">Número del comprobante</x-label>
            <input type="text" name="factura_numero_completo" id="factura_numero_completo" maxlength="20" class="form-control"
                   value="{{ old('factura_numero_completo', $mantenimiento->factura_numero_completo ?? $mantenimiento->factura_numero) }}">
        </div>
        <div class="col-md-3">
            <x-label for="factura_fecha">Fecha del comprobante</x-label>
            <input type="date" name="factura_fecha" id="factura_fecha" class="form-control" value="{{ old('factura_fecha', $f('factura_fecha')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="observaciones">Observaciones</x-label>
            <input type="text" name="observaciones" id="observaciones" maxlength="500" class="form-control" value="{{ old('observaciones', $mantenimiento->observaciones) }}">
        </div>
    </div>
</x-seccion>

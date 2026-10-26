{{-- Formulario compartido para registrar y editar vehículos. Requiere $vehiculo y $usuarios. --}}
@php
    $f = fn ($campo) => $vehiculo->{$campo} instanceof \Carbon\CarbonInterface ? $vehiculo->{$campo}->format('Y-m-d') : $vehiculo->{$campo};
@endphp

<x-seccion numero="1" titulo="Identificación del vehículo">
    <div class="row g-3">
        <div class="col-md-4 col-lg-3">
            <x-label for="placa" requerido>Placa de rodaje</x-label>
            <input type="text" name="placa" id="placa" maxlength="10" required data-mayusculas
                   class="form-control font-monospace @error('placa') is-invalid @enderror" value="{{ old('placa', $vehiculo->placa) }}" placeholder="ABC-123">
            <x-error campo="placa" />
        </div>
        <div class="col-md-4 col-lg-3">
            <x-label for="marca" requerido>Marca</x-label>
            <input type="text" name="marca" id="marca" maxlength="60" required class="form-control @error('marca') is-invalid @enderror" value="{{ old('marca', $vehiculo->marca) }}">
            <x-error campo="marca" />
        </div>
        <div class="col-md-4 col-lg-3">
            <x-label for="modelo" requerido>Modelo</x-label>
            <input type="text" name="modelo" id="modelo" maxlength="60" required class="form-control @error('modelo') is-invalid @enderror" value="{{ old('modelo', $vehiculo->modelo) }}">
            <x-error campo="modelo" />
        </div>
        <div class="col-md-4 col-lg-3">
            <x-label for="anio_fabricacion" requerido>Año de fabricación</x-label>
            <input type="number" name="anio_fabricacion" id="anio_fabricacion" required min="1980" max="{{ date('Y') + 1 }}"
                   class="form-control @error('anio_fabricacion') is-invalid @enderror" value="{{ old('anio_fabricacion', $vehiculo->anio_fabricacion) }}">
            <x-error campo="anio_fabricacion" />
        </div>
        <div class="col-md-4 col-lg-3">
            <x-label for="tipo" requerido>Clase de vehículo</x-label>
            <select name="tipo" id="tipo" required class="form-select @error('tipo') is-invalid @enderror">
                <option value="">Seleccione…</option>
                @foreach(\App\Models\Vehiculo::TIPOS as $k => $v)
                    <option value="{{ $k }}" @selected(old('tipo', $vehiculo->tipo) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <x-error campo="tipo" />
        </div>
        <div class="col-md-4 col-lg-3">
            <x-label for="combustible" requerido>Combustible</x-label>
            <select name="combustible" id="combustible" required class="form-select @error('combustible') is-invalid @enderror">
                <option value="">Seleccione…</option>
                @foreach(\App\Models\Vehiculo::COMBUSTIBLES as $k => $v)
                    <option value="{{ $k }}" @selected(old('combustible', $vehiculo->combustible) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <x-error campo="combustible" />
        </div>
        <div class="col-md-4 col-lg-2">
            <x-label for="color">Color</x-label>
            <input type="text" name="color" id="color" maxlength="40" class="form-control" value="{{ old('color', $vehiculo->color) }}">
        </div>
        <div class="col-md-6 col-lg-2">
            <x-label for="numero_serie">N° de serie (VIN)</x-label>
            <input type="text" name="numero_serie" id="numero_serie" maxlength="60" data-mayusculas class="form-control font-monospace" value="{{ old('numero_serie', $vehiculo->numero_serie) }}">
        </div>
        <div class="col-md-6 col-lg-2">
            <x-label for="numero_motor">N° de motor</x-label>
            <input type="text" name="numero_motor" id="numero_motor" maxlength="60" data-mayusculas class="form-control font-monospace" value="{{ old('numero_motor', $vehiculo->numero_motor) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="2" titulo="Datos patrimoniales y kilometraje">
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <x-label for="valor_adquisicion" requerido>Valor de adquisición</x-label>
            <div class="input-group">
                <span class="input-group-text">S/</span>
                <input type="number" name="valor_adquisicion" id="valor_adquisicion" step="0.01" min="0" required
                       class="form-control @error('valor_adquisicion') is-invalid @enderror" value="{{ old('valor_adquisicion', $vehiculo->valor_adquisicion ?? '0.00') }}">
            </div>
            <x-error campo="valor_adquisicion" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="fecha_adquisicion">Fecha de adquisición</x-label>
            <input type="date" name="fecha_adquisicion" id="fecha_adquisicion" max="{{ date('Y-m-d') }}"
                   class="form-control @error('fecha_adquisicion') is-invalid @enderror" value="{{ old('fecha_adquisicion', $f('fecha_adquisicion')) }}">
            <x-error campo="fecha_adquisicion" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="kilometraje_actual" requerido>Kilometraje actual</x-label>
            <div class="input-group">
                <input type="number" name="kilometraje_actual" id="kilometraje_actual" min="{{ $vehiculo->exists ? $vehiculo->kilometraje_actual : 0 }}" required
                       class="form-control @error('kilometraje_actual') is-invalid @enderror" value="{{ old('kilometraje_actual', $vehiculo->kilometraje_actual) }}">
                <span class="input-group-text">km</span>
            </div>
            @if($vehiculo->exists)<div class="form-text">Último registrado: {{ number_format($vehiculo->kilometraje_actual) }} km.</div>@endif
            <x-error campo="kilometraje_actual" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-label for="kilometraje_siguiente_mantenimiento">Próximo mantenimiento a los</x-label>
            <div class="input-group">
                <input type="number" name="kilometraje_siguiente_mantenimiento" id="kilometraje_siguiente_mantenimiento" min="0"
                       class="form-control @error('kilometraje_siguiente_mantenimiento') is-invalid @enderror" value="{{ old('kilometraje_siguiente_mantenimiento', $vehiculo->kilometraje_siguiente_mantenimiento) }}">
                <span class="input-group-text">km</span>
            </div>
            <x-error campo="kilometraje_siguiente_mantenimiento" />
        </div>
    </div>
</x-seccion>

<x-seccion numero="3" titulo="Documentación vigente" ayuda="Registre las fechas de vencimiento. El sistema alertará con {{ config('inpe.dias_alerta.documentos_vehiculo') }} días de anticipación.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="fecha_soat">Vencimiento del SOAT</x-label>
            <input type="date" name="fecha_soat" id="fecha_soat" class="form-control" value="{{ old('fecha_soat', $f('fecha_soat')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_revision_tecnica">Vencimiento de la revisión técnica</x-label>
            <input type="date" name="fecha_revision_tecnica" id="fecha_revision_tecnica" class="form-control" value="{{ old('fecha_revision_tecnica', $f('fecha_revision_tecnica')) }}">
        </div>
        <div class="col-md-4">
            <x-label for="fecha_seguro">Vencimiento del seguro vehicular</x-label>
            <input type="date" name="fecha_seguro" id="fecha_seguro" class="form-control" value="{{ old('fecha_seguro', $f('fecha_seguro')) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="4" titulo="Asignación y estado">
    <div class="row g-3">
        <div class="col-md-4">
            <x-label for="estado" requerido>Estado</x-label>
            <select name="estado" id="estado" required class="form-select @error('estado') is-invalid @enderror">
                @foreach(\App\Models\Vehiculo::ESTADOS as $k => $v)
                    <option value="{{ $k }}" @selected(old('estado', $vehiculo->estado) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <x-error campo="estado" />
        </div>
        <div class="col-md-4">
            <x-label for="area_asignada">Área asignada</x-label>
            <input type="text" name="area_asignada" id="area_asignada" maxlength="100" class="form-control" value="{{ old('area_asignada', $vehiculo->area_asignada) }}">
        </div>
        <div class="col-md-4">
            <x-label for="responsable_id">Conductor / responsable</x-label>
            <select name="responsable_id" id="responsable_id" class="form-select @error('responsable_id') is-invalid @enderror">
                <option value="">Sin asignar</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" @selected((int) old('responsable_id', $vehiculo->responsable_id) === $u->id)>{{ $u->nombre_completo }}</option>
                @endforeach
            </select>
            <x-error campo="responsable_id" />
        </div>
        <div class="col-md-6">
            <x-label for="imagen">Fotografía del vehículo</x-label>
            <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png" class="form-control @error('imagen') is-invalid @enderror">
            <div class="form-text">Formatos JPG o PNG. Tamaño máximo: 2 MB.@if($vehiculo->imagen) Si adjunta otra, reemplazará la actual.@endif</div>
            <x-error campo="imagen" />
        </div>
        <div class="col-md-6">
            <x-label for="observaciones">Observaciones</x-label>
            <textarea name="observaciones" id="observaciones" rows="3" maxlength="500" data-contador class="form-control">{{ old('observaciones', $vehiculo->observaciones) }}</textarea>
        </div>
    </div>
</x-seccion>

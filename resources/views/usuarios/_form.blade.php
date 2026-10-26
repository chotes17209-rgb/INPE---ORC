{{-- Formulario compartido de cuentas de usuario. Requiere $usuario y $roles. --}}
@php
    $esNuevo = ! $usuario->exists;
    $esPropio = $usuario->exists && $usuario->is(auth()->user());
    $rolesMarcados = old('roles', $usuario->exists ? $usuario->roles->pluck('name')->all() : []);
    $descripcionRoles = [
        'super-admin'  => 'Acceso total, incluida la gestión de administradores.',
        'admin'        => 'Gestiona usuarios y todos los módulos.',
        'contador'     => 'Registra, aprueba pagos y elimina registros contables.',
        'asistente'    => 'Registra y edita información; no aprueba pagos.',
        'visualizador' => 'Solo consulta información y reportes.',
    ];
@endphp

<x-seccion numero="1" titulo="Datos personales">
    <div class="row g-3">
        <div class="col-md-6">
            <x-label for="name" requerido>Nombres</x-label>
            <input type="text" name="name" id="name" maxlength="80" required class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}">
            <x-error campo="name" />
        </div>
        <div class="col-md-6">
            <x-label for="apellidos" requerido>Apellidos</x-label>
            <input type="text" name="apellidos" id="apellidos" maxlength="80" required class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos', $usuario->apellidos) }}">
            <x-error campo="apellidos" />
        </div>
        <div class="col-md-4">
            <x-label for="dni">DNI</x-label>
            <input type="text" name="dni" id="dni" maxlength="8" inputmode="numeric" data-solo-digitos class="form-control font-monospace @error('dni') is-invalid @enderror" value="{{ old('dni', $usuario->dni) }}">
            <x-error campo="dni" />
        </div>
        <div class="col-md-4">
            <x-label for="telefono">Teléfono</x-label>
            <input type="tel" name="telefono" id="telefono" maxlength="15" class="form-control" value="{{ old('telefono', $usuario->telefono) }}">
        </div>
        <div class="col-md-4">
            <x-label for="cargo">Cargo</x-label>
            <input type="text" name="cargo" id="cargo" maxlength="100" class="form-control" value="{{ old('cargo', $usuario->cargo) }}" placeholder="Ej.: Contador público">
        </div>
        <div class="col-md-4">
            <x-label for="area">Área</x-label>
            <input type="text" name="area" id="area" maxlength="100" class="form-control" value="{{ old('area', $usuario->area) }}">
        </div>
    </div>
</x-seccion>

<x-seccion numero="2" titulo="Cuenta de acceso">
    <div class="row g-3">
        <div class="col-md-6">
            <x-label for="email" requerido>Correo electrónico institucional</x-label>
            <input type="email" name="email" id="email" maxlength="120" required autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" placeholder="usuario@inpe-orc.gob.pe">
            <x-error campo="email" />
        </div>
        <div class="col-md-6">
            <x-label for="estado" requerido>Estado de la cuenta</x-label>
            @if($esPropio)
                <input type="hidden" name="estado" value="activo">
                <input type="text" class="form-control" value="Activo" readonly>
                <div class="form-text">No puede desactivar su propia cuenta.</div>
            @else
                <select name="estado" id="estado" class="form-select">
                    @foreach(\App\Models\User::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(old('estado', $usuario->estado) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div class="col-md-6">
            <x-label for="password" :requerido="$esNuevo">{{ $esNuevo ? 'Contraseña' : 'Nueva contraseña' }}</x-label>
            <div class="input-group">
                <input type="password" name="password" id="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" @if($esNuevo) required @endif>
                <button type="button" class="btn btn-outline-secondary" data-ver-password="#password" aria-label="Mostrar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <div class="form-text">Mínimo 8 caracteres, con letras y números.{{ $esNuevo ? '' : ' Déjela en blanco para conservar la actual.' }}</div>
            <x-error campo="password" />
        </div>
        <div class="col-md-6">
            <x-label for="password_confirmation" :requerido="$esNuevo">Confirmar contraseña</x-label>
            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="form-control" @if($esNuevo) required @endif>
        </div>
    </div>
</x-seccion>

<x-seccion numero="3" titulo="Rol en el sistema" ayuda="El rol determina las acciones que el usuario puede realizar.">
    @if($esPropio)
        <div class="callout callout--info mb-0"><i class="bi bi-info-circle-fill" aria-hidden="true"></i><div>No puede modificar sus propios roles. Solicítelo a otro administrador.</div></div>
        @foreach($rolesMarcados as $r)<input type="hidden" name="roles[]" value="{{ $r }}">@endforeach
    @else
        <div class="row g-2">
            @foreach($roles as $rol)
                <div class="col-md-6 col-xl-4">
                    <label class="form-check border rounded p-3 ps-5 h-100 mb-0 d-block" for="rol_{{ $rol->id }}" style="cursor:pointer">
                        <input type="checkbox" name="roles[]" value="{{ $rol->name }}" id="rol_{{ $rol->id }}" class="form-check-input" @checked(in_array($rol->name, $rolesMarcados, true))>
                        <span class="fw-semibold d-block">{{ \App\Models\User::nombreRol($rol->name) }}</span>
                        <span class="small text-muted">{{ $descripcionRoles[$rol->name] ?? 'Rol personalizado.' }}</span>
                    </label>
                </div>
            @endforeach
        </div>
        <x-error campo="roles" />
        <x-error campo="roles.0" />
    @endif
</x-seccion>

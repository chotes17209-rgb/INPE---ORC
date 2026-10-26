@extends('layouts.app')
@section('title', 'Usuarios')
@section('page-title', 'Usuarios del sistema')
@section('page-subtitle', 'Cuentas de acceso del personal y roles asignados.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Usuarios</li>@endsection

@section('page-actions')
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Roles y permisos</a>
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo usuario</a>
@endsection

@section('content')
<div class="card">
    <form method="GET" class="filter-bar" role="search" aria-label="Filtrar usuarios">
        <div class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label" for="f_buscar">Buscar</label>
                <input type="search" name="buscar" id="f_buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="Nombres, apellidos, correo o DNI">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_rol">Rol</label>
                <select name="rol" id="f_rol" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->name }}" @selected(request('rol') === $rol->name)>{{ \App\Models\User::nombreRol($rol->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="f_estado">Estado</label>
                <select name="estado" id="f_estado" class="form-select form-select-sm" data-autoenviar>
                    <option value="">Todos</option>
                    @foreach(\App\Models\User::ESTADOS as $k => $v)
                        <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                @if(request()->anyFilled(['buscar', 'rol', 'estado']))
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary" aria-label="Quitar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">DNI</th>
                    <th scope="col">Cargo / área</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Último acceso</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($usuarios as $u)
                @php $protegido = $u->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin'); @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.usuarios.show', $u) }}" class="d-flex align-items-center gap-2 text-reset">
                            <span class="avatar" aria-hidden="true">{{ $u->iniciales }}</span>
                            <span>
                                <span class="cell-main d-block">{{ $u->nombre_completo }}@if($u->is(auth()->user())) <span class="badge text-bg-light border ms-1">Usted</span>@endif</span>
                                <span class="cell-sub">{{ $u->email }}</span>
                            </span>
                        </a>
                    </td>
                    <td class="font-monospace">{{ $u->dni ?? '—' }}</td>
                    <td>{{ $u->cargo ?? '—' }}<span class="cell-sub">{{ $u->area }}</span></td>
                    <td>
                        @foreach($u->roles as $r)
                            <span class="etiqueta">{{ \App\Models\User::nombreRol($r->name) }}</span>
                        @endforeach
                    </td>
                    <td class="text-nowrap">
                        @if($u->ultimo_acceso)
                            <span title="{{ $u->ultimo_acceso->format('d/m/Y H:i') }}">{{ $u->ultimo_acceso->diffForHumans() }}</span>
                        @else
                            <span class="text-muted">Nunca</span>
                        @endif
                    </td>
                    <td><x-estado :color="$u->estado_badge">{{ $u->estado_label }}</x-estado></td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.usuarios.show', $u) }}" class="btn btn-sm btn-light btn-icon" title="Ver" aria-label="Ver {{ $u->nombre_completo }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            @unless($protegido)
                                <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-sm btn-light btn-icon" title="Editar" aria-label="Editar {{ $u->nombre_completo }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @unless($u->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.usuarios.toggle-estado', $u) }}"
                                          data-confirm="{{ $u->estado === 'activo' ? 'La cuenta de ' . $u->nombre_completo . ' quedará inactiva y no podrá iniciar sesión.' : 'La cuenta de ' . $u->nombre_completo . ' podrá volver a iniciar sesión.' }}"
                                          data-confirm-title="{{ $u->estado === 'activo' ? 'Desactivar cuenta' : 'Activar cuenta' }}"
                                          data-confirm-btn="{{ $u->estado === 'activo' ? 'Desactivar' : 'Activar' }}"
                                          data-confirm-variant="{{ $u->estado === 'activo' ? 'warning' : 'success' }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light btn-icon" title="{{ $u->estado === 'activo' ? 'Desactivar' : 'Activar' }}" aria-label="{{ $u->estado === 'activo' ? 'Desactivar' : 'Activar' }} cuenta">
                                            <i class="bi bi-{{ $u->estado === 'activo' ? 'pause-circle' : 'play-circle' }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endunless
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-vacio icono="people" titulo="No se encontraron usuarios" texto="Pruebe con otros criterios de búsqueda." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($usuarios->hasPages())
        <div class="paginacion">{{ $usuarios->links() }}</div>
    @endif
</div>
@endsection

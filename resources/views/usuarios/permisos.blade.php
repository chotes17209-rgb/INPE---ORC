@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Roles del usuario')
@section('page-title', 'Roles del usuario')
@section('page-subtitle', $usuario->nombre_completo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.show', $usuario) }}">{{ $usuario->nombre_completo }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Roles</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.usuarios.permisos.update', $usuario) }}" class="card" style="max-width:820px">
    @csrf
    <div class="card-body">
        <div class="row g-2">
            @foreach($roles as $rol)
                <div class="col-md-6">
                    <label class="form-check border rounded p-3 ps-5 mb-0 d-block" for="rol_{{ $rol->id }}" style="cursor:pointer">
                        <input type="checkbox" name="roles[]" value="{{ $rol->name }}" id="rol_{{ $rol->id }}" class="form-check-input" @checked($usuario->hasRole($rol->name))>
                        <span class="fw-semibold">{{ \App\Models\User::nombreRol($rol->name) }}</span>
                    </label>
                </div>
            @endforeach
        </div>
        <x-error campo="roles" />
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.usuarios.show', $usuario) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar roles</button>
    </div>
</form>
@endsection

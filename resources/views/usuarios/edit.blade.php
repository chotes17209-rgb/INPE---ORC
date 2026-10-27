@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Editar usuario')
@section('page-title', 'Editar usuario')
@section('page-subtitle', $usuario->nombre_completo . ' · ' . $usuario->email)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.show', $usuario) }}">{{ $usuario->nombre_completo }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="card" autocomplete="off">
    @csrf @method('PUT')
    <div class="card-body pb-0">
        @include('usuarios._form')
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.usuarios.show', $usuario) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Nuevo usuario')
@section('page-title', 'Nuevo usuario')
@section('page-subtitle', 'Cree una cuenta de acceso para el personal autorizado.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuevo usuario</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.usuarios.store') }}" class="card" autocomplete="off">
    @csrf
    <div class="card-body pb-0">
        @include('usuarios._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</span>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Creando…"><i class="bi bi-person-check me-1" aria-hidden="true"></i>Crear usuario</button>
    </div>
</form>
@endsection

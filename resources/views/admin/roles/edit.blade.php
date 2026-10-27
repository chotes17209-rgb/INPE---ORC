@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Editar rol')
@section('page-title', 'Editar rol')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles y permisos</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar rol</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card" style="max-width:560px">
    @csrf @method('PUT')
    <div class="card-body">
        <x-label for="name" requerido>Identificador del rol</x-label>
        <input type="text" name="name" id="name" maxlength="60" required class="form-control font-monospace @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}">
        <div class="form-text">Use letras minúsculas, números y guiones.</div>
        <x-error campo="name" />
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

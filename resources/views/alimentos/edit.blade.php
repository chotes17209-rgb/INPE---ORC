@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Editar ' . $registro->numero_registro)
@section('page-title', 'Editar registro de alimentos')
@section('page-subtitle', 'Registro ' . $registro->numero_registro . ' · ' . $registro->proveedor_razon_social)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('alimentos.registros.index') }}">Suministro de alimentos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('alimentos.registros.show', $registro) }}">{{ $registro->numero_registro }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('alimentos.registros.update', $registro) }}" enctype="multipart/form-data" class="card">
    @csrf @method('PUT')
    <div class="card-body pb-0">
        @include('alimentos._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los cambios quedan registrados en la auditoría del sistema.</span>
        <a href="{{ route('alimentos.registros.show', $registro) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

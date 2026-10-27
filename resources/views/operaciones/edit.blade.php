@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Editar ' . $operacion->numero_operacion)
@section('page-title', 'Editar operación recíproca')
@section('page-subtitle', 'Operación ' . $operacion->numero_operacion . ' · ' . $operacion->entidad_proveedora)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('operaciones.registros.index') }}">Operaciones recíprocas</a></li>
    <li class="breadcrumb-item"><a href="{{ route('operaciones.registros.show', $operacion) }}">{{ $operacion->numero_operacion }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('operaciones.registros.update', $operacion) }}" enctype="multipart/form-data" class="card">
    @csrf @method('PUT')
    <div class="card-body pb-0">
        @include('operaciones._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los cambios quedan registrados en la auditoría del sistema.</span>
        <a href="{{ route('operaciones.registros.show', $operacion) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

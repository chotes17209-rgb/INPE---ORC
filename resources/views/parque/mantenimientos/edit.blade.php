@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Editar mantenimiento')
@section('page-title', 'Editar mantenimiento')
@section('page-subtitle', ($mantenimiento->vehiculo?->placa ?? '') . ' · Programado para el ' . $mantenimiento->fecha_programada->format('d/m/Y'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.index') }}">Mantenimientos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.show', $mantenimiento) }}">Detalle</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('parque.mantenimientos.update', $mantenimiento) }}" class="card">
    @csrf @method('PUT')
    <div class="card-body pb-0">
        @include('parque.mantenimientos._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los cambios quedan registrados en la auditoría del sistema.</span>
        <a href="{{ route('parque.mantenimientos.show', $mantenimiento) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

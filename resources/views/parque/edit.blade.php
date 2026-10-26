@extends('layouts.app')
@section('title', 'Editar vehículo ' . $vehiculo->placa)
@section('page-title', 'Editar vehículo')
@section('page-subtitle', $vehiculo->placa . ' · ' . $vehiculo->descripcion)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.show', $vehiculo) }}">{{ $vehiculo->placa }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('parque.vehiculos.update', $vehiculo) }}" enctype="multipart/form-data" class="card">
    @csrf @method('PUT')
    <div class="card-body pb-0">
        @include('parque._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los cambios quedan registrados en la auditoría del sistema.</span>
        <a href="{{ route('parque.vehiculos.show', $vehiculo) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar cambios</button>
    </div>
</form>
@endsection

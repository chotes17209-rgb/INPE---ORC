@extends('layouts.app')
@section('title', 'Nuevo vehículo')
@section('page-title', 'Registrar vehículo')
@section('page-subtitle', 'Incorpore una unidad vehicular al inventario del parque automotor.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuevo vehículo</li>
@endsection

@section('content')
<form method="POST" action="{{ route('parque.vehiculos.store') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body pb-0">
        @include('parque._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</span>
        <a href="{{ route('parque.vehiculos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Registrar vehículo</button>
    </div>
</form>
@endsection

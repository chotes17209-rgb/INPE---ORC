@extends('layouts.app')
@section('title', 'Nuevo registro de alimentos')
@section('page-title', 'Nuevo registro de alimentos')
@section('page-subtitle', 'Registre el comprobante de un suministro de alimentos para los establecimientos penitenciarios.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('alimentos.registros.index') }}">Suministro de alimentos</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuevo registro</li>
@endsection

@section('content')
<form method="POST" action="{{ route('alimentos.registros.store') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body pb-0">
        @include('alimentos._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</span>
        <a href="{{ route('alimentos.registros.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar registro</button>
    </div>
</form>
@endsection

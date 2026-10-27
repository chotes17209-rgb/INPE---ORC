@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Nueva operación recíproca')
@section('page-title', 'Nueva operación recíproca')
@section('page-subtitle', 'Registre el comprobante de un servicio básico, contrato o convenio con otra entidad.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('operaciones.registros.index') }}">Operaciones recíprocas</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nueva operación</li>
@endsection

@section('content')
<form method="POST" action="{{ route('operaciones.registros.store') }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-body pb-0">
        @include('operaciones._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</span>
        <a href="{{ route('operaciones.registros.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-save me-1" aria-hidden="true"></i>Guardar operación</button>
    </div>
</form>
@endsection

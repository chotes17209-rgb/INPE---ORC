@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', 'Programar mantenimiento')
@section('page-title', 'Programar mantenimiento')
@section('page-subtitle', 'Registre un mantenimiento preventivo, correctivo o de emergencia.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parque.vehiculos.index') }}">Parque automotor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parque.mantenimientos.index') }}">Mantenimientos</a></li>
    <li class="breadcrumb-item active" aria-current="page">Programar</li>
@endsection

@section('content')
<form method="POST" action="{{ route('parque.mantenimientos.store') }}" class="card">
    @csrf
    <div class="card-body pb-0">
        @include('parque.mantenimientos._form')
    </div>
    <div class="form-actions">
        <span class="form-actions__note">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</span>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('parque.mantenimientos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-texto-envio="Guardando…"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Programar mantenimiento</button>
    </div>
</form>
@endsection

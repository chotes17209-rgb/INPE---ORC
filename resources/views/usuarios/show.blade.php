@extends(request()->header('X-Modal') ? 'layouts.modal' : 'layouts.app')
@section('title', $usuario->nombre_completo)
@section('page-title', $usuario->nombre_completo)
@section('page-subtitle', ($usuario->cargo ?: 'Sin cargo registrado') . ' · ' . ($usuario->area ?: 'Sin área'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $usuario->nombre_completo }}</li>
@endsection

@php $protegido = $usuario->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin'); @endphp

@section('page-actions')
    @unless($protegido)
        <a href="{{ route('admin.usuarios.edit', $usuario) }}" data-modal class="btn btn-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
    @endunless
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <span class="avatar avatar-lg mb-3" aria-hidden="true">{{ $usuario->iniciales }}</span>
                <h2 class="h5 mb-1">{{ $usuario->nombre_completo }}</h2>
                <p class="text-muted small mb-2">{{ $usuario->email }}</p>
                <x-estado :color="$usuario->estado_badge">{{ $usuario->estado_label }}</x-estado>
                <div class="mt-3">
                    @foreach($usuario->roles as $r)
                        <span class="etiqueta">{{ \App\Models\User::nombreRol($r->name) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @unless($protegido || $usuario->is(auth()->user()))
            <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}"
                  data-confirm="Se eliminará la cuenta de {{ $usuario->nombre_completo }}. El historial de sus acciones se conservará."
                  data-confirm-title="Eliminar usuario" data-confirm-btn="Eliminar">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-link text-danger p-0 small"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar esta cuenta</button>
            </form>
        @endunless
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-vcard card-header__icon" aria-hidden="true"></i> Información de la cuenta</div>
            <div class="ficha">
                <x-dato label="DNI"><span class="font-monospace">{{ $usuario->dni }}</span></x-dato>
                <x-dato label="Teléfono">{{ $usuario->telefono }}</x-dato>
                <x-dato label="Cargo">{{ $usuario->cargo }}</x-dato>
                <x-dato label="Área">{{ $usuario->area }}</x-dato>
                <x-dato label="Último acceso">{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}</x-dato>
                <x-dato label="IP del último acceso"><span class="font-monospace">{{ $usuario->ip_ultimo_acceso }}</span></x-dato>
                <x-dato label="Cuenta creada">{{ $usuario->created_at?->format('d/m/Y') }}</x-dato>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text card-header__icon" aria-hidden="true"></i> Actividad reciente</div>
            @if($actividad->isEmpty())
                <x-vacio icono="journal" titulo="Sin actividad registrada" />
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th scope="col">Fecha</th><th scope="col">Acción</th><th scope="col">Módulo</th><th scope="col">Registro</th></tr></thead>
                        <tbody>
                        @foreach($actividad as $a)
                            <tr>
                                <td class="text-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                                <td><x-estado :color="['crear' => 'success', 'actualizar' => 'info', 'eliminar' => 'danger'][$a->accion] ?? 'secondary'">{{ ucfirst($a->accion) }}</x-estado></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $a->modulo)) }}</td>
                                <td class="font-monospace small">{{ $a->modelo }} #{{ $a->modelo_id }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

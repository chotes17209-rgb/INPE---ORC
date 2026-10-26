@extends('layouts.app')
@section('title', 'Roles y permisos')
@section('page-title', 'Roles y permisos')
@section('page-subtitle', 'Perfiles de acceso del sistema y acciones autorizadas para cada uno.')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">Roles y permisos</li>
@endsection
@section('page-actions')
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo rol</a>
@endsection

@section('content')
@php
    $matriz = [
        'Consultar información y reportes'                     => ['super-admin', 'admin', 'contador', 'asistente', 'visualizador'],
        'Registrar y editar registros'                         => ['super-admin', 'admin', 'contador', 'asistente'],
        'Registrar pagos, dar conformidad y eliminar registros' => ['super-admin', 'admin', 'contador'],
        'Administrar usuarios y roles'                         => ['super-admin', 'admin'],
    ];
    $columnas = ['super-admin', 'admin', 'contador', 'asistente', 'visualizador'];
@endphp

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-grid-3x3-gap card-header__icon" aria-hidden="true"></i> Matriz de permisos</div>
            <div class="table-responsive">
                <table class="table align-middle text-center">
                    <thead>
                        <tr>
                            <th scope="col" class="text-start" style="min-width:240px">Acción</th>
                            @foreach($columnas as $c)<th scope="col">{{ \App\Models\User::nombreRol($c) }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($matriz as $accion => $permitidos)
                        <tr>
                            <th scope="row" class="text-start fw-normal">{{ $accion }}</th>
                            @foreach($columnas as $c)
                                <td>
                                    @if(in_array($c, $permitidos, true))
                                        <i class="bi bi-check-circle-fill text-success" aria-label="Permitido"></i>
                                    @else
                                        <i class="bi bi-dash text-muted" aria-label="No permitido"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">Los roles personalizados solo otorgan acceso de consulta, salvo que se amplíe la matriz en <code>AppServiceProvider</code>.</div>
        </div>
    </div>
    <div class="col-lg-8 col-xl-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-lock card-header__icon" aria-hidden="true"></i> Roles registrados</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th scope="col">Rol</th><th scope="col" class="text-end">Usuarios</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                    <tbody>
                    @foreach($roles as $r)
                        <tr>
                            <td>
                                <span class="cell-main">{{ \App\Models\User::nombreRol($r->name) }}</span>
                                <span class="cell-sub font-monospace">{{ $r->name }}{{ in_array($r->name, $rolesSistema, true) ? ' · rol del sistema' : '' }}</span>
                            </td>
                            <td class="text-end"><a href="{{ route('admin.usuarios.index', ['rol' => $r->name]) }}">{{ $r->users_count }}</a></td>
                            <td>
                                @unless(in_array($r->name, $rolesSistema, true))
                                    <div class="table-actions">
                                        <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-light btn-icon" title="Editar" aria-label="Editar rol"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" data-confirm="Se eliminará el rol «{{ $r->name }}»." data-confirm-title="Eliminar rol" data-confirm-btn="Eliminar">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light btn-icon text-danger" title="Eliminar" aria-label="Eliminar rol"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                @else
                                    <span class="d-block text-end text-muted"><i class="bi bi-lock" title="Rol protegido" aria-label="Rol protegido"></i></span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

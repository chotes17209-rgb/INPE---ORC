<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>@yield('title', 'Inicio') · {{ config('inpe.siglas') }} {{ config('inpe.oficina') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/erp-inpe.css') }}?v={{ config('inpe.version') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<a href="#contenido" class="skip-link">Saltar al contenido principal</a>

@include('layouts.partials.sidebar')
<div class="sidebar-backdrop" aria-hidden="true"></div>

<div class="app-main">
    @include('layouts.partials.topbar')

    <main id="contenido" class="app-content" tabindex="-1">

        {{-- Encabezado de la página --}}
        @hasSection('page-title')
        <div class="page-header">
            <div>
                <h1 class="page-header__title">@yield('page-title')</h1>
                @hasSection('page-subtitle')
                    <p class="page-header__subtitle">@yield('page-subtitle')</p>
                @endif
            </div>
            @hasSection('page-actions')
                <div class="page-header__actions">@yield('page-actions')</div>
            @endif
        </div>
        @endif

        @yield('pestanas')

        {{-- Mensajes del sistema --}}
        @if($errors->any())
            <div class="callout callout--danger mb-3" role="alert">
                <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                <div>
                    <div class="callout__title">No se pudo guardar la información</div>
                    <div>Revise los campos marcados en rojo:</div>
                    <ul class="mb-0 mt-1 ps-3 small">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif
        @if(session('info'))
            <div class="callout callout--info mb-3" role="status">
                <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="app-footer">
        <span>{{ config('inpe.institucion') }} — {{ config('inpe.oficina') }} · {{ config('inpe.area') }}</span>
        <span>{{ config('inpe.sistema') }} v{{ config('inpe.version') }} · &copy; {{ date('Y') }}</span>
    </footer>
</div>

{{-- Mensajes emergentes --}}
<div class="toast-container position-fixed top-0 end-0 p-3 mt-5" id="toastContainer" aria-live="polite">
    @foreach(['success' => 'check-circle-fill', 'error' => 'x-octagon-fill'] as $tipo => $icono)
        @if(session($tipo))
        <div class="toast align-items-center text-bg-{{ $tipo === 'error' ? 'danger' : 'success' }} border-0" role="status">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2"><i class="bi bi-{{ $icono }}" aria-hidden="true"></i>{{ session($tipo) }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
        @endif
    @endforeach
</div>

{{-- Ventana flotante para registrar, ver y editar sin salir del listado --}}
<div class="modal fade erp-modal" id="modalFormulario" tabindex="-1" aria-labelledby="modalFormularioTitulo" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content"></div>
    </div>
</div>

@include('partials.modal-confirmar')
@can('aprobar')
    @include('partials.modal-pago')
@endcan

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('vendor-scripts')
<script src="{{ asset('js/erp-inpe.js') }}?v={{ config('inpe.version') }}"></script>
@stack('scripts')
</body>
</html>

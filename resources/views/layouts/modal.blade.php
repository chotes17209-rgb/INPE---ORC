{{--
    Contenido de una ventana flotante. Las vistas de registro, edición y detalle
    usan esta plantilla cuando se abren desde un listado (cabecera X-Modal) y la
    plantilla completa cuando se abren como página.
--}}
<div class="modal-header">
    <div class="min-w-0">
        <h2 class="modal-title" id="modalFormularioTitulo">@yield('page-title')</h2>
        @hasSection('page-subtitle')
            <div class="modal-subtitle">@yield('page-subtitle')</div>
        @endif
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar ventana"></button>
</div>
<div class="modal-body">
    @if(session('info'))
        <div class="callout callout--info mb-3" role="status">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            <div>{{ session('info') }}</div>
        </div>
    @endif
    @yield('content')
</div>
@hasSection('page-actions')
    <div class="modal-footer modal-footer--acciones">
        @yield('page-actions')
        <button type="button" class="btn btn-outline-secondary ms-auto" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Cerrar</button>
    </div>
@endif

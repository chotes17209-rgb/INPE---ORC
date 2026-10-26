@php
    $usuario = auth()->user();
    $totalNoLeidas = $totalNoLeidas ?? 0;
    $notificacionesMenu = $notificacionesMenu ?? collect();
@endphp
<header class="app-topbar">
    <button type="button" class="btn btn-light btn-icon" data-toggle-sidebar aria-label="Mostrar u ocultar el menú">
        <i class="bi bi-list fs-5" aria-hidden="true"></i>
    </button>

    <nav aria-label="Ruta de navegación" class="d-none d-sm-block">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door me-1" aria-hidden="true"></i>Inicio</a></li>
            @yield('breadcrumb')
        </ol>
    </nav>

    <div class="topbar-actions">
        <div class="topbar-date d-none d-lg-block">
            <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}
        </div>

        {{-- Notificaciones --}}
        <div class="dropdown">
            <button class="btn btn-light btn-icon position-relative" data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="Notificaciones{{ $totalNoLeidas ? ": {$totalNoLeidas} sin leer" : '' }}">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if($totalNoLeidas > 0)
                    <span class="notif-dot">{{ $totalNoLeidas > 9 ? '9+' : $totalNoLeidas }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow p-0 notif-menu">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <strong class="small">Notificaciones</strong>
                    @if($totalNoLeidas > 0)
                        <form method="POST" action="{{ route('notificaciones.marcar-todas') }}" data-no-bloquear>
                            @csrf
                            <button class="btn btn-link btn-sm p-0 small">Marcar todas como leídas</button>
                        </form>
                    @endif
                </div>
                @forelse($notificacionesMenu as $n)
                    <a href="{{ route('notificaciones.abrir', $n) }}" class="notif-item {{ $n->leida ? '' : 'unread' }}">
                        <span class="bullet bg-{{ $n->tipo === 'danger' ? 'danger' : ($n->tipo === 'warning' ? 'warning' : ($n->tipo === 'success' ? 'success' : 'primary')) }}" aria-hidden="true"></span>
                        <span class="min-w-0">
                            <span class="d-block small fw-semibold">{{ $n->titulo }}</span>
                            <span class="d-block small text-muted text-truncate">{{ $n->mensaje }}</span>
                            <span class="d-block text-muted" style="font-size:.72rem">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <x-vacio icono="bell-slash" titulo="Sin notificaciones" class="py-4" />
                @endforelse
            </div>
        </div>

        {{-- Usuario --}}
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar" aria-hidden="true">{{ $usuario->iniciales }}</span>
                <span class="text-start d-none d-md-block">
                    <span class="user-chip__name d-block">{{ $usuario->name }}</span>
                    <span class="user-chip__role d-block">{{ $usuario->rol_principal }}</span>
                </span>
                <i class="bi bi-chevron-down small" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:240px">
                <li class="px-3 py-2">
                    <div class="fw-semibold">{{ $usuario->nombre_completo }}</div>
                    <div class="small text-muted">{{ $usuario->cargo ?: $usuario->rol_principal }}</div>
                    <div class="small text-muted">{{ $usuario->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                @can('administrar')
                    <li><a class="dropdown-item" href="{{ route('admin.usuarios.show', $usuario) }}"><i class="bi bi-person-badge me-2" aria-hidden="true"></i>Mi cuenta</a></li>
                @endcan
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Cerrar sesión</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

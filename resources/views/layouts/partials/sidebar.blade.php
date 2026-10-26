@php
    $menu = [
        [
            'seccion' => null,
            'items' => [
                ['ruta' => 'dashboard', 'activo' => 'dashboard', 'icono' => 'grid-1x2', 'texto' => 'Panel de control'],
            ],
        ],
        [
            'seccion' => 'Gestión contable',
            'items' => [
                ['id' => 'menuParque', 'icono' => 'truck-front', 'texto' => 'Parque automotor', 'activo' => 'parque.*', 'hijos' => [
                    ['ruta' => 'parque.vehiculos.index', 'activo' => 'parque.vehiculos.*', 'texto' => 'Vehículos'],
                    ['ruta' => 'parque.mantenimientos.index', 'activo' => 'parque.mantenimientos.*', 'texto' => 'Mantenimientos'],
                ]],
                ['id' => 'menuAlimentos', 'icono' => 'basket3', 'texto' => 'Suministro de alimentos', 'activo' => 'alimentos.*', 'hijos' => [
                    ['ruta' => 'alimentos.registros.index', 'activo' => ['alimentos.registros.index', 'alimentos.registros.show', 'alimentos.registros.edit'], 'texto' => 'Registros'],
                    ['ruta' => 'alimentos.registros.create', 'activo' => 'alimentos.registros.create', 'texto' => 'Nuevo registro', 'permiso' => 'registrar'],
                    ['ruta' => 'alimentos.resumen-mensual', 'activo' => 'alimentos.resumen-mensual', 'texto' => 'Resumen mensual'],
                ]],
                ['id' => 'menuOperaciones', 'icono' => 'receipt', 'texto' => 'Operaciones recíprocas', 'activo' => 'operaciones.*', 'hijos' => [
                    ['ruta' => 'operaciones.registros.index', 'activo' => ['operaciones.registros.index', 'operaciones.registros.show', 'operaciones.registros.edit'], 'texto' => 'Registros'],
                    ['ruta' => 'operaciones.registros.create', 'activo' => 'operaciones.registros.create', 'texto' => 'Nueva operación', 'permiso' => 'registrar'],
                    ['ruta' => 'operaciones.resumen-mensual', 'activo' => 'operaciones.resumen-mensual', 'texto' => 'Resumen mensual'],
                ]],
            ],
        ],
        [
            'seccion' => 'Análisis',
            'items' => [
                ['ruta' => 'reportes.index', 'activo' => 'reportes.index', 'icono' => 'bar-chart-line', 'texto' => 'Reportes'],
                ['ruta' => 'reportes.balance-presupuestal', 'activo' => 'reportes.balance-presupuestal', 'icono' => 'clipboard-data', 'texto' => 'Ejecución del gasto'],
                ['ruta' => 'ia.index', 'activo' => 'ia.*', 'icono' => 'stars', 'texto' => 'Asistente inteligente'],
            ],
        ],
        [
            'seccion' => 'Administración',
            'permiso' => 'administrar',
            'items' => [
                ['ruta' => 'admin.usuarios.index', 'activo' => 'admin.usuarios.*', 'icono' => 'people', 'texto' => 'Usuarios'],
                ['ruta' => 'admin.roles.index', 'activo' => 'admin.roles.*', 'icono' => 'shield-lock', 'texto' => 'Roles y permisos'],
            ],
        ],
    ];
@endphp

<aside class="app-sidebar" aria-label="Menú principal">
    <a href="{{ route('dashboard') }}" class="app-sidebar__brand">
        <span class="brand-mark" aria-hidden="true">{{ config('inpe.siglas') }}</span>
        <span>
            <span class="brand-title d-block">{{ config('inpe.oficina') }}</span>
            <span class="brand-sub d-block">{{ config('inpe.sistema') }}</span>
        </span>
    </a>

    <nav class="app-sidebar__nav">
        @foreach($menu as $grupo)
            @continue(isset($grupo['permiso']) && auth()->user()->cannot($grupo['permiso']))
            @if($grupo['seccion'])
                <div class="nav-heading">{{ $grupo['seccion'] }}</div>
            @endif
            <ul class="list-unstyled mb-0">
                @foreach($grupo['items'] as $item)
                    @if(isset($item['hijos']))
                        @php $abierto = request()->routeIs($item['activo']); @endphp
                        <li>
                            <a class="nav-link {{ $abierto ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#{{ $item['id'] }}"
                               role="button" aria-expanded="{{ $abierto ? 'true' : 'false' }}" aria-controls="{{ $item['id'] }}">
                                <i class="bi bi-{{ $item['icono'] }}" aria-hidden="true"></i>
                                <span>{{ $item['texto'] }}</span>
                                <i class="bi bi-chevron-down chevron" aria-hidden="true"></i>
                            </a>
                            <ul class="collapse list-unstyled nav-sub {{ $abierto ? 'show' : '' }}" id="{{ $item['id'] }}">
                                @foreach($item['hijos'] as $hijo)
                                    @continue(isset($hijo['permiso']) && auth()->user()->cannot($hijo['permiso']))
                                    <li>
                                        <a href="{{ route($hijo['ruta']) }}" class="nav-link {{ request()->routeIs($hijo['activo']) ? 'active' : '' }}"
                                           @if(request()->routeIs($hijo['activo'])) aria-current="page" @endif>{{ $hijo['texto'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li>
                            <a href="{{ route($item['ruta']) }}" class="nav-link {{ request()->routeIs($item['activo']) ? 'active' : '' }}"
                               @if(request()->routeIs($item['activo'])) aria-current="page" @endif>
                                <i class="bi bi-{{ $item['icono'] }}" aria-hidden="true"></i>
                                <span>{{ $item['texto'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="app-sidebar__footer">
        {{ config('inpe.area') }} · {{ config('inpe.sede') }}<br>
        Versión {{ config('inpe.version') }}
    </div>
</aside>

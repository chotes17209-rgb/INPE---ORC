@php
    // Un solo acceso por módulo. Dentro de cada módulo, las pestañas llevan a sus
    // secciones (registros, resumen mensual…) y los registros se crean, ven y
    // editan en ventanas flotantes desde el mismo listado.
    $menu = [
        [
            'seccion' => null,
            'items' => [
                ['ruta' => 'dashboard', 'activo' => 'dashboard', 'icono' => 'house-door', 'texto' => 'Inicio'],
            ],
        ],
        [
            'seccion' => 'Gestión administrativa',
            'items' => [
                ['ruta' => 'parque.vehiculos.index', 'activo' => 'parque.*', 'icono' => 'truck-front', 'texto' => 'Parque automotor'],
                ['ruta' => 'alimentos.registros.index', 'activo' => 'alimentos.*', 'icono' => 'basket3', 'texto' => 'Suministro de alimentos'],
                ['ruta' => 'operaciones.registros.index', 'activo' => 'operaciones.*', 'icono' => 'receipt', 'texto' => 'Operaciones recíprocas'],
            ],
        ],
        [
            'seccion' => 'Consultas',
            'items' => [
                ['ruta' => 'reportes.index', 'activo' => 'reportes.*', 'icono' => 'bar-chart-line', 'texto' => 'Reportes'],
                ['ruta' => 'ia.index', 'activo' => 'ia.*', 'icono' => 'stars', 'texto' => 'Asistente inteligente'],
            ],
        ],
        [
            'seccion' => 'Administración',
            'permiso' => 'administrar',
            'items' => [
                ['ruta' => 'admin.usuarios.index', 'activo' => 'admin.*', 'icono' => 'people', 'texto' => 'Usuarios y permisos'],
            ],
        ],
    ];
@endphp

<aside class="app-sidebar" aria-label="Menú principal">
    <a href="{{ route('dashboard') }}" class="app-sidebar__brand">
        <img src="{{ asset('img/inpe-escudo.png') }}" alt="" class="brand-escudo" width="46" height="51">
        <span>
            <span class="brand-title d-block">{{ config('inpe.siglas') }} · {{ config('inpe.oficina') }}</span>
            <span class="brand-sub d-block">{{ config('inpe.area') }}</span>
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
                    @php $activo = request()->routeIs($item['activo']); @endphp
                    <li>
                        <a href="{{ route($item['ruta']) }}" class="nav-link {{ $activo ? 'active' : '' }}" @if($activo) aria-current="page" @endif>
                            <i class="bi bi-{{ $item['icono'] }}" aria-hidden="true"></i>
                            <span>{{ $item['texto'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="app-sidebar__footer">
        {{ config('inpe.institucion') }}<br>
        {{ config('inpe.sede') }} · v{{ config('inpe.version') }}
    </div>
</aside>

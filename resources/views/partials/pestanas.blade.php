{{-- Pestañas de un módulo. Uso: @include('partials.pestanas', ['modulo' => 'alimentos']) --}}
@php
    $pestanas = [
        'parque' => [
            ['ruta' => 'parque.vehiculos.index', 'activo' => 'parque.vehiculos.*', 'icono' => 'truck-front', 'texto' => 'Vehículos'],
            ['ruta' => 'parque.mantenimientos.index', 'activo' => 'parque.mantenimientos.*', 'icono' => 'tools', 'texto' => 'Mantenimientos'],
        ],
        'alimentos' => [
            ['ruta' => 'alimentos.registros.index', 'activo' => 'alimentos.registros.*', 'icono' => 'list-ul', 'texto' => 'Registros'],
            ['ruta' => 'alimentos.resumen-mensual', 'activo' => 'alimentos.resumen-mensual', 'icono' => 'calendar3', 'texto' => 'Resumen mensual'],
        ],
        'operaciones' => [
            ['ruta' => 'operaciones.registros.index', 'activo' => 'operaciones.registros.*', 'icono' => 'list-ul', 'texto' => 'Registros'],
            ['ruta' => 'operaciones.resumen-mensual', 'activo' => 'operaciones.resumen-mensual', 'icono' => 'calendar3', 'texto' => 'Resumen mensual'],
        ],
        'reportes' => [
            ['ruta' => 'reportes.index', 'activo' => 'reportes.index', 'icono' => 'file-earmark-bar-graph', 'texto' => 'Reportes'],
            ['ruta' => 'reportes.balance-presupuestal', 'activo' => 'reportes.balance-presupuestal', 'icono' => 'clipboard-data', 'texto' => 'Ejecución del gasto'],
        ],
        'admin' => [
            ['ruta' => 'admin.usuarios.index', 'activo' => 'admin.usuarios.*', 'icono' => 'people', 'texto' => 'Usuarios'],
            ['ruta' => 'admin.roles.index', 'activo' => 'admin.roles.*', 'icono' => 'shield-lock', 'texto' => 'Roles y permisos'],
        ],
    ][$modulo] ?? [];
@endphp
<nav class="pestanas" aria-label="Secciones del módulo">
    @foreach($pestanas as $p)
        @php $activa = request()->routeIs($p['activo']); @endphp
        <a href="{{ route($p['ruta']) }}" class="pestanas__item {{ $activa ? 'active' : '' }}" @if($activa) aria-current="page" @endif>
            <i class="bi bi-{{ $p['icono'] }}" aria-hidden="true"></i>{{ $p['texto'] }}
        </a>
    @endforeach
</nav>

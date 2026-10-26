@extends('reportes.layouts.pdf')
@section('titulo', 'Inventario del parque automotor')
@section('subtitulo', 'Relación de vehículos, documentación y estado operativo')
@section('codigo', 'REP-PAR-' . now()->format('Ymd'))

@section('contenido')
<table class="resumen">
    <tr>
        <td><div class="et">Vehículos</div><div class="va">{{ $vehiculos->count() }}</div></td>
        <td><div class="et">Disponibles para servicio</div><div class="va">{{ $vehiculos->whereIn('estado', ['operativo', 'disponible', 'asignado'])->count() }}</div></td>
        <td><div class="et">En mantenimiento</div><div class="va">{{ $vehiculos->where('estado', 'mantenimiento')->count() }}</div></td>
        <td><div class="et">De baja</div><div class="va">{{ $vehiculos->where('estado', 'baja')->count() }}</div></td>
        <td><div class="et">Valor de adquisición</div><div class="va">S/ {{ number_format($vehiculos->sum('valor_adquisicion'), 2) }}</div></td>
    </tr>
</table>

<table class="datos">
    <thead>
        <tr>
            <th>Placa</th><th>Vehículo</th><th>Año</th><th>Clase</th><th>Combustible</th><th class="r">Kilometraje</th>
            <th>SOAT</th><th>Rev. técnica</th><th>Seguro</th><th>Responsable</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($vehiculos as $v)
        <tr>
            <td class="mono nw"><strong>{{ $v->placa }}</strong></td>
            <td>{{ $v->descripcion }}<span class="sub-celda">{{ $v->color }}</span></td>
            <td class="c">{{ $v->anio_fabricacion }}</td>
            <td>{{ $v->tipo_label }}</td>
            <td>{{ $v->combustible_label }}</td>
            <td class="r nw">{{ number_format($v->kilometraje_actual) }} km</td>
            @foreach(['fecha_soat', 'fecha_revision_tecnica', 'fecha_seguro'] as $campo)
                @php $s = $v->situacionDocumento($campo); @endphp
                <td class="nw">
                    @if($s === 'sin_registro')
                        <span class="muted">No registrado</span>
                    @else
                        {{ $v->{$campo}->format('d/m/Y') }}
                        @if($s === 'vencido')<span class="estado e-danger">VENCIDO</span>@elseif($s === 'por_vencer')<span class="estado e-warning">POR VENCER</span>@endif
                    @endif
                </td>
            @endforeach
            <td>{{ $v->responsable?->nombre_completo ?? '—' }}</td>
            <td><span class="estado e-{{ $v->estado_color }}">{{ mb_strtoupper($v->estado_label) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="11" class="c muted">No existen vehículos registrados.</td></tr>
    @endforelse
    </tbody>
</table>
<p class="nota">«Por vencer»: vence dentro de los próximos {{ config('inpe.dias_alerta.documentos_vehiculo') }} días.</p>
@endsection

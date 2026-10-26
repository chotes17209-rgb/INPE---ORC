@extends('reportes.layouts.pdf')
@section('titulo', 'Reporte de mantenimientos vehiculares')
@section('subtitulo')
    Estado: {{ $filtros['estado'] ? (\App\Models\VehiculoMantenimiento::ESTADOS[$filtros['estado']] ?? $filtros['estado']) : 'Todos' }}
    · Tipo: {{ $filtros['tipo_mantenimiento'] ? (\App\Models\VehiculoMantenimiento::TIPOS[$filtros['tipo_mantenimiento']] ?? $filtros['tipo_mantenimiento']) : 'Todos' }}
    · Vehículo: {{ $filtros['vehiculo'] ?: 'Todos' }}
    · Periodo: {{ $filtros['fecha_desde'] ? \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') : 'inicio' }} al {{ $filtros['fecha_hasta'] ? \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') : 'la fecha' }}
@endsection
@section('codigo', 'REP-MAN-' . now()->format('Ymd'))

@section('contenido')
<table class="resumen">
    <tr>
        <td><div class="et">Mantenimientos</div><div class="va">{{ $mantenimientos->count() }}</div></td>
        <td><div class="et">Completados</div><div class="va">{{ $mantenimientos->where('estado', 'completado')->count() }}</div></td>
        <td><div class="et">Costo estimado</div><div class="va">S/ {{ number_format($totalEstimado, 2) }}</div></td>
        <td><div class="et">Costo real</div><div class="va">S/ {{ number_format($totalReal, 2) }}</div></td>
        <td><div class="et">Gasto ejecutado</div><div class="va">S/ {{ number_format($totalGeneral, 2) }}</div></td>
    </tr>
</table>

<table class="datos">
    <thead>
        <tr>
            <th>N°</th><th>Placa</th><th>Tipo</th><th>Trabajo</th><th>Programado</th><th>Ejecutado</th><th>Taller</th>
            <th>O.S. / SIAF</th><th>Comprobante</th><th class="r">Estimado</th><th class="r">Real</th><th class="r">Total</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($mantenimientos as $i => $m)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td class="mono nw"><strong>{{ $m->vehiculo?->placa }}</strong><span class="sub-celda">{{ $m->vehiculo?->descripcion }}</span></td>
            <td>{{ $m->tipo_label }}</td>
            <td>{{ \Illuminate\Support\Str::limit($m->descripcion, 70) }}</td>
            <td class="nw">{{ $m->fecha_programada->format('d/m/Y') }}</td>
            <td class="nw">{{ $m->fecha_realizado?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $m->taller ?? '—' }}</td>
            <td>{{ $m->orden_servicio ?? '—' }}<span class="sub-celda">SIAF {{ $m->registro_siaf ?? '—' }}</span></td>
            <td class="nw">{{ $m->comprobante ?? '—' }}</td>
            <td class="r nw">{{ number_format($m->costo_estimado, 2) }}</td>
            <td class="r nw">{{ $m->costo_real !== null ? number_format($m->costo_real, 2) : '—' }}</td>
            <td class="r nw"><strong>{{ $m->total !== null ? number_format($m->total, 2) : '—' }}</strong></td>
            <td><span class="estado e-{{ $m->estado_badge }}">{{ mb_strtoupper($m->estado_label) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="13" class="c muted">No existen mantenimientos para los criterios seleccionados.</td></tr>
    @endforelse
    </tbody>
    @if($mantenimientos->count())
    <tfoot>
        <tr>
            <td colspan="9" class="r">TOTALES (S/)</td>
            <td class="r nw">{{ number_format($totalEstimado, 2) }}</td>
            <td class="r nw">{{ number_format($totalReal, 2) }}</td>
            <td class="r nw">{{ number_format($mantenimientos->whereNotNull('total')->sum('total'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
<p class="nota">El gasto ejecutado considera únicamente los mantenimientos con conformidad registrada.</p>
@endsection

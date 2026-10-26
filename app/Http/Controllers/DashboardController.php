<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\NotificacionSistema;
use App\Models\OperacionReciproca;
use App\Models\Vehiculo;
use App\Models\VehiculoMantenimiento;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Mantiene actualizados los estados antes de calcular los indicadores.
        OperacionReciproca::actualizarVencidas();
        Alimento::actualizarVencidos();

        $hoy        = today();
        $anio       = (int) date('Y');
        $diasDocs   = config('inpe.dias_alerta.documentos_vehiculo', 30);
        $diasPagos  = config('inpe.dias_alerta.operaciones', 7);

        /* ── Parque automotor ── */
        $flota = [
            'total'         => Vehiculo::count(),
            'operativos'    => Vehiculo::whereIn('estado', ['operativo', 'disponible', 'asignado'])->count(),
            'mantenimiento' => Vehiculo::where('estado', 'mantenimiento')->count(),
            'baja'          => Vehiculo::where('estado', 'baja')->count(),
        ];

        $soatsPorVencer      = Vehiculo::whereBetween('fecha_soat', [$hoy, $hoy->copy()->addDays($diasDocs)])->count();
        $soatsVencidos       = Vehiculo::where('estado', '!=', 'baja')->whereDate('fecha_soat', '<', $hoy)->count();
        $revisionesPorVencer = Vehiculo::whereBetween('fecha_revision_tecnica', [$hoy, $hoy->copy()->addDays($diasDocs)])->count();

        $mantProximos = VehiculoMantenimiento::with('vehiculo')
            ->where('estado', 'programado')
            ->whereBetween('fecha_programada', [$hoy, $hoy->copy()->addDays(7)])
            ->orderBy('fecha_programada')->get();

        $mantVencidos = VehiculoMantenimiento::with('vehiculo')
            ->whereIn('estado', ['programado', 'en_proceso'])
            ->whereDate('fecha_programada', '<', $hoy)
            ->orderBy('fecha_programada')->get();

        /* ── Indicadores de gasto del año ── */
        $gastos = [
            'mantenimiento' => VehiculoMantenimiento::where('estado', 'completado')->whereYear('fecha_realizado', $anio)->get()->sum('total_efectivo'),
            'alimentos'     => (float) Alimento::where('estado', 'pagado')->whereYear('fecha_pago', $anio)->sum('total'),
            'servicios'     => (float) OperacionReciproca::serviciosBasicos()->where('estado', 'pagado')->whereYear('fecha_pago', $anio)->sum('total'),
            'otros_pagos'   => (float) OperacionReciproca::whereNotIn('tipo_servicio', array_keys(OperacionReciproca::SERVICIOS_BASICOS))->where('estado', 'pagado')->whereYear('fecha_pago', $anio)->sum('total'),
        ];
        $gastos['total'] = array_sum($gastos);

        /* ── Obligaciones por pagar ── */
        $porPagar = [
            'operaciones_pendientes' => OperacionReciproca::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->count(),
            'operaciones_vencidas'   => OperacionReciproca::where('estado', 'vencido')->count(),
            'alimentos_pendientes'   => Alimento::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->count(),
            'alimentos_vencidos'     => Alimento::where('estado', 'vencido')->count(),
            'monto_pendiente'        => (float) OperacionReciproca::where('estado', '!=', 'pagado')->sum('total')
                                      + (float) Alimento::where('estado', '!=', 'pagado')->sum('total'),
        ];

        $proximosVencimientos = OperacionReciproca::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])
            ->whereBetween('fecha_vencimiento', [$hoy, $hoy->copy()->addDays($diasPagos)])
            ->orderBy('fecha_vencimiento')->take(6)->get();

        $alimentosPorVencer = Alimento::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])
            ->whereBetween('periodo_fin', [$hoy, $hoy->copy()->addDays(config('inpe.dias_alerta.alimentos', 7))])
            ->orderBy('periodo_fin')->take(6)->get();

        /* ── Ejecución por proveedor (operaciones recíprocas del año) ── */
        $pagoPorProveedor = OperacionReciproca::where('periodo_anio', $anio)
            ->selectRaw("entidad_proveedora, numero_contrato,
                         SUM(total) as total_registrado,
                         SUM(CASE WHEN estado = 'pagado' THEN total ELSE 0 END) as total_pagado,
                         COUNT(*) as cantidad")
            ->groupBy('entidad_proveedora', 'numero_contrato')
            ->orderByDesc('total_registrado')
            ->take(8)->get();

        /* ── Gastos pagados en los últimos 6 meses ── */
        $gastosMensuales = collect(range(5, 0))->map(function (int $i) {
            $f = Carbon::now()->startOfMonth()->subMonths($i);

            return [
                'mes'           => ucfirst($f->translatedFormat('M Y')),
                'mantenimiento' => VehiculoMantenimiento::where('estado', 'completado')->whereYear('fecha_realizado', $f->year)->whereMonth('fecha_realizado', $f->month)->get()->sum('total_efectivo'),
                'alimentos'     => (float) Alimento::where('estado', 'pagado')->whereYear('fecha_pago', $f->year)->whereMonth('fecha_pago', $f->month)->sum('total'),
                'servicios'     => (float) OperacionReciproca::where('estado', 'pagado')->whereYear('fecha_pago', $f->year)->whereMonth('fecha_pago', $f->month)->sum('total'),
            ];
        })->all();

        /* ── Distribución de operaciones por tipo de servicio ── */
        $operacionesPorServicio = OperacionReciproca::where('periodo_anio', $anio)
            ->selectRaw('tipo_servicio, SUM(total) as total_monto')
            ->groupBy('tipo_servicio')
            ->orderByDesc('total_monto')
            ->get()
            ->map(fn ($s) => [
                'servicio' => OperacionReciproca::tipos()[$s->tipo_servicio] ?? $s->tipo_servicio,
                'monto'    => (float) $s->total_monto,
            ]);

        $notificaciones = NotificacionSistema::where('user_id', auth()->id())
            ->where('leida', false)->latest()->take(5)->get();

        return view('dashboard.index', compact(
            'anio', 'flota', 'soatsPorVencer', 'soatsVencidos', 'revisionesPorVencer',
            'mantProximos', 'mantVencidos', 'gastos', 'porPagar',
            'proximosVencimientos', 'alimentosPorVencer', 'pagoPorProveedor',
            'gastosMensuales', 'operacionesPorServicio', 'notificaciones'
        ));
    }
}

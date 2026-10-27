<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use App\Models\Alimento;
use App\Models\OperacionReciproca;
use App\Models\VehiculoMantenimiento;
use App\Support\Catalogo;
use App\Support\ExportadorCsv;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $anio  = (int) $request->get('anio', date('Y'));
        $penal = $request->get('penal');

        $mantenimientos = VehiculoMantenimiento::where('estado', 'completado')->whereYear('fecha_realizado', $anio)->get();
        $alimentos      = Alimento::whereYear('fecha_emision', $anio)->when($penal, fn ($q) => $q->where('penal', $penal))->get();
        $operaciones    = OperacionReciproca::whereYear('fecha_emision', $anio)->get();

        $mantenimientoMensual = $alimentosMensual = $serviciosMensual = [];
        for ($m = 1; $m <= 12; $m++) {
            $mes = fn ($fecha) => (int) $fecha?->format('n') === $m;

            $mm = $mantenimientos->filter(fn ($x) => $mes($x->fecha_realizado));
            $aa = $alimentos->filter(fn ($x) => $mes($x->fecha_emision));
            $ss = $operaciones->filter(fn ($x) => $mes($x->fecha_emision) && array_key_exists($x->tipo_servicio, OperacionReciproca::SERVICIOS_BASICOS));

            $mantenimientoMensual[$m] = ['cantidad' => $mm->count(), 'total' => $mm->sum('total_efectivo')];
            $alimentosMensual[$m]     = ['cantidad' => $aa->count(), 'total' => $aa->sum('total'), 'pagado' => $aa->where('estado', 'pagado')->sum('total')];
            $serviciosMensual[$m]     = ['cantidad' => $ss->count(), 'total' => $ss->sum('total')];
        }

        $pagoPorProveedor = OperacionReciproca::where('periodo_anio', $anio)
            ->selectRaw("entidad_proveedora, ruc_entidad, numero_contrato,
                         SUM(total) as total_registrado,
                         SUM(CASE WHEN estado = 'pagado' THEN total ELSE 0 END) as total_pagado")
            ->groupBy('entidad_proveedora', 'ruc_entidad', 'numero_contrato')
            ->orderByDesc('total_registrado')->get();

        $alimentosPorProveedor = Alimento::whereYear('fecha_emision', $anio)
            ->when($penal, fn ($q) => $q->where('penal', $penal))
            ->selectRaw("proveedor_razon_social, proveedor_ruc, numero_contrato, penal,
                         SUM(total) as total_registrado,
                         SUM(CASE WHEN estado = 'pagado' THEN total ELSE 0 END) as total_pagado,
                         COUNT(*) as cantidad")
            ->groupBy('proveedor_razon_social', 'proveedor_ruc', 'numero_contrato', 'penal')
            ->orderByDesc('total_registrado')->get();

        $totales = [
            'mantenimiento' => $mantenimientos->sum('total_efectivo'),
            'alimentos'     => $alimentos->sum('total'),
            'serv_basicos'  => $operaciones->filter(fn ($o) => array_key_exists($o->tipo_servicio, OperacionReciproca::SERVICIOS_BASICOS))->sum('total'),
            'otros_pagos'   => $operaciones->filter(fn ($o) => array_key_exists($o->tipo_servicio, OperacionReciproca::OTROS_PAGOS))->sum('total'),
        ];

        return view('reportes.index', compact(
            'mantenimientoMensual', 'alimentosMensual', 'serviciosMensual',
            'pagoPorProveedor', 'alimentosPorProveedor', 'totales', 'anio', 'penal'
        ));
    }

    public function generalPDF(Request $request)
    {
        $anio = (int) $request->get('anio', date('Y'));

        $mantenimientos = VehiculoMantenimiento::with(['vehiculo', 'registradoPor'])->where('estado', 'completado')->whereYear('fecha_realizado', $anio)->orderByDesc('fecha_realizado')->get();
        $alimentos      = Alimento::with('registradoPor')->whereYear('fecha_emision', $anio)->orderByDesc('fecha_emision')->get();
        $operaciones    = OperacionReciproca::with('registradoPor')->whereYear('fecha_emision', $anio)->orderByDesc('fecha_emision')->get();
        $balance        = $this->calcularBalance($anio);

        return DocumentoPdf::descargar('reportes.general-pdf', compact('mantenimientos', 'alimentos', 'operaciones', 'anio', 'balance'), "reporte-general-{$anio}.pdf", 'landscape');
    }

    public function generalExcel(Request $request)
    {
        $anio        = (int) $request->get('anio', date('Y'));
        $alimentos   = Alimento::whereYear('fecha_emision', $anio)->orderBy('fecha_emision')->get();
        $operaciones = OperacionReciproca::whereYear('fecha_emision', $anio)->orderBy('fecha_emision')->get();
        $mantenim    = VehiculoMantenimiento::with('vehiculo')->where('estado', 'completado')->whereYear('fecha_realizado', $anio)->orderBy('fecha_realizado')->get();

        // Un solo listado consolidado de egresos, con el módulo de origen en la primera columna.
        $filas = collect()
            ->concat($alimentos->map(fn ($a) => [
                'Alimentos', $a->numero_registro, $a->proveedor_razon_social, $a->proveedor_ruc ?? '',
                $a->comprobante ?? '', ExportadorCsv::fecha($a->fecha_emision), $a->penal_label,
                ExportadorCsv::monto($a->total), $a->estado_label, ExportadorCsv::fecha($a->fecha_pago),
            ]))
            ->concat($operaciones->map(fn ($o) => [
                'Operaciones recíprocas', $o->numero_operacion, $o->entidad_proveedora, $o->ruc_entidad ?? '',
                $o->comprobante ?? '', ExportadorCsv::fecha($o->fecha_emision), $o->tipo_servicio_label,
                ExportadorCsv::monto($o->total), $o->estado_label, ExportadorCsv::fecha($o->fecha_pago),
            ]))
            ->concat($mantenim->map(fn ($m) => [
                'Mantenimiento vehicular', 'MANT-' . str_pad($m->id, 5, '0', STR_PAD_LEFT), $m->taller ?? '', '',
                $m->comprobante ?? '', ExportadorCsv::fecha($m->fecha_realizado), $m->vehiculo?->placa,
                ExportadorCsv::monto($m->total_efectivo), $m->estado_label, ExportadorCsv::fecha($m->fecha_realizado),
            ]));

        return ExportadorCsv::descargar(
            "reporte-general-{$anio}",
            "Reporte general consolidado de egresos — Ejercicio {$anio}",
            ['Módulo', 'N° registro', 'Proveedor / entidad', 'RUC', 'Comprobante', 'Fecha', 'Detalle', 'Importe (S/)', 'Estado', 'Fecha de pago'],
            $filas,
            [
                ['Total alimentos', '', '', '', '', '', '', ExportadorCsv::monto($alimentos->sum('total'))],
                ['Total operaciones recíprocas', '', '', '', '', '', '', ExportadorCsv::monto($operaciones->sum('total'))],
                ['Total mantenimientos', '', '', '', '', '', '', ExportadorCsv::monto($mantenim->sum('total_efectivo'))],
            ]
        );
    }

    /**
     * Balance de ejecución del gasto: por cada módulo compara lo registrado
     * (obligaciones reconocidas en el ejercicio) con lo efectivamente pagado.
     */
    public function balancePresupuestal(Request $request)
    {
        $anio    = (int) $request->get('anio', date('Y'));
        $balance = $this->calcularBalance($anio);

        return view('reportes.balance', compact('anio', 'balance'));
    }

    private function calcularBalance(int $anio): array
    {
        $alimentos   = Alimento::whereYear('fecha_emision', $anio)->get();
        $operaciones = OperacionReciproca::where('periodo_anio', $anio)->get();
        $mantenim    = VehiculoMantenimiento::whereYear('fecha_programada', $anio)->where('estado', '!=', 'cancelado')->get();

        $servicios = $operaciones->filter(fn ($o) => array_key_exists($o->tipo_servicio, OperacionReciproca::SERVICIOS_BASICOS));
        $otros     = $operaciones->reject(fn ($o) => array_key_exists($o->tipo_servicio, OperacionReciproca::SERVICIOS_BASICOS));

        $fila = fn (string $modulo, $registrado, $pagado, $vencido, int $cantidad) => [
            'modulo'     => $modulo,
            'cantidad'   => $cantidad,
            'registrado' => (float) $registrado,
            'pagado'     => (float) $pagado,
            'pendiente'  => (float) $registrado - (float) $pagado,
            'vencido'    => (float) $vencido,
            'avance'     => $registrado > 0 ? round($pagado / $registrado * 100, 1) : 0,
        ];

        $filas = [
            $fila('Suministro de alimentos', $alimentos->sum('total'), $alimentos->where('estado', 'pagado')->sum('total'), $alimentos->where('estado', 'vencido')->sum('total'), $alimentos->count()),
            $fila('Servicios básicos', $servicios->sum('total'), $servicios->where('estado', 'pagado')->sum('total'), $servicios->where('estado', 'vencido')->sum('total'), $servicios->count()),
            $fila('Otros pagos y contratos', $otros->sum('total'), $otros->where('estado', 'pagado')->sum('total'), $otros->where('estado', 'vencido')->sum('total'), $otros->count()),
            $fila('Mantenimiento vehicular', $mantenim->sum('total_efectivo'), $mantenim->where('estado', 'completado')->sum('total_efectivo'), $mantenim->filter(fn ($m) => $m->esta_atrasado)->sum('total_efectivo'), $mantenim->count()),
        ];

        $total = $fila(
            'Total',
            array_sum(array_column($filas, 'registrado')),
            array_sum(array_column($filas, 'pagado')),
            array_sum(array_column($filas, 'vencido')),
            array_sum(array_column($filas, 'cantidad')),
        );

        $mensual = [];
        for ($m = 1; $m <= 12; $m++) {
            $mensual[$m] = [
                'mes'    => Catalogo::mes($m),
                'pagado' => (float) $alimentos->filter(fn ($a) => $a->estado === 'pagado' && (int) $a->fecha_pago?->format('n') === $m && (int) $a->fecha_pago?->format('Y') === $anio)->sum('total')
                          + (float) $operaciones->filter(fn ($o) => $o->estado === 'pagado' && (int) $o->fecha_pago?->format('n') === $m && (int) $o->fecha_pago?->format('Y') === $anio)->sum('total')
                          + (float) $mantenim->filter(fn ($x) => $x->estado === 'completado' && (int) $x->fecha_realizado?->format('n') === $m)->sum('total_efectivo'),
            ];
        }

        return ['filas' => $filas, 'total' => $total, 'mensual' => $mensual];
    }
}

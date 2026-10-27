<?php

namespace App\Http\Controllers\Administrativo;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use App\Http\Requests\OperacionReciprocaRequest;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\OperacionReciproca;
use App\Support\Catalogo;
use App\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OperacionReciprocaController extends Controller
{
    public function index(Request $request)
    {
        OperacionReciproca::actualizarVencidas();

        $operaciones = $this->filtrar($request)
            ->with('registradoPor')
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total_pendiente' => OperacionReciproca::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->sum('total'),
            'count_pendiente' => OperacionReciproca::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->count(),
            'total_vencido'   => OperacionReciproca::where('estado', 'vencido')->sum('total'),
            'count_vencido'   => OperacionReciproca::where('estado', 'vencido')->count(),
            'total_pagado'    => OperacionReciproca::where('estado', 'pagado')->whereYear('fecha_pago', date('Y'))->sum('total'),
        ];

        return view('operaciones.index', compact('operaciones', 'resumen'));
    }

    public function create()
    {
        $operacion = new OperacionReciproca([
            'periodo_mes'  => (int) date('n'),
            'periodo_anio' => (int) date('Y'),
        ]);

        return view('operaciones.create', compact('operacion'));
    }

    public function store(OperacionReciprocaRequest $request)
    {
        $data = $request->validated();
        $data['registrado_por'] = auth()->id();
        $data['estado']         = 'pendiente';

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('operaciones', 'public');
        }

        $operacion = OperacionReciproca::create($data);

        return $this->exito("Se registró la operación recíproca {$operacion->numero_operacion}.", route('operaciones.registros.show', $operacion));
    }

    public function show(OperacionReciproca $operacion)
    {
        $operacion->load(['registradoPor', 'aprobadoPor']);

        return view('operaciones.show', compact('operacion'));
    }

    public function edit(OperacionReciproca $operacion)
    {
        if (! $operacion->es_editable) {
            return $this->aviso('Una operación pagada no puede modificarse.', route('operaciones.registros.show', $operacion), 'info');
        }

        return view('operaciones.edit', compact('operacion'));
    }

    public function update(OperacionReciprocaRequest $request, OperacionReciproca $operacion)
    {
        if (! $operacion->es_editable) {
            return $this->aviso('Una operación pagada no puede modificarse.', route('operaciones.registros.show', $operacion), 'info');
        }

        $data = $request->validated();

        if ($request->hasFile('archivo_adjunto')) {
            if ($operacion->archivo_adjunto) {
                Storage::disk('public')->delete($operacion->archivo_adjunto);
            }
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('operaciones', 'public');
        } else {
            unset($data['archivo_adjunto']);
        }

        // Si se amplía la fecha de vencimiento de una operación vencida, vuelve a quedar pendiente.
        if ($operacion->estado === 'vencido' && $operacion->fecha_vencimiento && $data['fecha_vencimiento'] >= today()->toDateString()) {
            $data['estado'] = 'pendiente';
        }

        $operacion->update($data);

        return $this->exito('Los cambios fueron guardados correctamente.', route('operaciones.registros.show', $operacion));
    }

    public function destroy(OperacionReciproca $operacion)
    {
        if (! $operacion->es_editable) {
            return $this->aviso('Una operación pagada no puede eliminarse.');
        }

        $operacion->delete();

        return $this->exito("Se eliminó la operación {$operacion->numero_operacion}.", route('operaciones.registros.index'), true);
    }

    public function aprobar(RegistrarPagoRequest $request, OperacionReciproca $operacion)
    {
        if (! $operacion->puede_pagarse) {
            return $this->aviso('La operación seleccionada no admite el registro de pagos.');
        }

        $operacion->update([
            ...$request->validated(),
            'estado'           => 'pagado',
            'aprobado_por'     => auth()->id(),
            'fecha_aprobacion' => now(),
        ]);

        return $this->exito("Se registró el pago de {$operacion->numero_operacion}.", url()->previous());
    }

    public function resumenMensual(Request $request)
    {
        $anio   = (int) $request->get('anio', date('Y'));
        $tipo   = $request->get('tipo_servicio');
        $estado = $request->get('estado');

        $filas = OperacionReciproca::query()
            ->where('periodo_anio', $anio)
            ->when($tipo, fn ($q) => $q->where('tipo_servicio', $tipo))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->get()
            ->groupBy('periodo_mes');

        $resumen = [];
        for ($m = 1; $m <= 12; $m++) {
            $items = $filas->get($m, collect());
            $resumen[$m] = [
                'cantidad'    => $items->count(),
                'total_mes'   => $items->sum('total'),
                'total_base'  => $items->sum('base_imponible'),
                'total_igv'   => $items->sum('igv'),
                'total_otros' => $items->sum('otros'),
                'pagado'      => $items->where('estado', 'pagado')->sum('total'),
                'pendiente'   => $items->where('estado', '!=', 'pagado')->sum('total'),
            ];
        }

        return view('operaciones.resumen', compact('resumen', 'anio', 'tipo', 'estado'));
    }

    public function reportePDF(Request $request)
    {
        $operaciones = $this->filtrar($request)->with('registradoPor')->orderByDesc('fecha_emision')->get();
        $filtros     = $this->descripcionFiltros($request);

        return DocumentoPdf::descargar('reportes.operaciones-pdf', compact('operaciones', 'filtros'), 'reporte-operaciones-reciprocas-' . date('Y-m-d') . '.pdf', 'landscape');
    }

    public function reporteExcel(Request $request)
    {
        $operaciones = $this->filtrar($request)->orderByDesc('fecha_emision')->get();

        $filas = $operaciones->map(fn (OperacionReciproca $o) => [
            $o->numero_operacion, $o->tipo_servicio_label, $o->grupo_servicio,
            $o->entidad_proveedora, $o->ruc_entidad ?? '', $o->razon_social ?? '',
            $o->numero_contrato ?? '', $o->servicio_prestado ?? '', $o->penal_pertenece ?? '',
            ExportadorCsv::fecha($o->fecha_emision), ExportadorCsv::fecha($o->fecha_vencimiento), $o->periodo,
            $o->comprobante ?? '', $o->orden_servicio ?? '', $o->registro_siaf ?? '',
            ExportadorCsv::monto($o->base_imponible), ExportadorCsv::monto($o->igv),
            ExportadorCsv::monto($o->otros), ExportadorCsv::monto($o->total),
            $o->estado_label, ExportadorCsv::fecha($o->fecha_pago),
            $o->metodo_pago ? $o->metodo_pago_label : '', $o->numero_voucher ?? '',
        ]);

        return ExportadorCsv::descargar(
            'reporte-operaciones-reciprocas-' . date('Y-m-d'),
            'Reporte de operaciones recíprocas — ' . $this->descripcionFiltros($request),
            ['N° operación', 'Tipo de servicio', 'Grupo', 'Entidad proveedora', 'RUC', 'Razón social',
             'N° contrato', 'Servicio prestado', 'Establecimiento', 'Fecha emisión', 'Fecha vencimiento', 'Periodo',
             'Comprobante', 'Orden de servicio', 'Registro SIAF',
             'Base imponible (S/)', 'IGV (S/)', 'Otros (S/)', 'Total (S/)',
             'Estado', 'Fecha de pago', 'Método de pago', 'N° voucher'],
            $filas,
            [['', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES',
              ExportadorCsv::monto($operaciones->sum('base_imponible')), ExportadorCsv::monto($operaciones->sum('igv')),
              ExportadorCsv::monto($operaciones->sum('otros')), ExportadorCsv::monto($operaciones->sum('total'))]]
        );
    }

    public function comprobantePDF(OperacionReciproca $operacion)
    {
        $operacion->load(['registradoPor', 'aprobadoPor']);

        return DocumentoPdf::descargar('reportes.operacion-comprobante-pdf', compact('operacion'), "comprobante-{$operacion->numero_operacion}.pdf", 'portrait');
    }

    /** Filtros comunes al listado y a los reportes. */
    private function filtrar(Request $request): Builder
    {
        return OperacionReciproca::query()
            ->when($request->filled('buscar'), function (Builder $query) use ($request) {
                $q = $request->buscar;
                $query->where(fn ($sq) => $sq
                    ->whereLike('numero_operacion', "%{$q}%")
                    ->orWhereLike('entidad_proveedora', "%{$q}%")
                    ->orWhereLike('ruc_entidad', "%{$q}%")
                    ->orWhereLike('numero_factura', "%{$q}%")
                    ->orWhereLike('numero_contrato', "%{$q}%"));
            })
            ->when($request->filled('tipo_servicio'), fn ($q) => $q->where('tipo_servicio', $request->tipo_servicio))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('mes'), fn ($q) => $q->where('periodo_mes', $request->mes))
            ->when($request->filled('anio'), fn ($q) => $q->where('periodo_anio', $request->anio));
    }

    private function descripcionFiltros(Request $request): string
    {
        $partes = array_filter([
            $request->filled('tipo_servicio') ? (OperacionReciproca::tipos()[$request->tipo_servicio] ?? $request->tipo_servicio) : null,
            $request->filled('estado') ? 'Estado: ' . (OperacionReciproca::ESTADOS[$request->estado] ?? $request->estado) : null,
            $request->filled('mes') ? 'Mes: ' . Catalogo::mes((int) $request->mes) : null,
            $request->filled('anio') ? 'Año: ' . $request->anio : null,
            $request->filled('buscar') ? 'Búsqueda: "' . $request->buscar . '"' : null,
        ]);

        return $partes ? implode(' · ', $partes) : 'Todos los registros';
    }
}

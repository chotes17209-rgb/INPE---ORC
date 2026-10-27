<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use App\Http\Requests\AlimentoRequest;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\Alimento;
use App\Support\Catalogo;
use App\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlimentoController extends Controller
{
    public function index(Request $request)
    {
        Alimento::actualizarVencidos();

        $registros = $this->filtrar($request)
            ->with(['registradoPor', 'aprobadoPor'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total_pendiente' => Alimento::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->sum('total'),
            'count_pendiente' => Alimento::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->count(),
            'total_vencido'   => Alimento::where('estado', 'vencido')->sum('total'),
            'count_vencido'   => Alimento::where('estado', 'vencido')->count(),
            'total_pagado'    => Alimento::where('estado', 'pagado')->whereYear('fecha_pago', date('Y'))->sum('total'),
        ];

        return view('alimentos.index', compact('registros', 'resumen'));
    }

    public function create()
    {
        return view('alimentos.create', ['registro' => new Alimento(['estado' => 'pendiente'])]);
    }

    public function store(AlimentoRequest $request)
    {
        $data = $request->validated();
        $data['registrado_por'] = auth()->id();
        $data['estado']       ??= 'pendiente';

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('alimentos', 'public');
        }

        $registro = Alimento::create($data);

        return redirect()->route('alimentos.registros.show', $registro)
            ->with('success', "Se registró el suministro de alimentos {$registro->numero_registro}.");
    }

    public function show(Alimento $registro)
    {
        $registro->load(['registradoPor', 'aprobadoPor']);

        return view('alimentos.show', compact('registro'));
    }

    public function edit(Alimento $registro)
    {
        if (! $registro->es_editable) {
            return redirect()->route('alimentos.registros.show', $registro)
                ->with('info', 'Un registro pagado no puede modificarse.');
        }

        return view('alimentos.edit', compact('registro'));
    }

    public function update(AlimentoRequest $request, Alimento $registro)
    {
        if (! $registro->es_editable) {
            return redirect()->route('alimentos.registros.show', $registro)
                ->with('info', 'Un registro pagado no puede modificarse.');
        }

        $data = array_filter($request->validated(), fn ($v, $k) => $k !== 'estado' || filled($v), ARRAY_FILTER_USE_BOTH);

        if ($request->hasFile('archivo_adjunto')) {
            if ($registro->archivo_adjunto) {
                Storage::disk('public')->delete($registro->archivo_adjunto);
            }
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('alimentos', 'public');
        } else {
            unset($data['archivo_adjunto']);
        }

        $registro->update($data);

        return redirect()->route('alimentos.registros.show', $registro)
            ->with('success', 'Los cambios fueron guardados correctamente.');
    }

    public function destroy(Alimento $registro)
    {
        if (! $registro->es_editable) {
            return back()->with('error', 'Un registro pagado no puede eliminarse.');
        }

        $registro->delete();

        return redirect()->route('alimentos.registros.index')
            ->with('success', "Se eliminó el registro {$registro->numero_registro}.");
    }

    public function aprobar(RegistrarPagoRequest $request, Alimento $registro)
    {
        if (! $registro->puede_pagarse) {
            return back()->with('error', 'El registro seleccionado no admite el registro de pagos.');
        }

        $registro->update([
            ...$request->validated(),
            'estado'           => 'pagado',
            'aprobado_por'     => auth()->id(),
            'fecha_aprobacion' => now(),
        ]);

        return back()->with('success', "Se registró el pago de {$registro->numero_registro}.");
    }

    public function resumenMensual(Request $request)
    {
        $anio   = (int) $request->get('anio', date('Y'));
        $penal  = $request->get('penal');
        $estado = $request->get('estado');

        $filas = Alimento::query()
            ->whereYear('fecha_emision', $anio)
            ->when($penal, fn ($q) => $q->where('penal', $penal))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->get()
            ->groupBy(fn ($a) => (int) $a->fecha_emision->format('n'));

        $resumen = [];
        for ($m = 1; $m <= 12; $m++) {
            $items = $filas->get($m, collect());
            $resumen[$m] = [
                'cantidad'    => $items->count(),
                'total_base'  => $items->sum('base_imponible'),
                'total_igv'   => $items->sum('igv'),
                'total_otros' => $items->sum('otros'),
                'total_mes'   => $items->sum('total'),
                'pagado'      => $items->where('estado', 'pagado')->sum('total'),
            ];
        }

        return view('alimentos.resumen', compact('resumen', 'anio', 'penal', 'estado'));
    }

    public function reportePDF(Request $request)
    {
        $registros = $this->filtrar($request)->with('registradoPor')->orderByDesc('fecha_emision')->get();
        $filtros   = $this->descripcionFiltros($request);

        return DocumentoPdf::descargar('reportes.alimentos-pdf', compact('registros', 'filtros'), 'reporte-alimentos-' . date('Y-m-d') . '.pdf', 'landscape');
    }

    public function reporteExcel(Request $request)
    {
        $registros = $this->filtrar($request)->orderByDesc('fecha_emision')->get();

        $filas = $registros->map(fn (Alimento $r) => [
            $r->numero_registro, $r->penal_label, $r->proveedor_razon_social, $r->proveedor_ruc ?? '',
            $r->proveedor_nombre ?? '', $r->numero_contrato ?? '',
            ExportadorCsv::fecha($r->periodo_inicio), ExportadorCsv::fecha($r->periodo_fin),
            $r->orden_servicio ?? '', ExportadorCsv::fecha($r->fecha_orden_servicio), $r->registro_siaf ?? '',
            $r->garantia_descripcion ?? '', $r->garantia_monto !== null ? ExportadorCsv::monto($r->garantia_monto) : '',
            ExportadorCsv::fecha($r->garantia_vencimiento),
            $r->comprobante ?? '', ExportadorCsv::fecha($r->fecha_emision),
            ExportadorCsv::monto($r->base_imponible), ExportadorCsv::monto($r->igv),
            ExportadorCsv::monto($r->otros), ExportadorCsv::monto($r->total),
            $r->estado_label, ExportadorCsv::fecha($r->fecha_pago),
            $r->metodo_pago ? $r->metodo_pago_label : '', $r->numero_voucher ?? '',
        ]);

        return ExportadorCsv::descargar(
            'reporte-alimentos-' . date('Y-m-d'),
            'Reporte de suministro de alimentos — ' . $this->descripcionFiltros($request),
            ['N° registro', 'Establecimiento penal', 'Proveedor', 'RUC', 'Nombre comercial', 'N° contrato',
             'Periodo desde', 'Periodo hasta', 'Orden de servicio', 'Fecha O.S.', 'Registro SIAF',
             'Garantía', 'Monto garantía (S/)', 'Venc. garantía', 'Comprobante', 'Fecha emisión',
             'Base imponible (S/)', 'IGV (S/)', 'Otros (S/)', 'Total (S/)',
             'Estado', 'Fecha de pago', 'Método de pago', 'N° voucher'],
            $filas,
            [['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES',
              ExportadorCsv::monto($registros->sum('base_imponible')), ExportadorCsv::monto($registros->sum('igv')),
              ExportadorCsv::monto($registros->sum('otros')), ExportadorCsv::monto($registros->sum('total'))]]
        );
    }

    public function comprobantePDF(Alimento $registro)
    {
        $registro->load(['registradoPor', 'aprobadoPor']);

        return DocumentoPdf::descargar('reportes.alimento-comprobante-pdf', compact('registro'), "comprobante-{$registro->numero_registro}.pdf", 'portrait');
    }

    /** Filtros comunes al listado y a los reportes. */
    private function filtrar(Request $request): Builder
    {
        return Alimento::query()
            ->when($request->filled('buscar'), function (Builder $query) use ($request) {
                $q = $request->buscar;
                $query->where(fn ($sq) => $sq
                    ->whereLike('numero_registro', "%{$q}%")
                    ->orWhereLike('proveedor_razon_social', "%{$q}%")
                    ->orWhereLike('proveedor_ruc', "%{$q}%")
                    ->orWhereLike('numero_contrato', "%{$q}%")
                    ->orWhereLike('orden_servicio', "%{$q}%"));
            })
            ->when($request->filled('penal'), fn ($q) => $q->where('penal', $request->penal))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('mes'), fn ($q) => $q->whereMonth('fecha_emision', $request->mes))
            ->when($request->filled('anio'), fn ($q) => $q->whereYear('fecha_emision', $request->anio));
    }

    private function descripcionFiltros(Request $request): string
    {
        $partes = array_filter([
            $request->filled('penal') ? (Alimento::PENALES[$request->penal] ?? $request->penal) : null,
            $request->filled('estado') ? 'Estado: ' . (Alimento::ESTADOS[$request->estado] ?? $request->estado) : null,
            $request->filled('mes') ? 'Mes: ' . Catalogo::mes((int) $request->mes) : null,
            $request->filled('anio') ? 'Año: ' . $request->anio : null,
            $request->filled('buscar') ? 'Búsqueda: "' . $request->buscar . '"' : null,
        ]);

        return $partes ? implode(' · ', $partes) : 'Todos los registros';
    }
}

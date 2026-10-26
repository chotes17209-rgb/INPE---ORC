<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use App\Http\Requests\MantenimientoRequest;
use App\Models\Vehiculo;
use App\Models\VehiculoMantenimiento;
use App\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoMantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $mantenimientos = $this->filtrar($request)
            ->with(['vehiculo', 'registradoPor'])
            ->orderByRaw("CASE WHEN estado IN ('programado','en_proceso') THEN 0 ELSE 1 END")
            ->orderBy('fecha_programada')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'programados' => VehiculoMantenimiento::where('estado', 'programado')->count(),
            'en_proceso'  => VehiculoMantenimiento::where('estado', 'en_proceso')->count(),
            'atrasados'   => VehiculoMantenimiento::whereIn('estado', ['programado', 'en_proceso'])->whereDate('fecha_programada', '<', today())->count(),
            'gasto_anio'  => VehiculoMantenimiento::where('estado', 'completado')->whereYear('fecha_realizado', date('Y'))->get()->sum('total_efectivo'),
        ];

        return view('parque.mantenimientos.index', compact('mantenimientos', 'resumen'));
    }

    public function create(Request $request)
    {
        $mantenimiento = new VehiculoMantenimiento([
            'vehiculo_id'        => $request->integer('vehiculo_id') ?: null,
            'tipo_mantenimiento' => 'preventivo',
            'fecha_programada'   => today(),
            'costo_estimado'     => 0,
        ]);

        return view('parque.mantenimientos.create', [
            'mantenimiento' => $mantenimiento,
            'vehiculos'     => $this->vehiculosDisponibles(),
        ]);
    }

    public function store(MantenimientoRequest $request)
    {
        $data = $request->validated();
        $data['registrado_por'] = auth()->id();
        $data['estado']         = 'programado';

        $mantenimiento = DB::transaction(function () use ($data) {
            $mantenimiento = VehiculoMantenimiento::create($data);
            $mantenimiento->vehiculo->update(['estado' => 'mantenimiento']);

            return $mantenimiento;
        });

        return redirect()->route('parque.mantenimientos.show', $mantenimiento)
            ->with('success', "Se programó el mantenimiento del vehículo {$mantenimiento->vehiculo->placa}.");
    }

    public function show(VehiculoMantenimiento $mantenimiento)
    {
        $mantenimiento->load(['vehiculo', 'registradoPor', 'aprobadoPor']);

        return view('parque.mantenimientos.show', compact('mantenimiento'));
    }

    public function edit(VehiculoMantenimiento $mantenimiento)
    {
        if (! $mantenimiento->esta_abierto) {
            return redirect()->route('parque.mantenimientos.show', $mantenimiento)
                ->with('info', 'Un mantenimiento completado o cancelado no puede modificarse.');
        }

        return view('parque.mantenimientos.edit', [
            'mantenimiento' => $mantenimiento,
            'vehiculos'     => $this->vehiculosDisponibles(),
        ]);
    }

    public function update(MantenimientoRequest $request, VehiculoMantenimiento $mantenimiento)
    {
        if (! $mantenimiento->esta_abierto) {
            return redirect()->route('parque.mantenimientos.show', $mantenimiento)
                ->with('info', 'Un mantenimiento completado o cancelado no puede modificarse.');
        }

        // El vehículo de un mantenimiento ya registrado no se cambia.
        $mantenimiento->update(collect($request->validated())->except('vehiculo_id')->all());

        return redirect()->route('parque.mantenimientos.show', $mantenimiento)
            ->with('success', 'Los cambios fueron guardados correctamente.');
    }

    public function destroy(VehiculoMantenimiento $mantenimiento)
    {
        if ($mantenimiento->estado === 'completado') {
            return back()->with('error', 'Un mantenimiento completado no puede eliminarse.');
        }

        DB::transaction(function () use ($mantenimiento) {
            $mantenimiento->delete();
            $this->liberarVehiculo($mantenimiento->vehiculo);
        });

        return redirect()->route('parque.mantenimientos.index')
            ->with('success', 'Se eliminó el mantenimiento programado.');
    }

    public function completar(VehiculoMantenimiento $mantenimiento)
    {
        if (! $mantenimiento->esta_abierto) {
            return redirect()->route('parque.mantenimientos.show', $mantenimiento)
                ->with('info', 'Este mantenimiento ya fue cerrado.');
        }

        $mantenimiento->load('vehiculo');

        return view('parque.mantenimientos.completar', compact('mantenimiento'));
    }

    public function marcarCompleto(Request $request, VehiculoMantenimiento $mantenimiento)
    {
        if (! $mantenimiento->esta_abierto) {
            return redirect()->route('parque.mantenimientos.show', $mantenimiento)
                ->with('info', 'Este mantenimiento ya fue cerrado.');
        }

        $kmMinimo = $mantenimiento->vehiculo->kilometraje_actual ?? 0;

        $data = $request->validate([
            'fecha_realizado'           => ['required', 'date', 'before_or_equal:today'],
            'costo_real'                => ['required', 'numeric', 'min:0'],
            'total'                     => ['nullable', 'numeric', 'min:0'],
            'factura_serie'             => ['nullable', 'string', 'max:10'],
            'factura_numero_completo'   => ['nullable', 'string', 'max:20'],
            'factura_fecha'             => ['nullable', 'date'],
            'kilometraje_mantenimiento' => ['nullable', 'integer', "min:{$kmMinimo}"],
            'observaciones'             => ['nullable', 'string', 'max:500'],
        ], [
            'kilometraje_mantenimiento.min' => "El kilometraje no puede ser menor al último registrado ({$kmMinimo} km).",
        ], [
            'fecha_realizado' => 'fecha de ejecución',
            'costo_real'      => 'costo real',
        ]);

        DB::transaction(function () use ($mantenimiento, $data) {
            $mantenimiento->update([
                ...$data,
                'total'            => $data['total'] ?? $data['costo_real'],
                'estado'           => 'completado',
                'aprobado_por'     => auth()->id(),
                'fecha_aprobacion' => now(),
            ]);

            $vehiculo = $mantenimiento->vehiculo;
            if (! empty($data['kilometraje_mantenimiento'])) {
                $vehiculo->kilometraje_actual = $data['kilometraje_mantenimiento'];
            }
            $vehiculo->save();
            $this->liberarVehiculo($vehiculo);
        });

        return redirect()->route('parque.mantenimientos.show', $mantenimiento)
            ->with('success', 'El mantenimiento fue registrado como completado.');
    }

    public function reportePDF(Request $request)
    {
        $mantenimientos = $this->filtrar($request)
            ->with(['vehiculo', 'registradoPor', 'aprobadoPor'])
            ->orderBy('fecha_programada')
            ->get();

        $totalEstimado = $mantenimientos->sum('costo_estimado');
        $totalReal     = $mantenimientos->whereNotNull('costo_real')->sum('costo_real');
        $totalGeneral  = $mantenimientos->where('estado', 'completado')->sum('total_efectivo');
        $filtros       = $request->only(['estado', 'tipo_mantenimiento', 'vehiculo', 'fecha_desde', 'fecha_hasta']) + array_fill_keys(['estado', 'tipo_mantenimiento', 'vehiculo', 'fecha_desde', 'fecha_hasta'], null);

        return DocumentoPdf::descargar('reportes.mantenimientos-pdf', compact('mantenimientos', 'totalEstimado', 'totalReal', 'totalGeneral', 'filtros'), 'reporte-mantenimientos-' . date('Y-m-d') . '.pdf', 'landscape');
    }

    public function reporteExcel(Request $request)
    {
        $mantenimientos = $this->filtrar($request)
            ->with(['vehiculo', 'registradoPor'])
            ->orderBy('fecha_programada')
            ->get();

        $i = 0;
        $filas = $mantenimientos->map(function (VehiculoMantenimiento $m) use (&$i) {
            return [
                ++$i, $m->vehiculo?->placa, $m->vehiculo?->descripcion, $m->tipo_label, $m->descripcion,
                ExportadorCsv::fecha($m->fecha_programada), ExportadorCsv::fecha($m->fecha_realizado),
                $m->estado_label, $m->taller ?? '', $m->tecnico_responsable ?? '', $m->kilometraje_mantenimiento ?? '',
                $m->orden_servicio ?? '', ExportadorCsv::fecha($m->fecha_orden_servicio), $m->registro_siaf ?? '',
                ExportadorCsv::monto($m->costo_estimado),
                $m->costo_real !== null ? ExportadorCsv::monto($m->costo_real) : '',
                $m->total !== null ? ExportadorCsv::monto($m->total) : '',
                $m->comprobante ?? '', ExportadorCsv::fecha($m->factura_fecha),
                $m->registradoPor?->nombre_completo ?? '', $m->observaciones ?? '',
            ];
        });

        return ExportadorCsv::descargar(
            'reporte-mantenimientos-' . date('Y-m-d'),
            'Reporte de mantenimientos vehiculares',
            ['N°', 'Placa', 'Vehículo', 'Tipo', 'Descripción', 'Fecha programada', 'Fecha de ejecución',
             'Estado', 'Taller', 'Técnico', 'Kilometraje', 'Orden de servicio', 'Fecha O.S.', 'Registro SIAF',
             'Costo estimado (S/)', 'Costo real (S/)', 'Total (S/)', 'Comprobante', 'Fecha comprobante',
             'Registrado por', 'Observaciones'],
            $filas,
            [['', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES',
              ExportadorCsv::monto($mantenimientos->sum('costo_estimado')),
              ExportadorCsv::monto($mantenimientos->whereNotNull('costo_real')->sum('costo_real')),
              ExportadorCsv::monto($mantenimientos->whereNotNull('total')->sum('total'))]]
        );
    }

    /** El vehículo vuelve a estar operativo cuando no le quedan mantenimientos abiertos. */
    private function liberarVehiculo(?Vehiculo $vehiculo): void
    {
        if ($vehiculo && $vehiculo->estado === 'mantenimiento' && ! $vehiculo->mantenimientosPendientes()->exists()) {
            $vehiculo->update(['estado' => 'operativo']);
        }
    }

    private function vehiculosDisponibles()
    {
        return Vehiculo::where('estado', '!=', 'baja')->orderBy('placa')->get();
    }

    private function filtrar(Request $request): Builder
    {
        return VehiculoMantenimiento::query()
            ->when($request->filled('vehiculo'), fn ($q) => $q->whereHas('vehiculo', fn ($v) => $v->where('placa', 'like', '%' . $request->vehiculo . '%')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('tipo_mantenimiento'), fn ($q) => $q->where('tipo_mantenimiento', $request->tipo_mantenimiento))
            ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('fecha_programada', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('fecha_programada', '<=', $request->fecha_hasta));
    }
}

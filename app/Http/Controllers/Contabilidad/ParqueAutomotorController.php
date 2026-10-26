<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use App\Http\Requests\VehiculoRequest;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ParqueAutomotorController extends Controller
{
    public function index(Request $request)
    {
        $vehiculos = $this->filtrar($request)
            ->with('responsable')
            ->withCount(['mantenimientos as mantenimientos_abiertos_count' => fn ($q) => $q->whereIn('estado', ['programado', 'en_proceso'])])
            ->orderBy('placa')
            ->paginate(15)
            ->withQueryString();

        $hoy    = today();
        $limite = today()->addDays(config('inpe.dias_alerta.documentos_vehiculo', 30));

        $stats = [
            'total'          => Vehiculo::count(),
            'operativos'     => Vehiculo::whereIn('estado', ['operativo', 'disponible', 'asignado'])->count(),
            'mantenimiento'  => Vehiculo::where('estado', 'mantenimiento')->count(),
            'docs_vencidos'  => Vehiculo::where(fn ($q) => $q->whereDate('fecha_soat', '<', $hoy)->orWhereDate('fecha_revision_tecnica', '<', $hoy))->count(),
            'docs_por_vencer'=> Vehiculo::where(fn ($q) => $q->whereBetween('fecha_soat', [$hoy, $limite])->orWhereBetween('fecha_revision_tecnica', [$hoy, $limite]))->count(),
        ];

        return view('parque.index', compact('vehiculos', 'stats'));
    }

    public function create()
    {
        return view('parque.create', [
            'vehiculo' => new Vehiculo(['estado' => 'disponible', 'anio_fabricacion' => date('Y'), 'kilometraje_actual' => 0]),
            'usuarios' => $this->responsables(),
        ]);
    }

    public function store(VehiculoRequest $request)
    {
        $data = $request->validated();
        $data['creado_por'] = auth()->id();

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('vehiculos', 'public');
        }

        $vehiculo = Vehiculo::create($data);

        return redirect()->route('parque.vehiculos.show', $vehiculo)
            ->with('success', "Se registró el vehículo de placa {$vehiculo->placa}.");
    }

    public function show(Vehiculo $vehiculo)
    {
        $vehiculo->load(['responsable', 'creadoPor']);

        $mantenimientos = $vehiculo->mantenimientos()
            ->with('registradoPor')
            ->orderByDesc('fecha_programada')
            ->paginate(10);

        $resumenMantenimientos = [
            'total'       => $vehiculo->mantenimientos()->count(),
            'completados' => $vehiculo->mantenimientos()->where('estado', 'completado')->count(),
            'gasto'       => $vehiculo->mantenimientos()->where('estado', 'completado')->get()->sum('total_efectivo'),
        ];

        return view('parque.show', compact('vehiculo', 'mantenimientos', 'resumenMantenimientos'));
    }

    public function edit(Vehiculo $vehiculo)
    {
        return view('parque.edit', [
            'vehiculo' => $vehiculo,
            'usuarios' => $this->responsables(),
        ]);
    }

    public function update(VehiculoRequest $request, Vehiculo $vehiculo)
    {
        $data = $request->validated();

        if ($request->hasFile('imagen')) {
            if ($vehiculo->imagen) {
                Storage::disk('public')->delete($vehiculo->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('vehiculos', 'public');
        } else {
            unset($data['imagen']);
        }

        $vehiculo->update($data);

        return redirect()->route('parque.vehiculos.show', $vehiculo)
            ->with('success', 'Los datos del vehículo fueron actualizados.');
    }

    public function destroy(Vehiculo $vehiculo)
    {
        if ($vehiculo->mantenimientosPendientes()->exists()) {
            return back()->with('error', 'No se puede eliminar un vehículo con mantenimientos programados o en proceso.');
        }

        $vehiculo->delete();

        return redirect()->route('parque.vehiculos.index')
            ->with('success', "Se eliminó el vehículo de placa {$vehiculo->placa}.");
    }

    public function cambiarEstado(Request $request, Vehiculo $vehiculo)
    {
        $request->validate(['estado' => ['required', Rule::in(array_keys(Vehiculo::ESTADOS))]]);

        $vehiculo->update(['estado' => $request->estado]);

        return back()->with('success', "El vehículo {$vehiculo->placa} quedó en estado «{$vehiculo->estado_label}».");
    }

    public function historial(Vehiculo $vehiculo)
    {
        $mantenimientos = $vehiculo->mantenimientos()
            ->with('registradoPor')
            ->orderByDesc('fecha_programada')
            ->paginate(20);

        return view('parque.historial', compact('vehiculo', 'mantenimientos'));
    }

    public function reportePDF(Request $request)
    {
        $vehiculos = $this->filtrar($request)->with('responsable')->orderBy('placa')->get();

        return DocumentoPdf::descargar('reportes.parque-pdf', compact('vehiculos'), 'reporte-parque-automotor-' . date('Y-m-d') . '.pdf', 'landscape');
    }

    public function reporteExcel(Request $request)
    {
        $vehiculos = $this->filtrar($request)->with('responsable')->orderBy('placa')->get();

        $filas = $vehiculos->map(fn (Vehiculo $v) => [
            $v->placa, $v->marca, $v->modelo, $v->anio_fabricacion, $v->tipo_label, $v->combustible_label,
            $v->color ?? '', $v->numero_serie ?? '', $v->numero_motor ?? '',
            $v->kilometraje_actual, ExportadorCsv::monto($v->valor_adquisicion),
            ExportadorCsv::fecha($v->fecha_soat), ExportadorCsv::fecha($v->fecha_revision_tecnica), ExportadorCsv::fecha($v->fecha_seguro),
            $v->estado_label, $v->area_asignada ?? '', $v->responsable?->nombre_completo ?? '',
        ]);

        return ExportadorCsv::descargar(
            'reporte-parque-automotor-' . date('Y-m-d'),
            'Reporte del parque automotor',
            ['Placa', 'Marca', 'Modelo', 'Año', 'Tipo', 'Combustible', 'Color', 'N° serie', 'N° motor',
             'Kilometraje', 'Valor de adquisición (S/)', 'Venc. SOAT', 'Venc. revisión técnica', 'Venc. seguro',
             'Estado', 'Área asignada', 'Responsable'],
            $filas,
            [['Total de vehículos', $vehiculos->count()]]
        );
    }

    private function filtrar(Request $request): Builder
    {
        return Vehiculo::query()
            ->when($request->filled('buscar'), function (Builder $query) use ($request) {
                $b = $request->buscar;
                $query->where(fn ($q) => $q
                    ->where('placa', 'like', "%{$b}%")
                    ->orWhere('marca', 'like', "%{$b}%")
                    ->orWhere('modelo', 'like', "%{$b}%"));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('combustible'), fn ($q) => $q->where('combustible', $request->combustible));
    }

    private function responsables()
    {
        return User::where('estado', 'activo')->orderBy('name')->get();
    }
}

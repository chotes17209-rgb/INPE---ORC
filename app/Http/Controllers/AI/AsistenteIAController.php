<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Support\DocumentoPdf;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\VehiculoMantenimiento;
use App\Models\Alimento;
use App\Models\OperacionReciproca;
use App\Services\AI\OpenAIService;

class AsistenteIAController extends Controller
{
    public function __construct(private OpenAIService $ai) {}

    private string $systemBase = "Eres el asistente institucional del Sistema Integrado de Gestión Contable del INPE - Oficina Regional Centro. "
        . "Responde siempre en español, con lenguaje formal y técnico propio de la administración pública peruana. "
        . "Las cifras monetarias se expresan en soles (S/). "
        . "Reglas: 1) Responde únicamente lo que se consulta. 2) Sé preciso y conciso. "
        . "3) Si se solicita una lista, muestra solo los elementos que cumplen la condición. "
        . "4) Basa tus respuestas exclusivamente en los datos proporcionados; si un dato no está disponible, indícalo. "
        . "5) Evita introducciones y conclusiones innecesarias.";

    public function index()
    {
        OperacionReciproca::actualizarVencidas();
        Alimento::actualizarVencidos();

        $statsParque = [
            'total'           => Vehiculo::count(),
            'operativos'      => Vehiculo::whereIn('estado', ['operativo', 'disponible', 'asignado'])->count(),
            'mantenimiento'   => Vehiculo::where('estado', 'mantenimiento')->count(),
            'costo_mant_anio' => VehiculoMantenimiento::where('estado', 'completado')->whereYear('fecha_realizado', date('Y'))->get()->sum('total_efectivo'),
        ];
        $statsAlimentos = [
            'total'    => Alimento::count(),
            'pagado'   => Alimento::where('estado', 'pagado')->whereYear('fecha_pago', date('Y'))->sum('total'),
            'vencidos' => Alimento::where('estado', 'vencido')->count(),
        ];
        $statsOperaciones = [
            'pendiente' => OperacionReciproca::whereIn('estado', ['pendiente', 'en_proceso', 'observado'])->sum('total'),
            'vencido'   => OperacionReciproca::where('estado', 'vencido')->sum('total'),
            'pagado'    => OperacionReciproca::where('estado', 'pagado')->whereYear('fecha_pago', date('Y'))->sum('total'),
        ];

        $vehiculos     = Vehiculo::orderBy('placa')->get(['id', 'placa', 'marca', 'modelo']);
        $iaConfigurada = $this->ai->estaConfigurado();
        $modeloIA      = $this->ai->modelo();

        return view('ai.index', compact('statsParque', 'statsAlimentos', 'statsOperaciones', 'vehiculos', 'iaConfigurada', 'modeloIA'));
    }

    public function consultar(Request $request)
    {
        $request->validate(['pregunta' => 'required|string|max:500']);
        $contexto  = $this->generarContextoSistema();
        $respuesta = $this->ai->consultar(
            $request->pregunta,
            $this->systemBase . " La fecha de hoy es " . now()->format('d/m/Y') . ". DATOS DEL SISTEMA: $contexto."
        );
        return response()->json(['respuesta' => $respuesta]);
    }

    public function analizarVehiculo(Request $request)
    {
        $request->validate(['vehiculo_id' => 'required|exists:vehiculos,id']);
        $v = Vehiculo::with(['mantenimientos' => fn($q) => $q->orderBy('fecha_programada', 'desc')->take(10)])->findOrFail($request->vehiculo_id);
        $historial = $v->mantenimientos->map(fn($m) => [
            'tipo'  => $m->tipo_mantenimiento,
            'desc'  => $m->descripcion,
            'fecha' => ($m->fecha_realizado ? $m->fecha_realizado->format('d/m/Y') : 'pendiente'),
            'costo' => $m->costo_real ?? $m->costo_estimado,
        ]);
        $prompt = "Analiza el vehículo placa {$v->placa} ({$v->marca} {$v->modelo} {$v->anio_fabricacion}). Km:{$v->kilometraje_actual}. Estado:{$v->estado}. SOAT:" . ($v->fecha_soat ? $v->fecha_soat->format('d/m/Y') : 'No registrado') . ". RTV:" . ($v->fecha_revision_tecnica ? $v->fecha_revision_tecnica->format('d/m/Y') : 'No registrado') . ". Mantenimientos: " . $historial->toJson() . ". Responde con: 1) Estado actual 2) Próximo mantenimiento estimado 3) Alertas documentarias 4) Recomendaciones. Sé breve y directo.";
        $respuesta = $this->ai->consultar($prompt, $this->systemBase . " Eres experto en mantenimiento de flotas del sector público peruano.");
        return response()->json(['analisis' => $respuesta, 'vehiculo' => $v->placa]);
    }

    public function predecirMantenimiento(Request $request)
    {
        $vehiculos = Vehiculo::with(['mantenimientos' => fn($q) => $q->where('estado', 'completado')])->get();
        $datos     = $vehiculos->map(fn($v) => [
            'placa'       => $v->placa,
            'marca'       => $v->marca . ' ' . $v->modelo,
            'km'          => $v->kilometraje_actual,
            'estado'      => $v->estado,
            'ultimo_mant' => $v->mantenimientos->sortByDesc('fecha_realizado')->first() ? $v->mantenimientos->sortByDesc('fecha_realizado')->first()->fecha_realizado->format('d/m/Y') : 'Sin registro',
            'costo_acum'  => $v->mantenimientos->sum('costo_real'),
        ]);
        $prompt = "Flota INPE ORC: " . $datos->toJson() . ". Indica de forma concisa: 1) Vehículos con riesgo de fallo inminente 2) Mantenimientos necesarios en 30 días 3) Vehículos con costos excesivos 4) Prioridades de acción. Solo los que tienen problemas, no los que están bien.";
        $respuesta = $this->ai->consultar($prompt, $this->systemBase . " Eres experto en gestión de flotas del sector público peruano.");
        return response()->json(['prediccion' => $respuesta]);
    }

    public function alertasOperaciones(Request $request)
    {
        $vencidas    = OperacionReciproca::where('estado', 'vencido')->get();
        $porVencer   = OperacionReciproca::where('estado', 'pendiente')->whereBetween('fecha_vencimiento', [now(), now()->addDays(7)])->get();
        $aliVencidos = Alimento::where('estado', 'vencido')->get();
        $porServicio = OperacionReciproca::where('estado', 'pagado')->whereYear('fecha_pago', date('Y'))
            ->selectRaw('tipo_servicio, SUM(total) as total_anio')->groupBy('tipo_servicio')->get();

        $prompt =
            "Ops. vencidas (" . $vencidas->count() . "): " . $vencidas->map(fn($o) => "{$o->tipo_servicio} S/{$o->total} venció:" . $o->fecha_vencimiento->format('d/m/Y'))->join(', ') . ". " .
            "Por vencer en 7 días (" . $porVencer->count() . "): " . $porVencer->map(fn($o) => "{$o->tipo_servicio} S/{$o->total} vence:" . $o->fecha_vencimiento->format('d/m/Y'))->join(', ') . ". " .
            "Alimentos vencidos (" . $aliVencidos->count() . "): " . $aliVencidos->map(fn($a) => "{$a->proveedor_razon_social} S/{$a->total} venció:" . $a->periodo_fin->format('d/m/Y'))->join(', ') . ". " .
            "Gasto anual por servicio: " . $porServicio->map(fn($g) => "{$g->tipo_servicio}:S/{$g->total_anio}")->join(', ') . ". " .
            "Genera de forma concisa: 1) Alertas prioritarias 2) Análisis de gasto 3) Recomendaciones presupuestales.";
        $respuesta = $this->ai->consultar($prompt, $this->systemBase . " Eres experto en gestión financiera del sector público peruano.");
        return response()->json(['alertas' => $respuesta]);
    }

    public function resumenEjecutivoPDF()
    {
        $contexto = $this->generarContextoSistema();
        $prompt   = "Genera un resumen ejecutivo formal y conciso para el área de Contabilidad del INPE ORC con estas secciones:\n1. ESTADO GENERAL\n2. PARQUE AUTOMOTOR\n3. ALIMENTOS Y RACIONES\n4. OPERACIONES RECÍPROCAS\n5. ALERTAS CRÍTICAS\n6. RECOMENDACIONES\nUsa viñetas. Sé breve en cada sección.";
        $contenidoIA = $this->ai->consultar($prompt, $this->systemBase . " La fecha de hoy es " . now()->format('d/m/Y') . ". DATOS DEL SISTEMA: $contexto.");

        return DocumentoPdf::descargar('reportes.resumen-ia-pdf', ['contenido' => $contenidoIA], 'resumen-ejecutivo-' . now()->format('Y-m-d') . '.pdf', 'portrait');
    }

    private function generarContextoSistema(): string
    {
        $alimentos = Alimento::all()->map(fn($a) =>
            "[{$a->numero_registro}] " .
            "Penal:{$a->penal_label} | " .
            "Proveedor:{$a->proveedor_razon_social} (RUC:{$a->proveedor_ruc}) | " .
            "Contrato:{$a->numero_contrato} | " .
            "OS:{$a->orden_servicio} | " .
            "SIAF:{$a->registro_siaf} | " .
            "Periodo:" . ($a->periodo_inicio ? $a->periodo_inicio->format('d/m/Y') : 'N/A') . " al " . ($a->periodo_fin ? $a->periodo_fin->format('d/m/Y') : 'N/A') . " | " .
            "Comprobante:{$a->factura_serie}-{$a->factura_numero} | " .
            "Emision:" . ($a->fecha_emision ? $a->fecha_emision->format('d/m/Y') : 'N/A') . " | " .
            "Base:S/{$a->base_imponible} IGV:S/{$a->igv} Total:S/{$a->total} | " .
            "Estado:{$a->estado_label}" .
            ($a->fecha_pago ? " | PagadoEl:" . $a->fecha_pago->format('d/m/Y') : "") .
            ($a->observaciones ? " | Obs:{$a->observaciones}" : "")
        )->join("\n");

        $operaciones = OperacionReciproca::all()->map(fn($o) =>
            "[{$o->numero_operacion}] " .
            "Servicio:{$o->tipo_servicio_label} | " .
            "Proveedor:{$o->entidad_proveedora} (RUC:{$o->ruc_entidad}) | " .
            "Contrato:{$o->numero_contrato} | " .
            "Penal:{$o->penal_pertenece} | " .
            "OS:{$o->orden_servicio} | " .
            "SIAF:{$o->registro_siaf} | " .
            "Factura:{$o->factura_serie}-{$o->numero_factura} | " .
            "Emision:" . ($o->fecha_emision ? $o->fecha_emision->format('d/m/Y') : 'N/A') . " | " .
            "Vencimiento:" . ($o->fecha_vencimiento ? $o->fecha_vencimiento->format('d/m/Y') : 'N/A') . " | " .
            "Base:S/{$o->base_imponible} IGV:S/{$o->igv} Total:S/{$o->total} | " .
            "Estado:{$o->estado}" .
            ($o->fecha_pago ? " | PagadoEl:" . $o->fecha_pago->format('d/m/Y') : "") .
            ($o->observaciones ? " | Obs:{$o->observaciones}" : "")
        )->join("\n");

        $vehiculos = Vehiculo::all()->map(fn($v) =>
            "[{$v->placa}] " .
            "{$v->marca} {$v->modelo} {$v->anio_fabricacion} | " .
            "Tipo:{$v->tipo} | " .
            "Color:{$v->color} | " .
            "Combustible:{$v->combustible} | " .
            "Serie:{$v->numero_serie} | " .
            "Motor:{$v->numero_motor} | " .
            "Km:{$v->kilometraje_actual} | " .
            "KmProxMant:{$v->kilometraje_siguiente_mantenimiento} | " .
            "Estado:{$v->estado} | " .
            "Area:{$v->area_asignada} | " .
            "SOAT:" . ($v->fecha_soat ? $v->fecha_soat->format('d/m/Y') : 'No registrado') . " | " .
            "RTV:" . ($v->fecha_revision_tecnica ? $v->fecha_revision_tecnica->format('d/m/Y') : 'No registrado') . " | " .
            "Seguro:" . ($v->fecha_seguro ? $v->fecha_seguro->format('d/m/Y') : 'No registrado') . " | " .
            "ValorAdq:S/{$v->valor_adquisicion} | " .
            "FechaAdq:" . ($v->fecha_adquisicion ? $v->fecha_adquisicion->format('d/m/Y') : 'No registrado')
        )->join("\n");

        $mantenimientos = VehiculoMantenimiento::with('vehiculo')->get()->map(fn($m) =>
            "Placa:" . ($m->vehiculo ? $m->vehiculo->placa : 'N/A') . " | " .
            "Tipo:{$m->tipo_mantenimiento} | " .
            "Desc:{$m->descripcion} | " .
            "Taller:{$m->taller} | " .
            "Tecnico:{$m->tecnico_responsable} | " .
            "KmMant:{$m->kilometraje_mantenimiento} | " .
            "Programado:" . ($m->fecha_programada ? $m->fecha_programada->format('d/m/Y') : 'N/A') . " | " .
            "Realizado:" . ($m->fecha_realizado ? $m->fecha_realizado->format('d/m/Y') : 'Pendiente') . " | " .
            "OS:{$m->orden_servicio} | " .
            "SIAF:{$m->registro_siaf} | " .
            "Factura:{$m->factura_serie}-{$m->factura_numero} | " .
            "FechaFactura:" . ($m->factura_fecha ? $m->factura_fecha->format('d/m/Y') : 'N/A') . " | " .
            "CostoEst:S/{$m->costo_estimado} | " .
            "CostoReal:S/{$m->costo_real} | " .
            "Total:S/{$m->total} | " .
            "Estado:{$m->estado}" .
            ($m->observaciones ? " | Obs:{$m->observaciones}" : "")
        )->join("\n");

        return
            "=== ALIMENTOS (" . Alimento::count() . " registros) ===\n" . $alimentos .
            "\n\n=== OPERACIONES RECÍPROCAS (" . OperacionReciproca::count() . " registros) ===\n" . $operaciones .
            "\n\n=== VEHÍCULOS (" . Vehiculo::count() . " registros) ===\n" . $vehiculos .
            "\n\n=== MANTENIMIENTOS (" . VehiculoMantenimiento::count() . " registros) ===\n" . $mantenimientos;
    }
}
<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta de datos de un contribuyente (SUNAT) a través de apis.net.pe.
 * La consulta se hace desde el servidor para no exponer el token en el navegador.
 */
class ConsultaRucController extends Controller
{
    public function __invoke(string $ruc): JsonResponse
    {
        if (! preg_match('/^(10|15|16|17|20)\d{9}$/', $ruc)) {
            return response()->json(['encontrado' => false, 'mensaje' => 'El RUC debe tener 11 dígitos válidos.'], 422);
        }

        if (blank(config('services.apiperu.token'))) {
            return response()->json(['encontrado' => false, 'mensaje' => 'La consulta de RUC no está configurada (APIPERU_TOKEN).'], 503);
        }

        try {
            $datos = Cache::remember("ruc:{$ruc}", now()->addDay(), function () use ($ruc) {
                $response = Http::withToken(config('services.apiperu.token'))
                    ->acceptJson()
                    ->timeout(10)
                    ->get(rtrim(config('services.apiperu.base_url'), '/') . '/sunat/ruc', ['numero' => $ruc]);

                return $response->successful() ? $response->json() : null;
            });
        } catch (\Throwable $e) {
            Log::warning("Consulta RUC {$ruc}: " . $e->getMessage());

            return response()->json(['encontrado' => false, 'mensaje' => 'No fue posible conectar con el servicio de SUNAT.'], 502);
        }

        if (empty($datos['razonSocial'] ?? $datos['nombre'] ?? null)) {
            Cache::forget("ruc:{$ruc}");

            return response()->json(['encontrado' => false, 'mensaje' => 'No se encontró el RUC consultado.'], 404);
        }

        $direccion = collect([$datos['direccion'] ?? null, $datos['distrito'] ?? null, $datos['provincia'] ?? null, $datos['departamento'] ?? null])
            ->filter(fn ($v) => filled($v) && $v !== '-')
            ->implode(', ');

        return response()->json([
            'encontrado'       => true,
            'ruc'              => $ruc,
            'razon_social'     => $datos['razonSocial'] ?? $datos['nombre'],
            'nombre_comercial' => $datos['nombreComercial'] ?? null,
            'estado'           => $datos['estado'] ?? null,
            'condicion'        => $datos['condicion'] ?? null,
            'direccion'        => $direccion ?: null,
        ]);
    }
}

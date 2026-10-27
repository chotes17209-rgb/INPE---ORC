<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Support\ConsultaSunat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta de contribuyentes (SUNAT) desde los formularios.
 * Se hace desde el servidor para no exponer los tokens en el navegador.
 */
class ConsultaRucController extends Controller
{
    public function __construct(private readonly ConsultaSunat $sunat)
    {
    }

    /** RUC → razón social. */
    public function __invoke(string $ruc): JsonResponse
    {
        if (! preg_match('/^(10|15|16|17|20)\d{9}$/', $ruc)) {
            return response()->json(['encontrado' => false, 'mensaje' => 'El RUC debe tener 11 dígitos y empezar con 10, 15, 16, 17 o 20.'], 422);
        }

        $datos = $this->sunat->porRuc($ruc);

        if (! $datos) {
            return response()->json([
                'encontrado' => false,
                'mensaje'    => $this->sunat->hayServicioExterno()
                    ? 'No se encontró el RUC en SUNAT. Verifique el número o escriba los datos manualmente.'
                    : 'El RUC no figura entre los proveedores registrados. Escriba los datos manualmente.',
            ], 404);
        }

        return response()->json(['encontrado' => true, ...$datos]);
    }

    /** Razón social → RUC (lista de coincidencias). */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 3) {
            return response()->json(['resultados' => [], 'mensaje' => 'Escriba al menos 3 letras.']);
        }

        $resultados = $this->sunat->porNombre(mb_substr($q, 0, 120));

        return response()->json([
            'resultados' => $resultados,
            'mensaje'    => $resultados->isEmpty()
                ? ($this->sunat->buscaNombresEnSunat()
                    ? 'Sin coincidencias en SUNAT. Puede escribir los datos manualmente.'
                    : 'Sin coincidencias entre los proveedores registrados. Escriba el RUC para consultarlo en SUNAT.')
                : null,
        ]);
    }
}

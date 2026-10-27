<?php

namespace App\Support;

use App\Models\Alimento;
use App\Models\OperacionReciproca;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Consulta de contribuyentes en ambos sentidos: RUC → razón social y
 * razón social → RUC.
 *
 * Fuentes, en orden: json.pe (JSONPE_TOKEN), decolecta / apis.net.pe
 * (APIPERU_TOKEN) y el directorio de proveedores ya registrados en el sistema,
 * que funciona sin conexión ni token.
 */
class ConsultaSunat
{
    /** @return array{ruc:string, razon_social:string, nombre_comercial:?string, estado:?string, condicion:?string, direccion:?string, fuente:string}|null */
    public function porRuc(string $ruc): ?array
    {
        $resultado = Cache::remember("sunat:ruc:{$ruc}", now()->addDay(), function () use ($ruc) {
            return $this->rucJsonPe($ruc) ?? $this->rucApiPeru($ruc);
        });

        if (! $resultado) {
            Cache::forget("sunat:ruc:{$ruc}");
        }

        return $resultado ?? $this->directorio()->firstWhere('ruc', $ruc);
    }

    /** Contribuyentes cuyo nombre coincide con el texto (máximo 10). */
    public function porNombre(string $texto): Collection
    {
        $texto = Str::squish($texto);
        $buscado = Str::lower(Str::ascii($texto));

        $locales = $this->directorio()
            ->filter(fn ($p) => Str::contains(Str::lower(Str::ascii($p['razon_social'] . ' ' . $p['nombre_comercial'])), $buscado))
            ->take(5);

        $externos = mb_strlen($texto) >= 4
            ? Cache::remember('sunat:nombre:' . md5($buscado), now()->addHours(6), fn () => $this->nombreJsonPe($texto))
            : collect();

        return $locales->concat($externos ?? [])->unique('ruc')->take(10)->values();
    }

    public function hayServicioExterno(): bool
    {
        return filled(config('services.jsonpe.token')) || filled(config('services.apiperu.token'));
    }

    public function buscaNombresEnSunat(): bool
    {
        return filled(config('services.jsonpe.token'));
    }

    /* ── json.pe ─────────────────────────────────────────────────────────── */

    private function rucJsonPe(string $ruc): ?array
    {
        $d = $this->postJsonPe('/ruc', ['ruc' => $ruc]);
        if (! is_array($d) || blank($d['nombre_o_razon_social'] ?? null)) {
            return null;
        }

        return $this->normalizar([
            'ruc'          => $d['ruc'] ?? $ruc,
            'razon_social' => $d['nombre_o_razon_social'],
            'estado'       => $d['estado'] ?? null,
            'condicion'    => $d['condicion'] ?? null,
            'direccion'    => $d['direccion_completa'] ?? $d['direccion'] ?? null,
        ], 'SUNAT');
    }

    private function nombreJsonPe(string $texto): Collection
    {
        $datos = $this->postJsonPe('/ruc/razon_social', ['razon_social' => $texto]);
        if (! is_array($datos)) {
            return collect();
        }

        return collect(array_is_list($datos) ? $datos : [$datos])
            ->filter(fn ($d) => filled($d['ruc'] ?? null) && filled($d['nombre_o_razon_social'] ?? null))
            ->map(fn ($d) => $this->normalizar([
                'ruc'          => $d['ruc'],
                'razon_social' => $d['nombre_o_razon_social'],
                'estado'       => $d['estado'] ?? null,
                'condicion'    => $d['condicion'] ?? null,
                'direccion'    => $d['direccion_completa'] ?? $d['ubicacion'] ?? null,
            ], 'SUNAT'))
            ->take(10);
    }

    private function postJsonPe(string $ruta, array $cuerpo): ?array
    {
        $token = config('services.jsonpe.token');
        if (blank($token)) {
            return null;
        }

        try {
            $r = Http::withToken($token)->acceptJson()->asJson()->timeout(10)
                ->post(rtrim(config('services.jsonpe.base_url'), '/') . $ruta, $cuerpo);

            return $r->successful() && ($r->json('success') ?? true) ? $r->json('data') : null;
        } catch (\Throwable $e) {
            Log::warning("json.pe {$ruta}: " . $e->getMessage());

            return null;
        }
    }

    /* ── decolecta / apis.net.pe ─────────────────────────────────────────── */

    private function rucApiPeru(string $ruc): ?array
    {
        $token = config('services.apiperu.token');
        if (blank($token)) {
            return null;
        }

        try {
            $r = Http::withToken($token)->acceptJson()->timeout(10)
                ->get(rtrim(config('services.apiperu.base_url'), '/') . '/sunat/ruc', ['numero' => $ruc]);
            $d = $r->successful() ? $r->json() : null;
        } catch (\Throwable $e) {
            Log::warning("Consulta RUC {$ruc}: " . $e->getMessage());

            return null;
        }

        $razon = $d['razon_social'] ?? $d['razonSocial'] ?? $d['nombre'] ?? null;
        if (blank($razon)) {
            return null;
        }

        $direccion = collect([$d['direccion'] ?? null, $d['distrito'] ?? null, $d['provincia'] ?? null, $d['departamento'] ?? null])
            ->filter(fn ($v) => filled($v) && $v !== '-')
            ->implode(', ');

        return $this->normalizar([
            'ruc'              => $d['numero_documento'] ?? $d['numeroDocumento'] ?? $ruc,
            'razon_social'     => $razon,
            'nombre_comercial' => $d['nombre_comercial'] ?? $d['nombreComercial'] ?? null,
            'estado'           => $d['estado'] ?? null,
            'condicion'        => $d['condicion'] ?? null,
            'direccion'        => $direccion,
        ], 'SUNAT');
    }

    /* ── Proveedores ya registrados ──────────────────────────────────────── */

    private function directorio(): Collection
    {
        $alimentos = Alimento::query()
            ->whereNotNull('proveedor_ruc')->where('proveedor_ruc', '!=', '')
            ->latest('id')->limit(500)
            ->get(['proveedor_ruc', 'proveedor_razon_social', 'proveedor_nombre'])
            ->map(fn ($a) => $this->normalizar([
                'ruc'              => $a->proveedor_ruc,
                'razon_social'     => $a->proveedor_razon_social,
                'nombre_comercial' => $a->proveedor_nombre,
            ], 'Registrado en el sistema'));

        $operaciones = OperacionReciproca::query()
            ->whereNotNull('ruc_entidad')->where('ruc_entidad', '!=', '')
            ->latest('id')->limit(500)
            ->get(['ruc_entidad', 'razon_social', 'entidad_proveedora', 'direccion_entidad'])
            ->map(fn ($o) => $this->normalizar([
                'ruc'              => $o->ruc_entidad,
                'razon_social'     => $o->razon_social ?: $o->entidad_proveedora,
                'nombre_comercial' => $o->entidad_proveedora,
                'direccion'        => $o->direccion_entidad,
            ], 'Registrado en el sistema'));

        return $alimentos->concat($operaciones)->filter(fn ($p) => filled($p['razon_social']))->unique('ruc')->values();
    }

    private function normalizar(array $d, string $fuente): array
    {
        return [
            'ruc'              => (string) $d['ruc'],
            'razon_social'     => Str::squish((string) $d['razon_social']),
            'nombre_comercial' => filled($d['nombre_comercial'] ?? null) && ($d['nombre_comercial'] ?? '') !== '-' ? Str::squish($d['nombre_comercial']) : null,
            'estado'           => $d['estado'] ?? null,
            'condicion'        => $d['condicion'] ?? null,
            'direccion'        => filled($d['direccion'] ?? null) ? Str::squish($d['direccion']) : null,
            'fuente'           => $fuente,
        ];
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Registra en la tabla audit_logs cada creación, modificación y
 * eliminación del modelo, con el usuario, la IP y los valores cambiados.
 *
 * El modelo puede definir:
 *  - $moduloAuditoria: módulo al que pertenece (por defecto, el nombre de la tabla).
 *  - $auditoriaIgnorar: campos cuyos cambios no generan registro (p. ej. último acceso).
 */
trait RegistraAuditoria
{
    protected static function bootRegistraAuditoria(): void
    {
        static::created(fn ($modelo) => $modelo->auditar('crear', null, $modelo->getAttributes()));

        static::updated(function ($modelo) {
            $cambios = collect($modelo->getChanges())
                ->except(['updated_at', ...(property_exists($modelo, 'auditoriaIgnorar') ? $modelo->auditoriaIgnorar : [])])
                ->all();
            if ($cambios === []) {
                return;
            }
            $anteriores = collect($modelo->getOriginal())->only(array_keys($cambios))->all();
            $modelo->auditar('actualizar', $anteriores, $cambios);
        });

        static::deleted(fn ($modelo) => $modelo->auditar('eliminar', $modelo->getOriginal(), null));
    }

    protected function auditar(string $accion, ?array $anteriores, ?array $nuevos): void
    {
        // La auditoría nunca debe impedir la operación principal.
        try {
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id'            => auth()->id(),
                'accion'             => $accion,
                'modulo'             => property_exists($this, 'moduloAuditoria') ? $this->moduloAuditoria : $this->getTable(),
                'modelo'             => class_basename($this),
                'modelo_id'          => $this->getKey(),
                'valores_anteriores' => $anteriores ? $this->sinCamposSensibles($anteriores) : null,
                'valores_nuevos'     => $nuevos ? $this->sinCamposSensibles($nuevos) : null,
                'ip_address'         => $request?->ip(),
                'user_agent'         => $request ? substr((string) $request->userAgent(), 0, 255) : 'consola',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar la auditoría: ' . $e->getMessage());
        }
    }

    private function sinCamposSensibles(array $valores): array
    {
        return collect($valores)->except(['password', 'remember_token'])->all();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Alimento;
use App\Models\NotificacionSistema;
use App\Models\OperacionReciproca;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Console\Command;

class VerificarVencimientosOperaciones extends Command
{
    protected $signature = 'operaciones:verificar-vencimientos';

    protected $description = 'Actualiza los estados vencidos y genera alertas de pagos y documentos vehiculares próximos a vencer';

    public function handle(): int
    {
        $hoy  = today();
        $dias = (int) config('inpe.dias_alerta.notificacion', 3);

        $this->info('Operaciones marcadas como vencidas: ' . OperacionReciproca::actualizarVencidas());
        $this->info('Registros de alimentos marcados como vencidos: ' . Alimento::actualizarVencidos());

        $destinatarios = User::role(['super-admin', 'admin', 'contador'])->where('estado', 'activo')->get();
        $generadas = 0;

        // Pagos próximos a vencer
        OperacionReciproca::where('estado', 'pendiente')
            ->whereBetween('fecha_vencimiento', [$hoy, $hoy->copy()->addDays($dias)])
            ->get()
            ->each(function (OperacionReciproca $op) use ($destinatarios, &$generadas) {
                $generadas += $this->notificar(
                    $destinatarios,
                    'operaciones',
                    route('operaciones.registros.show', $op),
                    'Pago próximo a vencer',
                    "La operación {$op->numero_operacion} ({$op->tipo_servicio_label}) por S/ " . number_format($op->total, 2) . " vence el {$op->fecha_vencimiento->format('d/m/Y')}.",
                    'warning'
                );
            });

        // Documentos vehiculares próximos a vencer
        Vehiculo::where('estado', '!=', 'baja')
            ->where(fn ($q) => $q
                ->whereBetween('fecha_soat', [$hoy, $hoy->copy()->addDays($dias)])
                ->orWhereBetween('fecha_revision_tecnica', [$hoy, $hoy->copy()->addDays($dias)]))
            ->get()
            ->each(function (Vehiculo $v) use ($destinatarios, $dias, &$generadas) {
                foreach (['fecha_soat' => 'SOAT', 'fecha_revision_tecnica' => 'revisión técnica'] as $campo => $documento) {
                    if ($v->situacionDocumento($campo, $dias) === 'por_vencer') {
                        $generadas += $this->notificar(
                            $destinatarios,
                            'parque',
                            route('parque.vehiculos.show', $v),
                            "Vencimiento de {$documento}",
                            "El {$documento} del vehículo {$v->placa} vence el {$v->{$campo}->format('d/m/Y')}.",
                            'warning'
                        );
                    }
                }
            });

        $this->info("Notificaciones generadas: {$generadas}");

        return self::SUCCESS;
    }

    /** Crea la notificación para cada destinatario, una sola vez por día y por registro. */
    private function notificar($destinatarios, string $modulo, string $url, string $titulo, string $mensaje, string $tipo): int
    {
        $creadas = 0;

        foreach ($destinatarios as $usuario) {
            $existe = NotificacionSistema::where('user_id', $usuario->id)
                ->where('url_accion', $url)
                ->where('titulo', $titulo)
                ->whereDate('created_at', today())
                ->exists();

            if (! $existe) {
                NotificacionSistema::create([
                    'user_id'    => $usuario->id,
                    'titulo'     => $titulo,
                    'mensaje'    => $mensaje,
                    'tipo'       => $tipo,
                    'modulo'     => $modulo,
                    'url_accion' => $url,
                ]);
                $creadas++;
            }
        }

        return $creadas;
    }
}

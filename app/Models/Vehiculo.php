<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes, RegistraAuditoria;

    protected string $moduloAuditoria = 'parque_automotor';

    const TIPOS = [
        'camioneta' => 'Camioneta',
        'sedan'     => 'Sedán',
        'furgon'    => 'Furgón',
        'bus'       => 'Bus',
        'camion'    => 'Camión',
        'moto'      => 'Motocicleta',
        'otro'      => 'Otro',
    ];

    const COMBUSTIBLES = [
        'gasolina'  => 'Gasolina',
        'diesel'    => 'Diésel',
        'gnv'       => 'GNV',
        'hibrido'   => 'Híbrido',
        'electrico' => 'Eléctrico',
    ];

    const ESTADOS = [
        'operativo'     => 'Operativo',
        'disponible'    => 'Disponible',
        'asignado'      => 'Asignado',
        'mantenimiento' => 'En mantenimiento',
        'baja'          => 'De baja',
    ];

    /** Documentos con fecha de vencimiento que se controlan por vehículo. */
    const DOCUMENTOS = [
        'fecha_soat'             => 'SOAT',
        'fecha_revision_tecnica' => 'Revisión técnica',
        'fecha_seguro'           => 'Seguro vehicular',
    ];

    protected $fillable = [
        'placa', 'marca', 'modelo', 'anio_fabricacion', 'color',
        'numero_serie', 'numero_motor', 'tipo', 'combustible',
        'valor_adquisicion', 'fecha_adquisicion', 'kilometraje_actual',
        'kilometraje_siguiente_mantenimiento', 'fecha_soat',
        'fecha_revision_tecnica', 'fecha_seguro', 'estado',
        'area_asignada', 'responsable_id', 'observaciones', 'imagen', 'creado_por',
    ];

    protected $casts = [
        'fecha_adquisicion'      => 'date',
        'fecha_soat'             => 'date',
        'fecha_revision_tecnica' => 'date',
        'fecha_seguro'           => 'date',
        'valor_adquisicion'      => 'decimal:2',
    ];

    /* ── Relaciones ── */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function mantenimientos()
    {
        return $this->hasMany(VehiculoMantenimiento::class);
    }

    public function mantenimientosPendientes()
    {
        return $this->mantenimientos()->whereIn('estado', ['programado', 'en_proceso']);
    }

    /* ── Control documentario ── */

    /**
     * Situación de un documento: 'vigente', 'por_vencer', 'vencido' o 'sin_registro'.
     */
    public function situacionDocumento(string $campo, ?int $dias = null): string
    {
        $dias ??= config('inpe.dias_alerta.documentos_vehiculo', 30);
        /** @var Carbon|null $fecha */
        $fecha = $this->{$campo};

        return match (true) {
            $fecha === null                          => 'sin_registro',
            $fecha->isBefore(today())                => 'vencido',
            $fecha->lte(today()->addDays($dias))     => 'por_vencer',
            default                                  => 'vigente',
        };
    }

    public function soatVencido(): bool
    {
        return $this->situacionDocumento('fecha_soat') === 'vencido';
    }

    public function soatPorVencer(int $dias = 30): bool
    {
        return $this->situacionDocumento('fecha_soat', $dias) === 'por_vencer';
    }

    public function revisionTecnicaVencida(): bool
    {
        return $this->situacionDocumento('fecha_revision_tecnica') === 'vencido';
    }

    /* ── Atributos calculados ── */
    public function getDescripcionAttribute(): string
    {
        return trim("{$this->marca} {$this->modelo}");
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function getCombustibleLabelAttribute(): string
    {
        return self::COMBUSTIBLES[$this->combustible] ?? ucfirst((string) $this->combustible);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'operativo'     => 'success',
            'disponible'    => 'info',
            'asignado'      => 'primary',
            'mantenimiento' => 'warning',
            'baja'          => 'danger',
            default         => 'secondary',
        };
    }
}

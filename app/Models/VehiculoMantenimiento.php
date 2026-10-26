<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehiculoMantenimiento extends Model
{
    use HasFactory, SoftDeletes, RegistraAuditoria;

    protected string $moduloAuditoria = 'mantenimientos';

    const TIPOS = [
        'preventivo' => 'Preventivo',
        'correctivo' => 'Correctivo',
        'emergencia' => 'Emergencia',
    ];

    const ESTADOS = [
        'programado' => 'Programado',
        'en_proceso' => 'En proceso',
        'completado' => 'Completado',
        'cancelado'  => 'Cancelado',
    ];

    protected $fillable = [
        'vehiculo_id', 'tipo_mantenimiento', 'descripcion',
        'fecha_programada', 'fecha_realizado',
        'costo_estimado', 'costo_real', 'total',
        'taller', 'tecnico_responsable', 'kilometraje_mantenimiento',
        'estado', 'observaciones',
        'orden_servicio', 'fecha_orden_servicio', 'registro_siaf',
        'factura_numero', 'factura_serie', 'factura_numero_completo', 'factura_fecha',
        'registrado_por', 'aprobado_por', 'fecha_aprobacion',
    ];

    protected $casts = [
        'fecha_programada'     => 'date',
        'fecha_realizado'      => 'date',
        'fecha_orden_servicio' => 'date',
        'factura_fecha'        => 'date',
        'fecha_aprobacion'     => 'datetime',
        'costo_estimado'       => 'decimal:2',
        'costo_real'           => 'decimal:2',
        'total'                => 'decimal:2',
    ];

    /* ── Relaciones ── */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class)->withTrashed();
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /* ── Atributos calculados ── */

    /** Importe que se considera como gasto: total facturado, costo real o costo estimado. */
    public function getTotalEfectivoAttribute(): float
    {
        return (float) ($this->total ?? $this->costo_real ?? $this->costo_estimado ?? 0);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_mantenimiento] ?? ucfirst((string) $this->tipo_mantenimiento);
    }

    public function getTipoBadgeAttribute(): string
    {
        return match ($this->tipo_mantenimiento) {
            'preventivo' => 'info',
            'correctivo' => 'warning',
            'emergencia' => 'danger',
            default      => 'secondary',
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'programado' => 'warning',
            'en_proceso' => 'info',
            'completado' => 'success',
            default      => 'secondary',
        };
    }

    public function getComprobanteAttribute(): ?string
    {
        $numero = $this->factura_numero_completo ?: $this->factura_numero;

        return $numero ? trim(($this->factura_serie ? $this->factura_serie . '-' : '') . $numero) : null;
    }

    public function getEstaAbiertoAttribute(): bool
    {
        return in_array($this->estado, ['programado', 'en_proceso'], true);
    }

    public function getEstaAtrasadoAttribute(): bool
    {
        return $this->esta_abierto && $this->fecha_programada?->isBefore(today());
    }
}

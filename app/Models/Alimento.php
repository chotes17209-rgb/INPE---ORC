<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use App\Support\Catalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alimento extends Model
{
    use HasFactory, SoftDeletes, RegistraAuditoria;

    protected string $moduloAuditoria = 'alimentos';

    const PENALES = [
        'HYO'      => 'EP Huancayo',
        'HVCA'     => 'EP Huancavelica',
        'AYACUCHO' => 'EP Ayacucho',
    ];

    const ESTADOS = Catalogo::ESTADOS_PAGO;

    protected $fillable = [
        'numero_registro',
        'penal', 'periodo_inicio', 'periodo_fin',
        'orden_servicio', 'fecha_orden_servicio', 'registro_siaf',
        'numero_contrato',
        'garantia_descripcion', 'garantia_monto', 'garantia_vencimiento',
        'factura_serie', 'factura_numero', 'fecha_emision',
        'proveedor_ruc', 'proveedor_nombre', 'proveedor_razon_social',
        'base_imponible', 'igv', 'otros', 'total',
        'estado', 'area_responsable',
        'fecha_pago', 'metodo_pago', 'numero_voucher',
        'descripcion', 'observaciones', 'archivo_adjunto',
        'registrado_por', 'aprobado_por', 'fecha_aprobacion',
    ];

    protected $casts = [
        'periodo_inicio'       => 'date',
        'periodo_fin'          => 'date',
        'fecha_orden_servicio' => 'date',
        'fecha_emision'        => 'date',
        'garantia_vencimiento' => 'date',
        'fecha_pago'           => 'date',
        'fecha_aprobacion'     => 'datetime',
        'base_imponible'       => 'decimal:2',
        'igv'                  => 'decimal:2',
        'otros'                => 'decimal:2',
        'total'                => 'decimal:2',
        'garantia_monto'       => 'decimal:2',
    ];

    /* ── Eventos ── */
    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->numero_registro)) {
                $ultimo = static::withTrashed()->max('id') ?? 0;
                $m->numero_registro = 'ALI-' . date('Y') . '-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Marca como vencidos los registros pendientes cuyo periodo de suministro ya concluyó.
     * Devuelve la cantidad de registros actualizados.
     */
    public static function actualizarVencidos(): int
    {
        return static::where('estado', 'pendiente')
            ->whereDate('periodo_fin', '<', today())
            ->update(['estado' => 'vencido']);
    }

    /* ── Relaciones ── */
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por'); }
    public function aprobadoPor()   { return $this->belongsTo(User::class, 'aprobado_por'); }

    /* ── Atributos calculados ── */
    public function getPenalLabelAttribute(): string
    {
        return self::PENALES[$this->penal] ?? $this->penal ?? '—';
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function getEstadoBadgeAttribute(): string
    {
        return Catalogo::colorEstadoPago($this->estado);
    }

    public function getComprobanteAttribute(): ?string
    {
        if (! $this->factura_numero) {
            return null;
        }

        // Evita duplicar la serie cuando el número ya la incluye (p. ej. «F001-00123»).
        return $this->factura_serie && ! str_starts_with($this->factura_numero, $this->factura_serie)
            ? "{$this->factura_serie}-{$this->factura_numero}"
            : $this->factura_numero;
    }

    public function getMetodoPagoLabelAttribute(): string
    {
        return Catalogo::metodoPago($this->metodo_pago);
    }

    public function getPuedePagarseAttribute(): bool
    {
        return in_array($this->estado, Catalogo::ESTADOS_PAGABLES, true);
    }

    public function getEsEditableAttribute(): bool
    {
        return $this->estado !== 'pagado';
    }

    /* ── Scopes ── */
    public function scopePorAnio($query, int $anio)
    {
        return $query->whereYear('fecha_emision', $anio);
    }

    public function scopePorPenal($query, string $penal)
    {
        return $query->where('penal', $penal);
    }
}

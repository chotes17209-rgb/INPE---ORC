<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use App\Support\Catalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperacionReciproca extends Model
{
    use HasFactory, SoftDeletes, RegistraAuditoria;

    protected $table = 'operaciones_reciprocas';

    protected string $moduloAuditoria = 'operaciones';

    const SERVICIOS_BASICOS = [
        'energia_electrica' => 'Energía eléctrica',
        'agua_saneamiento'  => 'Agua y saneamiento',
        'telefonia_fija'    => 'Telefonía fija',
        'telefonia_movil'   => 'Telefonía móvil',
        'internet'          => 'Internet',
    ];

    const OTROS_PAGOS = [
        'contrato_estado'             => 'Contrato con el Estado',
        'alquiler'                    => 'Alquiler',
        'convenio_interinstitucional' => 'Convenio interinstitucional',
        'pago_municipio'              => 'Pago a municipalidad',
        'otro'                        => 'Otro',
    ];

    const ESTADOS = Catalogo::ESTADOS_PAGO;

    protected $fillable = [
        'numero_operacion', 'tipo_servicio',
        'entidad_proveedora', 'razon_social', 'direccion_entidad', 'ruc_entidad',
        'numero_contrato', 'servicio_prestado',
        'vigencia_contrato_inicio', 'vigencia_contrato_fin',
        'usuario_servicio', 'penal_pertenece',
        'factura_serie', 'numero_factura',
        'fecha_emision', 'fecha_facturacion', 'fecha_vencimiento',
        'base_imponible', 'monto', 'igv', 'otros', 'total',
        'orden_servicio', 'fecha_orden_servicio', 'registro_siaf',
        'periodo_mes', 'periodo_anio',
        'estado', 'metodo_pago', 'numero_voucher', 'fecha_pago',
        'area_responsable', 'descripcion', 'observaciones', 'archivo_adjunto',
        'registrado_por', 'aprobado_por', 'fecha_aprobacion',
    ];

    protected $casts = [
        'fecha_emision'            => 'date',
        'fecha_facturacion'        => 'date',
        'fecha_vencimiento'        => 'date',
        'fecha_orden_servicio'     => 'date',
        'fecha_pago'               => 'date',
        'vigencia_contrato_inicio' => 'date',
        'vigencia_contrato_fin'    => 'date',
        'fecha_aprobacion'         => 'datetime',
        'base_imponible'           => 'decimal:2',
        'monto'                    => 'decimal:2',
        'igv'                      => 'decimal:2',
        'otros'                    => 'decimal:2',
        'total'                    => 'decimal:2',
    ];

    /* ── Eventos ── */
    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->numero_operacion)) {
                $ultimo = static::withTrashed()->max('id') ?? 0;
                $m->numero_operacion = 'ORC-' . date('Y') . '-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /** Todos los tipos de servicio (servicios básicos y otros pagos). */
    public static function tipos(): array
    {
        return self::SERVICIOS_BASICOS + self::OTROS_PAGOS;
    }

    /**
     * Marca como vencidas las operaciones pendientes cuya fecha de vencimiento ya pasó.
     * Devuelve la cantidad de registros actualizados.
     */
    public static function actualizarVencidas(): int
    {
        return static::where('estado', 'pendiente')
            ->whereDate('fecha_vencimiento', '<', today())
            ->update(['estado' => 'vencido']);
    }

    /* ── Relaciones ── */
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por'); }
    public function aprobadoPor()   { return $this->belongsTo(User::class, 'aprobado_por'); }

    /* ── Atributos calculados ── */
    public function getTipoServicioLabelAttribute(): string
    {
        return self::tipos()[$this->tipo_servicio] ?? ucfirst(str_replace('_', ' ', (string) $this->tipo_servicio));
    }

    public function getGrupoServicioAttribute(): string
    {
        return array_key_exists($this->tipo_servicio, self::SERVICIOS_BASICOS) ? 'Servicio básico' : 'Otro pago';
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
        if (! $this->numero_factura) {
            return null;
        }

        // Evita duplicar la serie cuando el número ya la incluye (p. ej. «F001-00123»).
        return $this->factura_serie && ! str_starts_with($this->numero_factura, $this->factura_serie)
            ? "{$this->factura_serie}-{$this->numero_factura}"
            : $this->numero_factura;
    }

    public function getPeriodoAttribute(): string
    {
        return Catalogo::mes($this->periodo_mes) . ' ' . $this->periodo_anio;
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

    public function getEstaVencidaAttribute(): bool
    {
        return $this->estado !== 'pagado' && $this->fecha_vencimiento?->isBefore(today());
    }

    /* ── Scopes ── */
    public function scopeServiciosBasicos($query)
    {
        return $query->whereIn('tipo_servicio', array_keys(self::SERVICIOS_BASICOS));
    }

    public function scopePorPeriodo($query, int $mes, int $anio)
    {
        return $query->where('periodo_mes', $mes)->where('periodo_anio', $anio);
    }
}

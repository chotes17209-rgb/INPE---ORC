<?php

namespace App\Support;

/**
 * Catálogos compartidos por varios módulos del sistema.
 */
final class Catalogo
{
    public const METODOS_PAGO = [
        'transferencia'     => 'Transferencia bancaria',
        'cheque'            => 'Cheque',
        'efectivo'          => 'Efectivo',
        'debito_automatico' => 'Débito automático',
    ];

    /** Estados de los comprobantes por pagar (alimentos y operaciones recíprocas). */
    public const ESTADOS_PAGO = [
        'pendiente'  => 'Pendiente',
        'en_proceso' => 'En proceso',
        'observado'  => 'Observado',
        'vencido'    => 'Vencido',
        'pagado'     => 'Pagado',
    ];

    /** Estados que todavía admiten el registro de un pago. */
    public const ESTADOS_PAGABLES = ['pendiente', 'en_proceso', 'observado', 'vencido'];

    public const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** Color (variante Bootstrap) asociado a cada estado de pago. */
    public static function colorEstadoPago(?string $estado): string
    {
        return match ($estado) {
            'pagado'     => 'success',
            'vencido'    => 'danger',
            'en_proceso' => 'info',
            'observado'  => 'warning',
            default      => 'secondary',
        };
    }

    public static function metodoPago(?string $metodo): string
    {
        return self::METODOS_PAGO[$metodo] ?? '—';
    }

    public static function mes(?int $mes): string
    {
        return self::MESES[$mes] ?? '—';
    }
}

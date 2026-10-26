<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera archivos CSV compatibles con Microsoft Excel (UTF-8 con BOM),
 * con una cabecera institucional uniforme para todos los reportes.
 */
final class ExportadorCsv
{
    /**
     * @param  string    $archivo     Nombre del archivo sin extensión.
     * @param  string    $titulo      Título del reporte.
     * @param  array     $columnas    Encabezados de columna.
     * @param  iterable  $filas       Filas (arrays) a exportar.
     * @param  array     $pie         Filas adicionales al final (totales, etc.).
     */
    public static function descargar(string $archivo, string $titulo, array $columnas, iterable $filas, array $pie = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($titulo, $columnas, $filas, $pie) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // BOM para que Excel reconozca las tildes

            fputcsv($f, [mb_strtoupper(config('inpe.institucion')) . ' — ' . mb_strtoupper(config('inpe.oficina'))]);
            fputcsv($f, [config('inpe.area')]);
            fputcsv($f, [$titulo]);
            fputcsv($f, ['Fecha de generación: ' . now()->format('d/m/Y H:i') . ' — Usuario: ' . (auth()->user()?->nombre_completo ?? 'Sistema')]);
            fputcsv($f, []);
            fputcsv($f, $columnas);

            foreach ($filas as $fila) {
                fputcsv($f, $fila);
            }

            if ($pie !== []) {
                fputcsv($f, []);
                foreach ($pie as $fila) {
                    fputcsv($f, $fila);
                }
            }

            fclose($f);
        }, $archivo . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Formato numérico sin separador de miles para que Excel lo trate como número. */
    public static function monto($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    public static function fecha($fecha): string
    {
        return $fecha?->format('d/m/Y') ?? '';
    }
}

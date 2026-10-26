<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Genera los documentos PDF del sistema con la plantilla institucional
 * y la numeración «Página X de Y» en el pie de cada página.
 */
final class DocumentoPdf
{
    public static function descargar(string $vista, array $datos, string $archivo, string $orientacion = 'portrait'): Response
    {
        $pdf = Pdf::loadView($vista, $datos)->setPaper('a4', $orientacion);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(
            $canvas->get_width() - 100,
            $canvas->get_height() - 33,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente,
            7,
            [0.36, 0.41, 0.47]
        );

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $archivo . '"',
        ]);
    }
}

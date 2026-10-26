@extends('reportes.layouts.pdf')
@section('titulo', 'Resumen ejecutivo de la gestión contable')
@section('subtitulo', 'Documento elaborado con apoyo de inteligencia artificial sobre los registros del sistema al ' . now()->format('d/m/Y'))
@section('codigo', 'RES-IA-' . now()->format('Ymd'))
@section('estilos')
    .contenido { font-size: 9px; line-height: 1.6; margin-top: 8px; }
    .contenido h3 { font-size: 9.6px; color: #12355b; text-transform: uppercase; margin: 12px 0 3px; border-bottom: .6px solid #9aa6b5; padding-bottom: 2px; }
    .contenido p { margin: 0 0 4px; }
    .contenido ul { margin: 2px 0 6px 14px; padding: 0; }
@endsection

@section('contenido')
@php
    // Convierte el texto plano de la IA en párrafos, títulos y listas (sin interpretar HTML).
    $lineas = preg_split('/\r?\n/', trim($contenido));
    $enLista = false;
    $html = '';
    foreach ($lineas as $linea) {
        $l = trim($linea);
        $esVineta = preg_match('/^(?:[-*•]|\d+[.)])\s+(.*)$/u', $l, $mv);
        $esTitulo = preg_match('/^(?:#{1,4}\s+(.*)|\*\*([^*]+)\*\*:?|(\d+\.\s+[A-ZÁÉÍÓÚÑ ]{4,}):?)$/u', $l, $mt);
        if ($l === '' || ! $esVineta || $esTitulo) {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
        }
        if ($l === '') continue;
        $negrita = fn ($t) => preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($t));
        if ($esTitulo) {
            $html .= '<h3>' . e(trim($mt[1] ?: ($mt[2] ?? '') ?: ($mt[3] ?? ''), ' :')) . '</h3>';
        } elseif ($esVineta) {
            if (! $enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $negrita($mv[1]) . '</li>';
        } else {
            $html .= '<p>' . $negrita($l) . '</p>';
        }
    }
    if ($enLista) { $html .= '</ul>'; }
@endphp
<div class="contenido">{!! $html !!}</div>

<p class="nota">El presente resumen tiene carácter referencial. Las cifras deben contrastarse con los reportes oficiales del sistema y con la información del SIAF antes de su uso en documentos de gestión.</p>
@endsection

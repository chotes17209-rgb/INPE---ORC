{{--
    Plantilla institucional para documentos PDF (DomPDF).
    Secciones: titulo, subtitulo (opcional), codigo (opcional), contenido.
    Variables opcionales: $orientacion ('landscape'|'portrait').
    Nota: DomPDF no admite flexbox ni grid; la maquetación usa tablas.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>@yield('titulo')</title>
<style>
    @page { margin: 2.55cm 1.1cm 1.5cm 1.1cm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.4px; color: #1b2635; margin: 0; }

    /* Membrete y pie fijos en cada página */
    .membrete { position: fixed; top: -2.15cm; left: 0; right: 0; height: 1.95cm; }
    .pie { position: fixed; bottom: -1.05cm; left: 0; right: 0; height: .75cm; border-top: .6px solid #9aa6b5; padding-top: 4px; font-size: 7px; color: #5d6878; }

    .gob { border-collapse: collapse; }
    .gob td { padding: 4px 7px; font-size: 7.6px; font-weight: bold; color: #fff; vertical-align: middle; line-height: 1.15; }
    .gob .peru { background: #c8102e; font-size: 9px; letter-spacing: .5px; }
    .gob .sector { background: #3b3b3b; }
    .gob .entidad { background: #6b6b6b; }
    .gob .oficina { background: #e4e7eb; color: #1b2635; }

    .doc-titulo { margin-top: 7px; border-bottom: 1.4px solid #12355b; padding-bottom: 4px; }
    .doc-titulo h1 { margin: 0; font-size: 11.5px; color: #12355b; text-transform: uppercase; letter-spacing: .3px; }
    .doc-titulo .sub { font-size: 7.6px; color: #5d6878; margin-top: 1px; }
    .doc-meta { font-size: 7.2px; color: #5d6878; text-align: right; line-height: 1.45; }
    .doc-meta strong { color: #1b2635; }

    /* Tablas de datos */
    table.datos { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.datos th { background: #12355b; color: #fff; font-size: 7px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; text-align: left; border: .5px solid #12355b; }
    table.datos td { padding: 3.2px 3px; border: .5px solid #d3d9e1; vertical-align: top; }
    table.datos tbody tr:nth-child(even) td { background: #f5f7fa; }
    table.datos tfoot td { background: #e8eef6; font-weight: bold; border-top: 1.2px solid #12355b; }
    .r { text-align: right; } .c { text-align: center; } .nw { white-space: nowrap; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.6px; }
    .muted { color: #6b7685; }
    .sub-celda { display: block; font-size: 6.8px; color: #6b7685; }

    /* Bloques de resumen */
    table.resumen { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 6px -4px 2px; }
    table.resumen td { background: #f2f5f9; border: .5px solid #d3d9e1; border-left: 3px solid #12355b; padding: 5px 7px; }
    table.resumen .et { font-size: 6.6px; text-transform: uppercase; color: #5d6878; letter-spacing: .3px; }
    table.resumen .va { font-size: 10.5px; font-weight: bold; color: #12355b; }

    .filtros { margin-top: 5px; padding: 4px 6px; background: #f7f9fb; border: .5px solid #d3d9e1; font-size: 7.2px; color: #3f4a59; }
    .seccion { margin: 11px 0 3px; font-size: 8.6px; font-weight: bold; color: #12355b; text-transform: uppercase; letter-spacing: .3px; border-bottom: .6px solid #9aa6b5; padding-bottom: 2px; }

    /* Fichas (comprobantes) */
    table.ficha { width: 100%; border-collapse: collapse; margin-top: 3px; }
    table.ficha td { border: .5px solid #d3d9e1; padding: 4px 6px; vertical-align: top; width: 25%; }
    table.ficha .et { display: block; font-size: 6.6px; text-transform: uppercase; color: #6b7685; letter-spacing: .2px; }
    table.ficha .va { display: block; font-size: 8.6px; font-weight: bold; margin-top: 1px; }

    .estado { padding: 1px 4px; border-radius: 2px; font-size: 6.8px; font-weight: bold; white-space: nowrap; }
    .e-success { background: #e2f2e8; color: #17603c; }
    .e-warning { background: #fbefd9; color: #8a5409; }
    .e-danger  { background: #fbe4e1; color: #a01b12; }
    .e-info    { background: #e1edf7; color: #1a5680; }
    .e-primary { background: #e8eef6; color: #12355b; }
    .e-secondary { background: #eceff3; color: #4b5563; }

    .firmas { width: 100%; margin-top: 46px; border-collapse: collapse; }
    .firmas td { width: 33%; text-align: center; vertical-align: bottom; padding: 0 14px; font-size: 7.4px; }
    .firmas .linea { border-top: .7px solid #1b2635; padding-top: 3px; }
    .nota { margin-top: 10px; font-size: 6.9px; color: #6b7685; }
    @yield('estilos')
</style>
</head>
<body>

<div class="membrete">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="vertical-align:top">
                <table class="gob">
                    <tr>
                        <td class="peru">PERÚ</td>
                        <td class="sector">{{ config('inpe.sector') }}</td>
                        <td class="entidad">{{ config('inpe.institucion') }}</td>
                        <td class="oficina">{{ config('inpe.oficina') }}<br><span style="font-weight:normal">{{ config('inpe.area') }}</span></td>
                    </tr>
                </table>
            </td>
            <td class="doc-meta" style="vertical-align:top;width:30%">
                @hasSection('codigo')<strong>@yield('codigo')</strong><br>@endif
                Emitido: {{ now()->format('d/m/Y H:i') }}<br>
                Usuario: {{ auth()->user()?->nombre_completo ?? 'Sistema' }}
            </td>
        </tr>
    </table>
    <div class="doc-titulo">
        <h1>@yield('titulo')</h1>
        @hasSection('subtitulo')<div class="sub">@yield('subtitulo')</div>@endif
    </div>
</div>

<div class="pie">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td>{{ config('inpe.sistema') }} v{{ config('inpe.version') }} — Documento generado automáticamente. RUC {{ config('inpe.ruc') }}.</td>
            <td style="width:110px"></td>
        </tr>
    </table>
</div>

<main>
    @yield('contenido')
</main>
</body>
</html>

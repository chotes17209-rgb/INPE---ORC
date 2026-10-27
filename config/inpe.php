<?php

/*
|--------------------------------------------------------------------------
| Datos institucionales
|--------------------------------------------------------------------------
| Se utilizan en la cabecera del sistema, en los reportes PDF y en los
| comprobantes. Pueden ajustarse desde el archivo .env sin tocar código.
*/

return [
    'sector'      => env('INPE_SECTOR', 'Ministerio de Justicia y Derechos Humanos'),
    'institucion' => env('INPE_INSTITUCION', 'Instituto Nacional Penitenciario'),
    'siglas'      => env('INPE_SIGLAS', 'INPE'),
    'oficina'     => env('INPE_OFICINA', 'Oficina Regional Centro'),
    'sede'        => env('INPE_SEDE', 'Huancayo'),
    'area'        => env('INPE_AREA', 'Área Administrativa'),
    'ruc'         => env('INPE_RUC', '20131370564'),
    'sistema'     => env('INPE_SISTEMA', 'Sistema Integrado de Gestión Administrativa'),
    'version'     => '4.0',

    // Días de anticipación para considerar un documento "por vencer"
    'dias_alerta' => [
        'documentos_vehiculo' => 30,
        'operaciones'         => 7,
        'alimentos'           => 7,
        'notificacion'        => 3,
    ],
];

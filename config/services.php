<?php

return [
    'mailgun'  => ['domain' => env('MAILGUN_DOMAIN'), 'secret' => env('MAILGUN_SECRET'), 'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net')],
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],

    /*
    |--------------------------------------------------------------------------
    | Servicio de Inteligencia Artificial
    |--------------------------------------------------------------------------
    | Compatible con cualquier proveedor que implemente la API de
    | "chat completions" de OpenAI (OpenAI, Groq, etc.). Solo se cambia
    | la URL base, la clave y el modelo desde el archivo .env.
    */
    'ia' => [
        'api_key'  => env('IA_API_KEY', env('OPENAI_API_KEY')),
        'model'    => env('IA_MODEL', env('OPENAI_MODEL', 'llama-3.3-70b-versatile')),
        'base_url' => env('IA_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout'  => (int) env('IA_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Consulta de RUC (SUNAT) — apis.net.pe
    |--------------------------------------------------------------------------
    */
    /*
     | Consulta de contribuyentes (SUNAT). Se usa el primer servicio configurado:
     |  - json.pe:  RUC → razón social y razón social → RUC (JSONPE_TOKEN).
     |  - decolecta / apis.net.pe: RUC → razón social (APIPERU_TOKEN y APIPERU_URL).
     | Sin token, la búsqueda por nombre usa los proveedores ya registrados.
     */
    'jsonpe' => [
        'token'    => env('JSONPE_TOKEN'),
        'base_url' => env('JSONPE_URL', 'https://api.json.pe/api'),
    ],

    'apiperu' => [
        'token'    => env('APIPERU_TOKEN'),
        'base_url' => env('APIPERU_URL', 'https://api.apis.net.pe/v2'),
    ],
];

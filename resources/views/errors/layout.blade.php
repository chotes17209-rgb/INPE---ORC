<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('inpe.siglas', 'INPE') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/erp-inpe.css') }}" rel="stylesheet">
</head>
<body>
<main class="error-page">
    <div style="max-width:520px">
        <span class="brand-mark mx-auto mb-4" aria-hidden="true">{{ config('inpe.siglas', 'INPE') }}</span>
        <div class="error-code">@yield('codigo')</div>
        <h1 class="h4 fw-bold mt-2">@yield('titulo')</h1>
        <p class="text-muted">@yield('mensaje')</p>
        <div class="d-flex gap-2 justify-content-center mt-4">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary"><i class="bi bi-house-door me-1"></i>Ir al inicio</a>
        </div>
    </div>
</main>
</body>
</html>
